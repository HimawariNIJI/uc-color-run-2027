<?php

use App\Http\Controllers\Admin\Auth\SessionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QrScannerController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\RegistrationExportController;
use App\Http\Controllers\Admin\RegistrationIdentityPhotoController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [SessionController::class, 'create'])->name('login');
        Route::post('/login', [SessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/export', UserExportController::class)->name('users.export');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

        Route::get('/registrations', [RegistrationController::class, 'index'])->name('registrations.index');
        Route::get('/registrations/export', RegistrationExportController::class)->name('registrations.export');
        Route::get('/registrations/{registration:code}', [RegistrationController::class, 'show'])->name('registrations.show');
        Route::get('/registrations/{registration:code}/identity-photo', RegistrationIdentityPhotoController::class)->name('registrations.identity-photo');

        Route::get('/qr-scanner', [QrScannerController::class, 'index'])->name('scanner.index');
        Route::post('/qr-scanner', [QrScannerController::class, 'store'])->middleware('throttle:60,1')->name('scanner.store');
        Route::get('/qr-scanner/scans/{scan}', [QrScannerController::class, 'show'])->name('scanner.show');
    });
});
