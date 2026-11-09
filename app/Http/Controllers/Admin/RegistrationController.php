<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Support\RegistrationFilters;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    private const PER_PAGE = 10;

    public function index(Request $request): View
    {
        $filters = RegistrationFilters::fromRequest($request);

        $registrations = $filters->apply(Registration::query())
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'filters' => $filters,
            'statusOptions' => collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $status): array => [$status->value => $status->label()])->all(),
            'categoryOptions' => collect(RaceCategory::cases())->mapWithKeys(fn (RaceCategory $category): array => [$category->value => $category->label()])->all(),
        ]);
    }

    /**
     * Registration details. Requests from the admin modal receive only the modal content.
     */
    public function show(Request $request, Registration $registration): View|string
    {
        $registration->load(['bibCollector:id,name', 'members']);

        // The racepack pickup QR is not built yet; the page shows a "coming soon" placeholder.
        return view('admin.registrations.show', [
            'registration' => $registration,
        ])->fragmentIf($request->hasHeader('X-Admin-Modal'), 'details');
    }
}
