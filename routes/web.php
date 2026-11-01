<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Registration Routes (Image 1 Wireframe)
Route::get('/registration', [RegistrationController::class, 'create'])->name('registration.create');
Route::post('/registration', [RegistrationController::class, 'store'])->name('registration.store');
Route::get('/registration/{id}/edit', [RegistrationController::class, 'edit'])->name('registration.edit');
Route::put('/registration/{id}', [RegistrationController::class, 'update'])->name('registration.update');
Route::redirect('/register', '/registration');

// My Registration & BIB Routes (Image 2 & 3 Wireframes)
Route::get('/my-registration', [RegistrationController::class, 'myRegistration'])->name('my-registration');
Route::get('/my-bib', [RegistrationController::class, 'myBib'])->name('my-bib');

// Midtrans QRIS Payment Gateway Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/create-order', [PaymentController::class, 'createOrder'])->name('create');
    Route::get('/check-status', [PaymentController::class, 'checkStatus'])->name('status');
    Route::post('/mock-pay', [PaymentController::class, 'mockPay'])->name('mock-pay');
    Route::post('/webhook', [PaymentController::class, 'webhook'])->name('webhook');
});
