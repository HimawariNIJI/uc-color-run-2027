<?php

namespace App\Enums;

/**
 * The price type of an individual package: Early Bid or Normal Bid (official names). Every
 * package has its own quota (see TicketPrice). Bundles have no price type.
 */
enum FeePeriod: string
{
    case EarlyBird = 'early_bird';
    case Regular = 'normal';

    public function label(): string
    {
        return match ($this) {
            self::EarlyBird => 'Early Bid',
            self::Regular => 'Normal Bid',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EarlyBird => 'Harga spesial dengan kuota terbatas untuk setiap paket.',
            self::Regular => 'Harga normal setelah Early Bid, dengan kuota per paket.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EarlyBird => 'bird',
            self::Regular => 'tag',
        };
    }

    /**
     * URL segment used in the registration steps, for example "early-bird".
     */
    public function slug(): string
    {
        return match ($this) {
            self::EarlyBird => 'early-bird',
            self::Regular => 'normal',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        return collect(self::cases())->first(fn (self $period): bool => $period->slug() === $slug);
    }
}
