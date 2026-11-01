<?php

namespace App\Http\Controllers;

use App\Enums\FeePeriod;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Http\Requests\RegistrationRequest;
use App\Models\Registration;
use App\Models\User;
use App\Support\EarlyBirdQuota;
use App\Support\IndonesiaRegions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Registration steps: type → price type (Early Bird / Normal Price) → distance → participant form.
 */
class RegistrationController extends Controller
{
    public function chooseType(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAlreadyRegistered($request->user())) {
            return $redirect;
        }

        return view('registration.choose-type', [
            'registrationTypes' => RegistrationType::cases(),
        ]);
    }

    public function choosePriceType(Request $request, RegistrationType $type): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAlreadyRegistered($request->user())) {
            return $redirect;
        }

        $isEarlyBirdAvailable = EarlyBirdQuota::isAvailableForAny();

        return view('registration.choose-price', [
            'registrationType' => $type,
            'priceTypes' => collect(FeePeriod::cases())->map(fn (FeePeriod $period): array => [
                'period' => $period,
                'fees' => collect(RaceCategory::cases())->map(fn (RaceCategory $category): int => $category->feeFor($type, $period)),
                'isAvailable' => $period !== FeePeriod::EarlyBird || $isEarlyBirdAvailable,
            ]),
        ]);
    }

    public function chooseCategory(Request $request, RegistrationType $type, FeePeriod $priceType): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAlreadyRegistered($request->user())) {
            return $redirect;
        }

        $slotsLeft = $priceType === FeePeriod::EarlyBird ? EarlyBirdQuota::remaining() : null;

        if ($slotsLeft !== null && array_sum($slotsLeft) === 0) {
            return to_route('registration.price', $type)
                ->with('error', 'Slot Early Bird sudah habis untuk semua jarak lari. Silakan pilih Normal Price.');
        }

        return view('registration.choose-category', [
            'registrationType' => $type,
            'priceType' => $priceType,
            'categories' => RaceCategory::cases(),
            'slotsLeft' => $slotsLeft,
        ]);
    }

    public function create(Request $request, RegistrationType $type, FeePeriod $priceType, RaceCategory $category): View|RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->redirectIfAlreadyRegistered($user)) {
            return $redirect;
        }

        if ($priceType === FeePeriod::EarlyBird && ! EarlyBirdQuota::isAvailable($category)) {
            return to_route('registration.category', [$type, $priceType->slug()])
                ->with('error', "Slot Early Bird untuk {$category->distance()} sudah habis. Pilih jarak lain atau Normal Price.");
        }

        return view('registration.form', [
            'registration' => new Registration([
                'type' => $type,
                'category' => $category,
                'full_name' => $user->name,
                'email' => $user->email,
            ]),
            'amount' => $category->feeFor($type, $priceType),
            'feePeriod' => $priceType,
            'regions' => IndonesiaRegions::all(),
            'action' => route('registration.store', [$type, $priceType->slug(), $category]),
            'method' => 'POST',
        ]);
    }

    public function store(RegistrationRequest $request, RegistrationType $type, FeePeriod $priceType, RaceCategory $category): RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->redirectIfAlreadyRegistered($user)) {
            return $redirect;
        }

        $register = fn (): Registration => $user->registration()->create([
            ...$request->safe()->except('agreement'),
            'type' => $type,
            'category' => $category,
            'email' => $user->email,
            'amount' => $category->feeFor($type, $priceType),
            'fee_period' => $priceType,
        ]);

        $registration = $priceType === FeePeriod::EarlyBird
            ? EarlyBirdQuota::whileLocked($category, fn (): ?Registration => EarlyBirdQuota::isAvailable($category) ? $register() : null)
            : $register();

        // The last Early Bird slot was taken while the participant filled the form.
        if ($registration === null) {
            return to_route('registration.create', [$type, FeePeriod::Regular->slug(), $category])
                ->withInput($request->except('agreement'))
                ->with('error', "Maaf, slot Early Bird untuk {$category->distance()} baru saja habis. Datamu masih tersimpan di formulir ini dengan Normal Price. Periksa kembali lalu kirim ulang.");
        }

        $registration->openPaymentWindow();

        return to_route('payment.show')
            ->with('success', 'Registrasi berhasil dikirim. Selesaikan pembayaran untuk mengamankan slotmu.');
    }

    private function redirectIfAlreadyRegistered(?User $user): ?RedirectResponse
    {
        if ($user?->registration === null) {
            return null;
        }

        return to_route('my-registration.show')
            ->with('info', 'Kamu sudah memiliki registrasi UC Color Run 2027.');
    }
}
