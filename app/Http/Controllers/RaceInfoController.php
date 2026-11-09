<?php

namespace App\Http\Controllers;

use App\Enums\RaceCategory;
use App\Models\TicketPrice;
use App\Support\EventSchedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RaceInfoController extends Controller
{
    public function category(Request $request): View
    {
        return view('race.category', [
            'categories' => RaceCategory::cases(),
            'selectedCategory' => RaceCategory::tryFrom((string) $request->query('category')) ?? RaceCategory::ThreeK,
            'packages' => TicketPrice::catalogue(),
            'raceDay' => EventSchedule::raceDay(),
        ]);
    }

    public function schedule(): View
    {
        $raceDay = EventSchedule::raceDay();

        return view('race.schedule', [
            'raceDay' => $raceDay,
            'timeLeft' => EventSchedule::countdownTo($raceDay),
        ]);
    }

    public function regulation(): View
    {
        return view('pages.legal', [
            'title' => 'Race Regulation',
            'description' => 'Peraturan resmi yang wajib dipahami dan dipatuhi seluruh peserta UC Color Run 2027.',
            'items' => config('event.regulations'),
        ]);
    }
}
