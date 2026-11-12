<?php

namespace App\Support;

use App\Enums\QrScanRejection;
use App\Exceptions\RacepackQrRejected;
use App\Models\Registration;
use App\Models\RegistrationMember;
use BaconQrCode\Common\ErrorCorrectionLevel;
use Throwable;

/**
 * The racepack QR participants show at BIB collection, and how the admin scanner reads it.
 *
 * The payload is every participant's ID, "UCCR-{BIB}", the payer first and then bundle members by
 * position, joined with ";":
 *   individual:    UCCR-0320001
 *   Couple Bundle: UCCR-0550001;UCCR-0550002
 *   Family Bundle: UCCR-0350001;UCCR-0350002;UCCR-0350003;UCCR-0350004
 *
 * Nothing is stored. The payload is built from the BIBs issued when the payment was confirmed, so
 * it only exists for paid registrations and is the same on every page load; the image is rendered
 * from the payload, so the same payload always gives the same QR.
 */
final class RacepackQr
{
    public const SEPARATOR = ';';

    /**
     * Rendered size in pixels; the page scales the SVG.
     */
    public const SIZE = 320;

    /**
     * The largest bundle (Family Bundle).
     */
    private const MAX_PARTICIPANTS = 4;

    private const PARTICIPANT_ID = '/^'.BibNumber::REGISTRATION_CODE_PREFIX.'(\d{'.BibNumber::LENGTH.'})$/';

    /**
     * The payload, or null when the QR is not active: the payment is not confirmed, or a
     * participant has no BIB.
     */
    public static function payloadFor(Registration $registration): ?string
    {
        if (! $registration->isPaid()) {
            return null;
        }

        $bibNumbers = $registration->participantBibNumbers();

        if (in_array(null, $bibNumbers, true)) {
            return null;
        }

        return implode(self::SEPARATOR, array_map(BibNumber::participantId(...), $bibNumbers));
    }

    /**
     * The QR as an inline SVG, with medium error correction for reliable scanning from a phone
     * screen. Null when it is not active, or when rendering failed (reported to the log).
     */
    public static function svgFor(Registration $registration): ?string
    {
        $payload = self::payloadFor($registration);

        if ($payload === null) {
            return null;
        }

        try {
            return QrCode::svg($payload, self::SIZE, ErrorCorrectionLevel::M(), margin: 2);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * The paid registration a scanned payload belongs to. Every ID must be a valid, unique BIB of
     * an existing participant, all of the same registration, and the payload must list exactly
     * that registration's participants in order. Anything else refuses the whole scan.
     *
     * @throws RacepackQrRejected
     */
    public static function resolve(string $payload): Registration
    {
        $ids = explode(self::SEPARATOR, trim($payload));

        if (count($ids) > self::MAX_PARTICIPANTS) {
            throw new RacepackQrRejected(QrScanRejection::Malformed);
        }

        $bibNumbers = array_map(function (string $id): string {
            if (preg_match(self::PARTICIPANT_ID, $id, $match) !== 1) {
                throw new RacepackQrRejected(QrScanRejection::Malformed);
            }

            return $match[1];
        }, $ids);

        foreach ($bibNumbers as $bibNumber) {
            if (! BibNumber::isValid($bibNumber)) {
                throw new RacepackQrRejected(QrScanRejection::InvalidBib);
            }
        }

        if (count(array_unique($bibNumbers)) !== count($bibNumbers)) {
            throw new RacepackQrRejected(QrScanRejection::DuplicateBib);
        }

        $registration = Registration::query()->with('members')->find(self::registrationIdOf($bibNumbers));

        if (! $registration->isPaid()) {
            throw new RacepackQrRejected(QrScanRejection::Unpaid, $registration);
        }

        if (self::payloadFor($registration) !== implode(self::SEPARATOR, $ids)) {
            throw new RacepackQrRejected(QrScanRejection::Incomplete, $registration);
        }

        return $registration;
    }

    /**
     * The paid registration for an ID typed at the desk when no camera is available: its
     * registration code, or any one participant's "UCCR-{BIB}".
     *
     * @throws RacepackQrRejected
     */
    public static function resolveTypedId(string $id): Registration
    {
        $registration = Registration::query()->with('members')->firstWhere('code', $id);

        if ($registration === null && preg_match(self::PARTICIPANT_ID, $id, $match) === 1) {
            $registration = Registration::query()->with('members')->find(self::registrationIdOf([$match[1]]));
        }

        if ($registration === null) {
            throw new RacepackQrRejected(QrScanRejection::NotFound);
        }

        if (! $registration->isPaid()) {
            throw new RacepackQrRejected(QrScanRejection::Unpaid, $registration);
        }

        return $registration;
    }

    /**
     * Whether a scanned or typed value only holds racepack ID characters, so it can be kept in the
     * scan history without storing anything personal a camera may have picked up.
     */
    public static function isSafeToStore(string $payload): bool
    {
        return strlen($payload) <= 255 && preg_match('/^[A-Z0-9;-]+$/', $payload) === 1;
    }

    /**
     * The one registration these BIBs belong to, as payer or bundle member.
     *
     * @param  list<string>  $bibNumbers
     *
     * @throws RacepackQrRejected
     */
    private static function registrationIdOf(array $bibNumbers): int
    {
        $payers = Registration::query()->whereIn('bib_number', $bibNumbers)->pluck('id', 'bib_number');
        $members = RegistrationMember::query()->whereIn('bib_number', $bibNumbers)->pluck('registration_id', 'bib_number');
        $registrationIds = [];

        foreach ($bibNumbers as $bibNumber) {
            $owners = array_filter([$payers[$bibNumber] ?? null, $members[$bibNumber] ?? null]);

            if ($owners === []) {
                throw new RacepackQrRejected(QrScanRejection::NotFound);
            }

            array_push($registrationIds, ...$owners);
        }

        $registrationIds = array_values(array_unique($registrationIds));

        if (count($registrationIds) > 1) {
            throw new RacepackQrRejected(QrScanRejection::MixedRegistrations);
        }

        return $registrationIds[0];
    }
}
