<?php

namespace Database\Seeders\Foundation;

use InvalidArgumentException;

/**
 * BIB numbers and registration codes for UC Color Run 2027. This is the only place the codes
 * are defined.
 *
 * A BIB has 10 digits: race category (2), price type (2), participant type (2), sequence (4).
 * Example: 10K, Early Bird, External, 19th number = 10 01 02 0019 = "1001020019",
 * with registration code "UCCR-1001020019". BIBs are text so the leading zero of 3K and 5K stays.
 *
 * Numbers are handed out in two steps:
 *  1. Registering reserves the next number for the prefix in registrations.registration_code.
 *     No BIB is issued yet.
 *  2. Once the payment is confirmed, the first participant receives the reserved number as their
 *     BIB, and other package members receive the next free numbers for the same prefix.
 *
 * The next number is the highest sequence already used for the prefix, in registration codes
 * and BIBs, plus one. It must be taken inside a database transaction, with the unique indexes on
 * registration_code and bib_number as the final guard (retry on a duplicate). Numbers are never
 * reused, so registrations that are never paid leave gaps. The database ID is never used.
 *
 * This class lives with the foundation seeders until the website moves to the new schema; the
 * generation itself is built then.
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
     * @var array<string, string>
     */
    public const PRICE_TYPE_CODES = ['early_bird' => '01', 'normal' => '02'];

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
    public static function prefix(string $raceCategory, string $priceType, string $registrationType, ?string $bundleType = null): string
    {
        $participantType = $registrationType === 'bundling' ? "bundling:{$bundleType}" : $registrationType;

        return self::code(self::RACE_CATEGORY_CODES, $raceCategory, 'race category')
            .self::code(self::PRICE_TYPE_CODES, $priceType, 'price type')
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

    public static function registrationCode(string $bibNumber): string
    {
        return self::REGISTRATION_CODE_PREFIX.$bibNumber;
    }

    /**
     * @param  array<string, string>  $codes
     */
    private static function code(array $codes, string $value, string $name): string
    {
        return $codes[$value] ?? throw new InvalidArgumentException("Unknown {$name} \"{$value}\" for a BIB number.");
    }
}
