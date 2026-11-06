<?php

namespace App\Enums;

/**
 * The price type a participant picks while registering. Early Bird has a limited number of
 * slots per race category (see EarlyBirdQuota). The "regular" value is shown as "Normal Price".
 */
enum FeePeriod: string
{
    case EarlyBird = 'early_bird';
    case Regular = 'regular';

    public function label(): string
    {
        return match ($this) {
            self::EarlyBird => 'Early Bird',
            self::Regular => 'Normal Price',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EarlyBird => 'Harga spesial dengan slot terbatas untuk setiap jarak lari.',
            self::Regular => 'Harga normal yang berlaku selama pendaftaran dibuka.',
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
