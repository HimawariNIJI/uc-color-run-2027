<?php

use App\Http\Controllers\RacepackController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'uc-color-run.category')->name('category');

Route::view('/schedule', 'uc-color-run.schedule')->name('schedule');

Route::get('/regulation', function () {
    $documents = [
        'regulation' => 'Race Regulation',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms & Conditions',
    ];

    $document = request()->query('doc', 'regulation');
    $document = array_key_exists($document, $documents) ? $document : 'regulation';

    return view('uc-color-run.regulation', [
        'pageTitle' => $documents[$document],
        'paragraphs' => array_fill(0, 6, config('uc-color-run.lorem')),
    ]);
})->name('regulation');

// Racepack scanner (panitia).
// TODO: pindahkan ke dalam group middleware 'auth' setelah Admin Login selesai.
Route::get('/racepack-scanner', [RacepackController::class, 'index'])->name('racepack.scanner');
Route::get('/racepack/recent', [RacepackController::class, 'recent'])->name('racepack.recent');
Route::post('/racepack/scan', [RacepackController::class, 'scan'])
    ->middleware('throttle:120,1')
    ->name('racepack.scan');
