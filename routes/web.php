<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\MyBibController;
use App\Http\Controllers\MyRegistrationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentSimulationController;
use App\Http\Controllers\RaceInfoController;
use App\Http\Controllers\RegistrationController;
use App\Http\Middleware\EnsureRegistrationIsPayable;
use App\Http\Middleware\EnsureUserHasRegistration;
use App\Models\Registration;
use Illuminate\Support\Facades\Route;

Route::controller(PageController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/about', 'about')->name('about');
    Route::get('/faq', 'faq')->name('faq');
    Route::get('/privacy-policy', 'privacy')->name('privacy');
});

Route::controller(RaceInfoController::class)->prefix('race-info')->name('race.')->group(function () {
    Route::get('/category', 'category')->name('category');
    Route::get('/schedule', 'schedule')->name('schedule');
    Route::get('/regulation', 'regulation')->name('regulation');
});

Route::controller(ContactController::class)->group(function () {
    Route::get('/contact', 'create')->name('contact');
    // Only participants signed in with Google may send a message; guests are sent to the sign-in page.
    Route::post('/contact', 'store')->middleware(['auth', 'throttle:5,1'])->name('contact.store');
});

// Called by Midtrans, not by browsers: no session or CSRF token, so every request is verified by
// its signature and the status is fetched from Midtrans again (see MidtransNotificationController).
Route::post('/payments/midtrans/notification', MidtransNotificationController::class)
    ->middleware('throttle:120,1')
    ->name('payments.midtrans.notification');

Route::middleware('guest')->group(function () {
    Route::get('/sign-in', [SessionController::class, 'create'])->name('login');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Participants sign in first, so the type step only offers what their Google email is eligible for.
// Every step after it re-checks the type in the URL on the server (RegistrationPolicy::create).
Route::controller(RegistrationController::class)->prefix('registration')->name('registration.')->middleware('auth')->group(function () {
    Route::get('/', 'chooseType')->name('type');

    // Bundles are open to every participant and have no price type, so they have their own form.
    Route::get('/bundling/{bundle}', 'createBundle')->whereIn('bundle', ['family', 'couple'])->name('bundle.create');
    Route::post('/bundling/{bundle}', 'storeBundle')->whereIn('bundle', ['family', 'couple'])->name('bundle.store');

    Route::middleware('can:create,'.Registration::class.',type')->group(function () {
        Route::get('/{type}', 'choosePriceType')->name('price');
        Route::get('/{type}/{priceType}', 'chooseCategory')->name('category');
        Route::get('/{type}/{priceType}/{category}', 'create')->name('create');
        Route::post('/{type}/{priceType}/{category}', 'store')->name('store');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/sign-out', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/my-registration', [MyRegistrationController::class, 'show'])->name('my-registration.show');
    Route::get('/my-bib', [MyBibController::class, 'show'])->name('my-bib.show');

    Route::middleware(EnsureUserHasRegistration::class)->group(function () {
        Route::get('/my-registration/edit', [MyRegistrationController::class, 'edit'])->name('my-registration.edit');
        Route::put('/my-registration', [MyRegistrationController::class, 'update'])->name('my-registration.update');

        Route::controller(PaymentController::class)->prefix('payment')->name('payment.')->group(function () {
            // No QRIS payment while the package has no price in the database.
            Route::middleware(EnsureRegistrationIsPayable::class)->group(function () {
                Route::get('/', 'show')->name('show');
                Route::post('/check', 'check')->name('check');
                Route::post('/retry', 'retry')->name('retry');
            });
            Route::get('/status', 'status')->middleware('throttle:30,1')->name('status');
            Route::get('/success', 'success')->name('success');
            Route::get('/failed', 'failed')->name('failed');
        });

        Route::post('/payment/simulate/{outcome}', PaymentSimulationController::class)
            ->middleware(EnsureRegistrationIsPayable::class)
            ->whereIn('outcome', ['paid', 'failed'])
            ->name('payment.simulate');
    });
});
