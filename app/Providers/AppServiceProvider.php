<?php

namespace App\Providers;

use App\Enums\FeePeriod;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registration URLs name the price type by slug, for example /registration/internal/early-bird.
        Route::bind('priceType', fn (string $value): FeePeriod => FeePeriod::fromSlug($value) ?? abort(404));
    }
}
