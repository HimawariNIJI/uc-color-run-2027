<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\User;
use App\Support\NumberFormat;
use App\Support\RegistrationFilters;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Ranges offered by the Registration Trend dropdown, in days.
     */
    public const TREND_RANGES = [7, 30, 90];

    private const RECENT_REGISTRATIONS = 5;

    public function __invoke(Request $request): View
    {
        $filters = RegistrationFilters::fromRequest($request);
        $trendRange = in_array($request->integer('range'), self::TREND_RANGES, true) ? $request->integer('range') : self::TREND_RANGES[0];

        $statusCounts = Registration::query()->toBase()
            ->selectRaw('payment_status, count(*) as total')
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status')
            ->map(fn (mixed $total): int => (int) $total);

        $totalUsers = User::query()->count();
        $totalRegistrations = $statusCounts->sum();
        $paidRegistrations = $statusCounts->get(PaymentStatus::Paid->value, 0);
        $bibsIssued = Registration::query()->bibCollected()->count();

        return view('admin.dashboard', [
            'stats' => [
                ['label' => 'Total Users', 'value' => $totalUsers, 'caption' => 'Accounts that have logged in', 'icon' => 'users'],
                ['label' => 'Total Registrations', 'value' => $totalRegistrations, 'caption' => NumberFormat::percentage($totalRegistrations, $totalUsers).' of logged-in users', 'icon' => 'clipboard-list'],
                ['label' => 'Paid Registrations', 'value' => $paidRegistrations, 'caption' => NumberFormat::percentage($paidRegistrations, $totalRegistrations).' of registrations', 'icon' => 'wallet'],
                ['label' => 'BIB Issued', 'value' => $bibsIssued, 'caption' => NumberFormat::integer(max(0, $paidRegistrations - $bibsIssued)).' paid registrations awaiting BIB', 'icon' => 'ticket'],
            ],
            'registrationsByCategory' => $this->registrationsByCategory(),
            'paymentOverview' => collect([PaymentStatus::Paid, PaymentStatus::Unpaid, PaymentStatus::Failed])
                ->map(fn (PaymentStatus $status): array => [
                    'label' => $status->label(),
                    'value' => $statusCounts->get($status->value, 0),
                    'tone' => $status->tone(),
                ])->all(),
            'trend' => $this->registrationTrend($trendRange),
            'trendRange' => $trendRange,
            'trendRanges' => self::TREND_RANGES,
            'recentRegistrations' => $filters->apply(Registration::query())->latest()->latest('id')->limit(self::RECENT_REGISTRATIONS)->get(),
            'filters' => $filters,
            'statusOptions' => collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => $status->label()])->all(),
        ]);
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function registrationsByCategory(): array
    {
        $counts = Registration::query()->toBase()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        return collect(RaceCategory::cases())
            ->map(fn (RaceCategory $category): array => ['label' => $category->label(), 'value' => (int) $counts->get($category->value, 0)])
            ->all();
    }

    /**
     * New registrations per day, including days without any.
     *
     * @return Collection<int, array{date: CarbonImmutable, value: int}>
     */
    private function registrationTrend(int $days): Collection
    {
        $start = CarbonImmutable::today()->subDays($days - 1);

        $daily = Registration::query()->toBase()
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(CarbonPeriod::create($start, CarbonImmutable::today())->toArray())
            ->map(fn (mixed $day): array => [
                'date' => CarbonImmutable::instance($day),
                'value' => (int) $daily->get($day->toDateString(), 0),
            ])
            ->values();
    }
}
