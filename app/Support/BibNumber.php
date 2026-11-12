<?php

namespace App\Support;

use App\Exceptions\BibNumbersExhausted;
use App\Models\BibSequence;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/**
 * BIB numbers, participant IDs and registration codes for UC Color Run 2027. This is the only place
 * they are defined.
 *
 * A BIB has exactly 7 digits: the package's official 3-digit prefix and a 4-digit sequence that
 * starts at 0001, for example "0310001" for the first paid 3K Early Bird Internal participant. BIBs
 * are text so the leading zero stays. Every prefix has its own sequence, and every participant gets
 * their own number: a Couple Bundle uses 2 numbers, a Family Bundle 4.
 *
 * BIBs follow the order in which payments are confirmed, not the order of registering: numbers are
 * only drawn once the backend has verified the Midtrans settlement (Registration::markAsPaid), and a
 * bundle draws all of its numbers at once, so they are consecutive. A number is never reused, and a
 * registration that is never paid uses none.
 *
 * Once issued, a participant's ID is "UCCR-{BIB}" (racepack QR, Participants sheet).
 *
 * The registration code is given when registering and never changes, so it cannot contain the BIB:
 * "UCCR-", the package prefix and the package's own registration sequence, for example
 * "UCCR-052-0001" for the first 5K Early Bird External registration. Midtrans order IDs are built
 * from it. Registrations made before BIBs followed the payment order keep their older code
 * ("UCCR-0520001" or "UCR-2027-ABCDE"). The database ID is never used.
 */
final class BibNumber
{
    public const LENGTH = 7;

    public const SEQUENCE_DIGITS = 4;

    public const MAX_SEQUENCE = 9999;

    /**
     * The start of every participant ID and registration code.
     */
    public const REGISTRATION_CODE_PREFIX = 'UCCR-';

    /**
     * The official prefixes. Keys are "race category:price type:participant type" for individual
     * packages and "race category:bundle type" for bundles. These are final: never change them.
     *
     * @var array<string, string>
     */
    public const PREFIXES = [
        '3k:early_bird:internal' => '031',
        '3k:early_bird:external' => '032',
        '3k:normal:internal' => '033',
        '3k:normal:external' => '034',
        '3k:family' => '035',
        '5k:early_bird:internal' => '051',
        '5k:early_bird:external' => '052',
        '5k:normal:internal' => '053',
        '5k:normal:external' => '054',
        '5k:couple' => '055',
        '10k:early_bird:internal' => '101',
        '10k:early_bird:external' => '102',
        '10k:normal:internal' => '103',
        '10k:normal:external' => '104',
    ];

    /**
     * The official prefix of a package.
     *
     * @throws InvalidArgumentException for a combination that is not an official package
     */
    public static function prefix(string $raceCategory, ?string $priceType, string $registrationType, ?string $bundleType = null): string
    {
        $key = $bundleType !== null ? "{$raceCategory}:{$bundleType}" : "{$raceCategory}:{$priceType}:{$registrationType}";

        return self::PREFIXES[$key] ?? throw new InvalidArgumentException("There is no official BIB prefix for \"{$key}\".");
    }

