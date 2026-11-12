<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Support\IdentityPhoto;
use App\Support\IndonesiaRegions;
use App\Support\QrisCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class MyRegistrationController extends Controller
{
    /**
     * An expired or lost QRIS is verified with Midtrans before the page decides between "Pay Now"
     * (a payment that can still be finished), "Pay Again" (a new attempt for the same package
     * while it has a free slot) or the reason why paying again is not possible.
     */
    public function show(Request $request): View
    {
        $registration = $request->user()->registration;

        if ($registration !== null && ! $registration->isPaid()) {
            QrisCheckout::latestPayment($registration);
        }

        return view('registration.show', [
            'registration' => $registration,
            'retryBlockedReason' => $registration === null ? null : QrisCheckout::retryBlockedReason($registration),
        ]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $registration = $request->user()->registration;

        if (! $registration->isEditable()) {
            return to_route('my-registration.show')
                ->with('error', 'Registrasi yang sudah lunas tidak dapat diubah. Hubungi panitia untuk bantuan.');
        }

        return view('registration.form', [
            'registration' => $registration,
            'package' => $registration->package(),
            'regions' => IndonesiaRegions::all(),
            'action' => route('my-registration.update'),
            'method' => 'PUT',
        ]);
    }

    /**
     * A new identity photo (KTP, or Kartu Keluarga for a Family Bundle) replaces the saved one.
     * The old file is removed only after the registration points to the new one, and the new file
     * is removed again if saving fails.
     */
    public function update(RegistrationRequest $request): RedirectResponse
    {
        $registration = $request->user()->registration;

        if (! $registration->isEditable()) {
            return to_route('my-registration.show')
                ->with('error', 'Registrasi yang sudah lunas tidak dapat diubah. Hubungi panitia untuk bantuan.');
        }

        $document = $request->identityDocument();
        $previousPhotoPath = $registration->identityPhotoPath();
        $newPhotoPath = $request->identityPhoto() ? IdentityPhoto::store($request->identityPhoto(), $document) : null;

        try {
            $registration->update([
                ...$request->participant(),
                ...($newPhotoPath !== null ? [$document->column() => $newPhotoPath] : []),
            ]);
        } catch (Throwable $exception) {
            IdentityPhoto::delete($newPhotoPath);

            throw $exception;
        }

        if ($newPhotoPath !== null) {
            IdentityPhoto::delete($previousPhotoPath);
        }

        return to_route('my-registration.show')->with('success', 'Data registrasi berhasil diperbarui.');
    }
}
