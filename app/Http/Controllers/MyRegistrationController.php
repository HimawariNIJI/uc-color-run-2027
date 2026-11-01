<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Support\IndonesiaRegions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyRegistrationController extends Controller
{
    public function show(Request $request): View
    {
        return view('registration.show', [
            'registration' => $request->user()->registration,
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
            'amount' => $registration->amount,
            'feePeriod' => $registration->fee_period,
            'regions' => IndonesiaRegions::all(),
            'action' => route('my-registration.update'),
            'method' => 'PUT',
        ]);
    }

    public function update(RegistrationRequest $request): RedirectResponse
    {
        $registration = $request->user()->registration;

        if (! $registration->isEditable()) {
            return to_route('my-registration.show')
                ->with('error', 'Registrasi yang sudah lunas tidak dapat diubah. Hubungi panitia untuk bantuan.');
        }

        $registration->update($request->safe()->except('agreement'));

        return to_route('my-registration.show')->with('success', 'Data registrasi berhasil diperbarui.');
    }
}
