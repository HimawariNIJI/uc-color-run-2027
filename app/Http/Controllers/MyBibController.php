<?php

namespace App\Http\Controllers;

use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyBibController extends Controller
{
    public function show(Request $request): View
    {
        $registration = $request->user()->registration;
        $hasBib = $registration?->isPaid() ?? false;

        return view('bib.show', [
            'registration' => $registration,
            'hasBib' => $hasBib,
            'racepackQrSvg' => $hasBib ? QrCode::svg($registration->racepackPayload()) : null,
        ]);
    }
}
