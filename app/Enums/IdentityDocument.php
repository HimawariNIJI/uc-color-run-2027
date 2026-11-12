<?php

namespace App\Enums;

use App\Models\TicketPrice;

/**
 * The document the payer proves identity with: their own KTP, or the family's Kartu Keluarga for
 * a Family Bundle (the KK covers every family member, so nobody in that bundle needs a KTP).
 * Each document has its own form field, column and folder on the private photo disk.
 */
enum IdentityDocument: string
{
    case Ktp = 'ktp';
    case FamilyCard = 'kk';

    public static function forPackage(?TicketPrice $package): self
    {
        return $package?->isFamilyBundle() ? self::FamilyCard : self::Ktp;
    }

    /**
     * Field label, for example "Foto KTP".
     */
    public function label(): string
    {
        return match ($this) {
            self::Ktp => 'Foto KTP',
            self::FamilyCard => 'Foto Kartu Keluarga (KK)',
        };
    }

    /**
     * The photo inside a sentence, for example "Pilih foto KTP".
     */
    public function noun(): string
    {
        return match ($this) {
            self::Ktp => 'foto KTP',
            self::FamilyCard => 'foto Kartu Keluarga',
        };
    }

    /**
     * Name of the file input on the registration form.
     */
    public function field(): string
    {
        return match ($this) {
            self::Ktp => 'ktp_photo',
            self::FamilyCard => 'family_card_photo',
        };
    }

    /**
     * Registration column holding the stored photo's path.
     */
    public function column(): string
    {
        return match ($this) {
            self::Ktp => 'ktp_photo_path',
            self::FamilyCard => 'family_card_photo_path',
        };
    }
}
