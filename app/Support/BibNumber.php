<?php

namespace App\Support;

use App\Models\Registration;
use App\Models\RegistrationMember;
use InvalidArgumentException;

/**
 * BIB numbers and registration codes for UC Color Run 2027. This is the only place the codes
 * are defined.
 *
 * A BIB has 10 digits: race category (2), price type (2), participant type (2), sequence (4).
 * Example: 10K, Early Bid, External, 19th number = 10 01 02 0019 = "1001020019",
 * with registration code "UCCR-1001020019". BIBs are text so the leading zero of 3K and 5K stays.
 * Bundles have no Early Bid / Normal Bid, so their price type digits are "00"
 * (5K Couple Bundle: 05 00 03 0001).
 *
 * Numbers are handed out in two steps:
 *  1. Registering reserves the next number for the package in the registration code.
 *     No BIB is issued yet.
 *  2. Once the payment is confirmed, the account holder receives the reserved number as their
 *     BIB and bundle members receive the next free numbers of the same package.
 *
 * The next number is the highest sequence already used for the prefix, in registration codes and
 * BIBs, plus one. Callers hold the package's row lock (TicketPrice::lockForRegistration), and the
 * unique indexes on the code and BIB columns are the final guard. Numbers are never reused, so
 * registrations that are never paid leave gaps. The database ID is never used.
 */
final class BibNumber
{
    public const LENGTH = 10;

    public const SEQUENCE_DIGITS = 4;

    public const REGISTRATION_CODE_PREFIX = 'UCCR-';

    /**
     * Race category code: the distance in kilometres.
     *
     * @var array<string, string>
     */
    public const RACE_CATEGORY_CODES = ['3k' => '03', '5k' => '05', '10k' => '10'];

    /**
     * Early Bid and Normal Bid; bundles (no price type) use NO_PRICE_TYPE_CODE.
     *
     * @var array<string, string>
     */
    public const PRICE_TYPE_CODES = ['early_bird' => '01', 'normal' => '02'];

    public const NO_PRICE_TYPE_CODE = '00';

    /**
     * Keyed by registration type, with the bundle type added for bundling.
     *
     * @var array<string, string>
     */
    public const PARTICIPANT_TYPE_CODES = [
        'internal' => '01',
        'external' => '02',
        'bundling:couple' => '03',
        'bundling:family' => '04',
    ];

    /**
     * The first six digits, shared by every BIB of the same race category, price type and participant type.
     */
    public static function prefix(string $raceCategory, ?string $priceType, string $registrationType, ?string $bundleType = null): string
    {
        $participantType = $registrationType === 'bundling' ? "bundling:{$bundleType}" : $registrationType;

        return self::code(self::RACE_CATEGORY_CODES, $raceCategory, 'race category')
            .($priceType === null ? self::NO_PRICE_TYPE_CODE : self::code(self::PRICE_TYPE_CODES, $priceType, 'price type'))
            .self::code(self::PARTICIPANT_TYPE_CODES, $participantType, 'participant type');
    }

    public static function make(string $prefix, int $sequence): string
    {
        $highestSequence = 10 ** self::SEQUENCE_DIGITS - 1;

        if ($sequence < 1 || $sequence > $highestSequence) {
            throw new InvalidArgumentException("BIB sequence must be between 1 and {$highestSequence}, got {$sequence}.");
        }

        return $prefix.str_pad((string) $sequence, self::SEQUENCE_DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * The next unused number for the prefix. Numbers have a fixed width, so the highest string is
     * also the highest sequence. Call it while holding the package's row lock.
     */
    public static function next(string $prefix): string
    {
        $highest = collect([
            Registration::query()->where('code', 'like', self::REGISTRATION_CODE_PREFIX.$prefix.'%')->max('code'),
            Registration::query()->where('bib_number', 'like', $prefix.'%')->max('bib_number'),
            RegistrationMember::query()->where('bib_number', 'like', $prefix.'%')->max('bib_number'),
        ])
            ->filter()
            ->map(fn (string $number): int => (int) substr($number, -self::SEQUENCE_DIGITS))
            ->max() ?? 0;

        return self::make($prefix, $highest + 1);
    }

    public static function registrationCode(string $bibNumber): string
    {
        return self::REGISTRATION_CODE_PREFIX.$bibNumber;
    }

    /**
     * The number reserved in a registration code, or null for a code in another format.
     */
    public static function fromRegistrationCode(string $code): ?string
    {
        $number = substr($code, strlen(self::REGISTRATION_CODE_PREFIX));

        return str_starts_with($code, self::REGISTRATION_CODE_PREFIX) && strlen($number) === self::LENGTH ? $number : null;
    }

    /**
     * @param  array<string, string>  $codes
     */
    private static function code(array $codes, string $value, string $name): string
    {
        return $codes[$value] ?? throw new InvalidArgumentException("Unknown {$name} \"{$value}\" for a BIB number.");
    }
}
