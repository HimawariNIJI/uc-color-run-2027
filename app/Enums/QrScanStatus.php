<?php

namespace App\Enums;

/**
 * Outcome of one racepack QR scan. Only accepted scans appear in Recent QR Scanned.
 */
enum QrScanStatus: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
