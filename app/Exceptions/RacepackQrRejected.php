<?php

namespace App\Exceptions;

use App\Enums\QrScanRejection;
use App\Models\Registration;
use RuntimeException;

/**
 * A scanned racepack QR or typed ID that cannot be accepted. $registration is set when the scan
 * did point at one registration (for example an unpaid one).
 */
class RacepackQrRejected extends RuntimeException
{
    public function __construct(public readonly QrScanRejection $reason, public readonly ?Registration $registration = null)
    {
        parent::__construct($reason->message($registration?->code));
    }
}