    public static function make(string $prefix, int $sequence): string
    {
        if (! in_array($prefix, self::PREFIXES, true)) {
            throw new InvalidArgumentException("\"{$prefix}\" is not an official BIB prefix.");
        }

        if ($sequence < 1 || $sequence > self::MAX_SEQUENCE) {
            throw new InvalidArgumentException('BIB sequence must be between 1 and '.self::MAX_SEQUENCE.", got {$sequence}.");
        }

        return $prefix.str_pad((string) $sequence, self::SEQUENCE_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Draw $count consecutive numbers of the prefix and return them in order. Only called when a
     * verified payment issues BIBs (Registration::markAsPaid).
     *
     * Call it inside the database transaction that stores the numbers. The UPDATE locks the
     * prefix's sequence row before anything is read, so a simultaneous confirmation of the same
     * package waits until this transaction ends and then continues after these numbers. If the
     * transaction is rolled back, the sequence goes back with it. Either way no number is handed
     * out twice; the unique indexes on the BIB columns are the last guard.
     *
     * @return list<string>
     *
     * @throws BibNumbersExhausted when fewer than $count numbers are left before 9999
     */
    public static function reserve(string $prefix, int $count = 1): array
    {
        if ($count < 1) {
            throw new InvalidArgumentException("Reserve at least one BIB number, not {$count}.");
        }

        self::ensureTransaction('Reserve BIB numbers inside the database transaction that stores them.');

        $reserved = BibSequence::query()
            ->whereKey($prefix)
            ->where('last_sequence', '<=', self::MAX_SEQUENCE - $count)
            ->increment('last_sequence', $count);

        if ($reserved === 0) {
            throw BibSequence::query()->whereKey($prefix)->exists()
                ? new BibNumbersExhausted($prefix, $count)
                : new InvalidArgumentException("There is no BIB sequence for prefix \"{$prefix}\".");
        }

        $last = (int) BibSequence::query()->whereKey($prefix)->value('last_sequence');

        return array_map(
            fn (int $sequence): string => self::make($prefix, $sequence),
            range($last - $count + 1, $last),
        );
    }

    /**
     * Make sure the prefix still has $count numbers left, so a registration is refused up front
     * when its participants could never get a BIB. Nothing is reserved.
     *
     * @throws BibNumbersExhausted
     */
    public static function ensureAvailable(string $prefix, int $count): void
    {
        $last = BibSequence::query()->whereKey($prefix)->value('last_sequence')
            ?? throw new InvalidArgumentException("There is no BIB sequence for prefix \"{$prefix}\".");

        if ((int) $last > self::MAX_SEQUENCE - $count) {
            throw new BibNumbersExhausted($prefix, $count);
        }
    }

    /**
     * The next registration code of a package, for example "UCCR-052-0001".
     *
     * Call it inside the transaction that stores the registration, after locking the package row
     * (TicketPrice::lockForRegistration). Every registration of a package takes that lock first and
     * only the package's one active row is ever registered for, so a package's registrations get
     * their codes one at a time and never the same one; the unique index on the code is the last guard.
     *
     * @throws BibNumbersExhausted after 9999 registrations of the prefix
     */
    public static function nextRegistrationCode(string $prefix): string
    {
        self::ensureTransaction('Make registration codes inside the database transaction that stores the registration.');

        if (! in_array($prefix, self::PREFIXES, true)) {
            throw new InvalidArgumentException("\"{$prefix}\" is not an official BIB prefix.");
        }

        $start = self::REGISTRATION_CODE_PREFIX.$prefix.'-';
        $last = Registration::query()->where('code', 'like', $start.'%')->max('code');
        $sequence = $last === null ? 1 : (int) substr($last, strlen($start)) + 1;

        if ($sequence > self::MAX_SEQUENCE) {
            throw new BibNumbersExhausted($prefix);
        }

        return $start.str_pad((string) $sequence, self::SEQUENCE_DIGITS, '0', STR_PAD_LEFT);
    }

    public static function isValid(?string $bibNumber): bool
    {
        return $bibNumber !== null
            && preg_match('/^\d{'.self::LENGTH.'}$/', $bibNumber) === 1
            && in_array(substr($bibNumber, 0, 3), self::PREFIXES, true)
            && (int) substr($bibNumber, -self::SEQUENCE_DIGITS) >= 1;
    }

    /**
     * A participant's ID once their BIB is issued: "UCCR-{BIB}".
     */
    public static function participantId(string $bibNumber): string
    {
        return self::REGISTRATION_CODE_PREFIX.$bibNumber;
    }

    private static function ensureTransaction(string $message): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException($message);
        }
    }
}
