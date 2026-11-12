<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The participant's BIB numbers and racepack QR, available once the payment is confirmed.
 */
class MyBibController extends Controller
{
    public function show(Request $request): View
    {
        $registration = $request->user()->registration?->load('members');

        return view('bib.show', [
            'registration' => $registration,
            'hasBib' => $registration?->isPaid() ?? false,
        ]);
    }
}
