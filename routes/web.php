<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

Route::get('/admin/user', [UserController::class, 'index'])->name('admin.user.index');
Route::get('/admin/user/export', [UserController::class, 'export'])->name('admin.user.export');

Route::get('/admin/registration', [RegistrationController::class, 'index',])->name('admin.registration.index');
Route::get('/admin/registrations/export', [RegistrationController::class, 'export',])->name('registrations.export');

Route::view('/admin/qr-scanner', 'admin.qr')->name('admin.qr-scanner');

Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');