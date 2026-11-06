<?php

namespace App\Support;

use App\Enums\RaceCategory;
use App\Models\Registration;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Early Bird slots per race category. Paid and pending registrations hold a slot; a failed
 * payment gives it back.
 */
class EarlyBirdQuota
{
    public static function slots(RaceCategory $category): int
    {
        return (int) config("event.early_bird_slots.{$category->value}", 0);
    }

    /**
     * Slots left for every category, keyed by category value.
     *
     * @return array<string, int>
     */
    public static function remaining(): array
    {
        $taken = Registration::query()
            ->holdingEarlyBirdSlot()
            ->toBase()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return collect(RaceCategory::cases())
            ->mapWithKeys(fn (RaceCategory $category): array => [
                $category->value => max(0, self::slots($category) - (int) $taken->get($category->value, 0)),
            ])
            ->all();
    }

    public static function remainingFor(RaceCategory $category): int
    {
        $taken = Registration::query()->holdingEarlyBirdSlot()->where('category', $category)->count();

        return max(0, self::slots($category) - $taken);
    }

    public static function isAvailable(RaceCategory $category): bool
    {
        return self::remainingFor($category) > 0;
    }

    public static function isAvailableForAny(): bool
    {
        return array_sum(self::remaining()) > 0;
    }

    /**
     * Run the callback while no other request can claim this category's last slots.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public static function whileLocked(RaceCategory $category, Closure $callback): mixed
    {
        return Cache::lock("early-bird-slots:{$category->value}", 10)->block(5, $callback);
    }
}
