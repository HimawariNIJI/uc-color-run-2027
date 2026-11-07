<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/faq', function () {
    return view('faq');
});

// (Hardcode Dummy)
Route::get('/my-registration', function () {
    $registration = (object) [
        'payment_status' => 'Paid', //bs coba diganti 'Pending' / null
        'category' => '10K Run',
        'ticket_code' => 'UCRUN-2026-888',
        'created_at' => '2026-10-28 10:00:00',
        'id' => 'UCR-2027-XXXX',
        'name' => 'Your Full Name',
        'email' => 'yourname@gmail.com',
        'tshirt_size' => 'M'
    ];

    return view('myregistration', compact('registration'));
});


// (Hardcode Dummy)
Route::get('/my-bib', function () {
    $registration = (object)[
        'payment_status' => 'Paid', // Ganti 'pending' / null
        'id' => 1,
        'bib_number' => '01234',
    ];
    
    $bib = [
        'bib_number' => '01234',
        'category' => '10K Marathon',
        'runner_name' => 'Ethan Canavaro',
        'wave' => 'Wave 1 (05:30 AM)'
    ];

    return view('bib', compact('registration', 'bib'));
});

Route::get('/auth/google', [GoogleController::class, 'redirect'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
    ->name('google.callback');