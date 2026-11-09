<?php

namespace App\Http\Controllers;

use App\Enums\FeePeriod;
use App\Models\TicketPrice;
use App\Support\EventSchedule;
use App\Support\YouTube;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        $openPackages = TicketPrice::catalogue()->filter->isOpen();

        return view('pages.home', [
            'raceDay' => EventSchedule::raceDay(),
            'venue' => config('event.venue'),
            'isEarlyBidOpen' => $openPackages->contains(fn (TicketPrice $package): bool => $package->price_type === FeePeriod::EarlyBird),
            'isAnyPackageOpen' => $openPackages->isNotEmpty(),
            'timeline' => EventSchedule::timeline(),
            'sponsors' => config('event.sponsors'),
            'bannerImage' => config('event.banner_image'),
            'recapVideoUrl' => YouTube::embedUrl(config('event.recap_video_url')),
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
