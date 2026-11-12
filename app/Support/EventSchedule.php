<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Date helpers for the race day and the public event timeline.
 */
class EventSchedule
{
    public static function raceDay(): Carbon
    {
        return Carbon::parse(config('event.race_day'));
    }

    /**
     * Timeline milestones with formatted dates and their state relative to now.
     *
     * @return Collection<int, array{title: string, description: string, dates: string, is_current: bool, is_past: bool}>
     */
    public static function timeline(): Collection
    {
        return collect(config('event.timeline'))->map(function (array $milestone): array {
            $startsAt = Carbon::parse($milestone['starts_at'])->startOfDay();
            $endsAt = Carbon::parse($milestone['ends_at'])->endOfDay();

            return [
                'title' => $milestone['title'],
                'description' => $milestone['description'],
                'dates' => self::formatRange($startsAt, $endsAt),
                'is_current' => now()->between($startsAt, $endsAt),
                'is_past' => now()->greaterThan($endsAt),
            ];
        });
    }

    /**
     * Time left until the given moment, used for the server-rendered countdown values.
     *
     * @return array{days: int, hours: int, minutes: int, seconds: int}
     */
    public static function countdownTo(CarbonInterface $moment): array
    {
        $seconds = max(0, (int) now()->diffInSeconds($moment, false));

        return [
            'days' => intdiv($seconds, 86400),
            'hours' => intdiv($seconds % 86400, 3600),
            'minutes' => intdiv($seconds % 3600, 60),
            'seconds' => $seconds % 60,
        ];
    }

    /**
     * Format a date range in Indonesian with full month names: "11 April 2027",
     * "8–10 April 2027", "5 Februari – 28 Maret 2027" or "5 Desember 2026 – 28 Maret 2027".
     */
    public static function formatRange(CarbonInterface $startsAt, CarbonInterface $endsAt): string
    {
        if ($startsAt->isSameDay($endsAt)) {
            return $startsAt->translatedFormat('j F Y');
        }

        if ($startsAt->isSameMonth($endsAt)) {
            return $startsAt->format('j').'–'.$endsAt->translatedFormat('j F Y');
        }

        $startFormat = $startsAt->isSameYear($endsAt) ? 'j F' : 'j F Y';

        return $startsAt->translatedFormat($startFormat).' – '.$endsAt->translatedFormat('j F Y');
    }
}
