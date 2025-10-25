<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Periods;
use App\Observers\PeriodsObserver;

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
        Periods::observe(PeriodsObserver::class);
    }
}
