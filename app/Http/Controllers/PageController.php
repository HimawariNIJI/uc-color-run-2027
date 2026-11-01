<?php

namespace App\Http\Controllers;

use App\Support\EarlyBirdQuota;
use App\Support\EventSchedule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'raceDay' => EventSchedule::raceDay(),
            'venue' => config('event.venue'),
            'isEarlyBirdAvailable' => EarlyBirdQuota::isAvailableForAny(),
            'timeline' => EventSchedule::timeline(),
            'sponsors' => config('event.sponsors'),
            'bannerImage' => config('event.banner_image'),
            'recapVideoUrl' => config('event.recap_video_url'),
        ]);
    }

    public function about(): View
    {
        return view('pages.about', [
            'values' => config('event.values'),
        ]);
    }

    public function faq(): View
    {
        return view('pages.faq', [
            'faqGroups' => config('event.faqs'),
        ]);
    }

    public function privacy(): View
    {
        return view('pages.legal', [
            'title' => 'Privacy Policy',
            'description' => 'Bagaimana UC Color Run 2027 mengumpulkan, menggunakan, dan melindungi data pribadi peserta.',
            'items' => config('event.privacy'),
        ]);
    }
}
