<?php

namespace App\Http\Controllers;

use App\Enums\FeePeriod;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Exceptions\BibNumbersExhausted;
use App\Http\Requests\BundleRegistrationRequest;
use App\Http\Requests\RegistrationRequest;
use App\Models\Registration;
use App\Models\TicketPrice;
use App\Models\User;
use App\Support\BibNumber;
use App\Support\IdentityPhoto;
use App\Support\IndonesiaRegions;
use App\Support\QrisCheckout;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Registration steps for the official packages in ticket_prices:
 * type → price type (Early Bid / Normal Bid) → distance → participant form, or Bundling → bundle form.
 * Submitting a valid form creates the registration and its Midtrans QRIS straight away.
 */
class RegistrationController extends Controller
{
    /**
     * Internal is only listed for participants signed in with a verified Universitas Ciputra email.
     */
    public function chooseType(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->redirectIfAlreadyRegistered($user)) {
            return $redirect;
        }

        return view('registration.choose-type', [
            'registrationTypes' => $user->registrationTypes(),
            'isEligibleForInternal' => $user->isEligibleForInternal(),
            'email' => $user->email,
        ]);
    }

    /**
     * Individual types choose Early Bid or Normal Bid; Bundling lists the bundle packages.
     */
    public function choosePriceType(Request $request, RegistrationType $type): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAlreadyRegistered($request->user())) {
            return $redirect;
        }

        $packages = TicketPrice::catalogue()->filter(fn (TicketPrice $package): bool => $package->registration_type === $type);

        if ($type === RegistrationType::Bundling) {
            return view('registration.choose-bundle', [
                'registrationType' => $type,
                'bundles' => $packages->values(),
            ]);
        }

        return view('registration.choose-price', [
            'registrationType' => $type,
            'priceTypes' => collect(FeePeriod::cases())->map(fn (FeePeriod $period): array => [
                'period' => $period,
                'packages' => $packages->filter(fn (TicketPrice $package): bool => $package->price_type === $period)->values(),
            ]),
        ]);
    }

    public function chooseCategory(Request $request, RegistrationType $type, FeePeriod $priceType): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAlreadyRegistered($request->user())) {
            return $redirect;
        }

        $packages = TicketPrice::catalogue()
            ->filter(fn (TicketPrice $package): bool => $package->registration_type === $type && $package->price_type === $priceType)
            ->values();

        // Bundles have no price type, so there is no such page for them.
        abort_if($packages->isEmpty(), 404);

        if ($packages->every->isSoldOut()) {
            return to_route('registration.price', $type)
                ->with('error', "Kuota {$priceType->label()} {$type->label()} sudah habis untuk semua jarak lari. Silakan pilih tipe harga lain.");
        }

        return view('registration.choose-category', [
            'registrationType' => $type,
            'priceType' => $priceType,
            'packages' => $packages,
        ]);
    }

    public function create(Request $request, RegistrationType $type, FeePeriod $priceType, RaceCategory $category): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAlreadyRegistered($request->user())) {
            return $redirect;
        }

        $package = TicketPrice::findOption($type, $priceType, $category) ?? abort(404);

        if (! $package->isOpen()) {
            return to_route('registration.category', [$type, $priceType->slug()])->with('error', $package->unavailableMessage());
        }

        return $this->form($request->user(), $package, route('registration.store', [$type, $priceType->slug(), $category]));
    }

    public function store(RegistrationRequest $request, RegistrationType $type, FeePeriod $priceType, RaceCategory $category): RedirectResponse
    {
        $package = TicketPrice::findOption($type, $priceType, $category) ?? abort(404);

        return $this->register($request, $package, $request->participant());
    }

    public function createBundle(Request $request, string $bundle): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfAlreadyRegistered($request->user())) {
            return $redirect;
        }

        $package = TicketPrice::findBundle($bundle) ?? abort(404);

        if (! $package->isOpen()) {
            return to_route('registration.price', RegistrationType::Bundling)->with('error', $package->unavailableMessage());
        }

        return $this->form($request->user(), $package, route('registration.bundle.store', $bundle));
    }

    public function storeBundle(BundleRegistrationRequest $request, string $bundle): RedirectResponse
    {
        $package = TicketPrice::findBundle($bundle) ?? abort(404);

        return $this->register($request, $package, $request->participant(), $request->members());
    }

    private function form(User $user, TicketPrice $package, string $action): View
    {
        return view('registration.form', [
            'registration' => new Registration([
                'type' => $package->registration_type,
                'category' => $package->race_category,
                'full_name' => $user->name,
                'email' => $user->email,
            ]),
            'package' => $package,
            'regions' => IndonesiaRegions::all(),
            'action' => $action,
            'method' => 'POST',
        ]);
    }

    /**
     * Create the registration while the package row is locked, so simultaneous sign-ups can never
     * exceed its quota or receive the same code. The amount is the package's price in the
     * database and the email is the signed-in account's; nothing from the browser decides either.
     *
     * The registration code ("UCCR-052-0001") is given in the same transaction and never changes.
     * No BIB is handed out yet: BIBs follow the order in which payments are confirmed (see
     * Registration::markAsPaid). Registering is refused up front when the package's BIB numbers
     * could no longer cover every participant.
     *
     * The identity photo (KTP, or Kartu Keluarga for a Family Bundle) is saved first, so the lock
     * is not held while the file is written, and removed again whenever the registration is not created.
     *
     * @param  array<string, mixed>  $participant  validated account holder fields
     * @param  list<array<string, mixed>>  $members  validated bundle members
     */
    private function register(RegistrationRequest $request, TicketPrice $package, array $participant, array $members = []): RedirectResponse
    {
        $user = $request->user();

        if ($redirect = $this->redirectIfAlreadyRegistered($user)) {
            return $redirect;
        }

        $document = $request->identityDocument();
        $identityPhotoPath = IdentityPhoto::store($request->identityPhoto(), $document);

        try {
            $registration = DB::transaction(function () use ($package, $participant, $members, $user, $document, $identityPhotoPath): ?Registration {
                $locked = $package->lockForRegistration();

                if (! $locked->isOpen()) {
                    return null;
                }

                BibNumber::ensureAvailable($locked->bibPrefix(), count($members) + 1);

                $registration = $user->registration()->create([
                    ...$participant,
                    $document->column() => $identityPhotoPath,
                    'ticket_price_id' => $locked->getKey(),
                    'code' => BibNumber::nextRegistrationCode($locked->bibPrefix()),
                    'type' => $locked->registration_type,
                    'category' => $locked->race_category,
                    'fee_period' => $locked->price_type,
                    'email' => $user->email,
                    'amount' => $locked->price,
                ]);

                $registration->members()->createMany($members);
                $registration->openPaymentWindow();

                return $registration;
            });
        } catch (UniqueConstraintViolationException $exception) {
            IdentityPhoto::delete($identityPhotoPath);

            // A double submit: the first request already created this account's registration.
            if ($user->registration()->exists()) {
                return to_route('payment.show');
            }

            throw $exception;
        } catch (BibNumbersExhausted) {
            IdentityPhoto::delete($identityPhotoPath);

            return $this->packageChoice($package)
                ->with('error', "Nomor BIB untuk paket {$package->name()} ({$package->eligibility()}) sudah habis (maksimal 9999 peserta), jadi pendaftaran paket ini ditutup. Silakan pilih paket lain atau hubungi panitia.");
        } catch (Throwable $exception) {
            IdentityPhoto::delete($identityPhotoPath);

            throw $exception;
        }

        if ($registration === null) {
            IdentityPhoto::delete($identityPhotoPath);

            return $this->packageClosedDuringSignUp($request, $package);
        }

        if ($error = QrisCheckout::start($registration)) {
            return to_route('payment.failed')->with('error', $error);
        }

        return to_route('payment.show')
            ->with('success', 'Registrasi berhasil dikirim. Scan QRIS di bawah untuk menyelesaikan pembayaran.');
    }

    /**
     * The package closed while the participant filled the form, usually because its last slot was
     * taken. An Early Bid participant keeps their data on the Normal Bid form when that is open.
     */
    private function packageClosedDuringSignUp(RegistrationRequest $request, TicketPrice $package): RedirectResponse
    {
        $current = $package->fresh() ?? $package;
        $message = $current->unavailableMessage() ?? 'Paket ini sedang tidak tersedia.';

        $normalBid = $package->price_type === FeePeriod::EarlyBird && $current->isSoldOut()
            ? TicketPrice::findOption($package->registration_type, FeePeriod::Regular, $package->race_category)
            : null;

        if ($normalBid?->isOpen()) {
            return to_route('registration.create', [$package->registration_type, FeePeriod::Regular->slug(), $package->race_category])
                ->withInput($request->except('agreement'))
                ->with('error', "Maaf, kuota {$package->name()} {$package->registration_type->label()} baru saja habis. Datamu masih tersimpan di formulir ini dengan Normal Bid. Pilih ulang Foto KTP, periksa kembali, lalu kirim ulang.");
        }

        return $this->packageChoice($package)->with('error', $message);
    }

    /**
     * Back to the page where the package was chosen: the bundle list or the distances of its price type.
     */
    private function packageChoice(TicketPrice $package): RedirectResponse
    {
        return $package->isBundle()
            ? to_route('registration.price', RegistrationType::Bundling)
            : to_route('registration.category', [$package->registration_type, $package->price_type->slug()]);
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
