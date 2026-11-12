<?php

namespace App\Enums;

/**
 * Why a scanned racepack QR (or typed ID) was refused. The whole scan is refused: a bundle QR is
 * never partly accepted.
 */
enum QrScanRejection: string
{
    case Malformed = 'malformed';
    case InvalidBib = 'invalid_bib';
    case DuplicateBib = 'duplicate_bib';
    case NotFound = 'not_found';
    case MixedRegistrations = 'mixed_registrations';
    case Incomplete = 'incomplete';
    case Unpaid = 'unpaid';

    /**
     * The message for the desk staff. Registration details are added by the caller where known.
     */
    public function message(?string $registrationCode = null): string
    {
        return match ($this) {
            self::Malformed => 'Invalid QR Code: format tidak dikenali. Pastikan peserta menunjukkan QR racepack dari halaman My BIB.',
            self::InvalidBib => 'Invalid QR Code: nomor BIB di dalam QR tidak valid.',
            self::DuplicateBib => 'Invalid QR Code: QR berisi nomor BIB yang sama lebih dari sekali.',
            self::NotFound => 'Registration Not Found: tidak ada peserta dengan nomor BIB atau Registration ID tersebut.',
            self::MixedRegistrations => 'Invalid QR Code: QR berisi peserta dari registrasi yang berbeda.',
            self::Incomplete => "Invalid QR Code: QR tidak sesuai dengan daftar peserta registrasi {$registrationCode}.",
            self::Unpaid => "Registrasi {$registrationCode} belum lunas, sehingga BIB belum dapat diberikan.",
        };
    }
}
