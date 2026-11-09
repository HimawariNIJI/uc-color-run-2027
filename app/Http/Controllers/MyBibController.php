<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The participant's BIB numbers. The racepack pickup QR is not built yet, so the page shows a
 * "coming soon" placeholder in its place.
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
