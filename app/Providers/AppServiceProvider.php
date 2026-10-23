<?php

namespace App\Providers;

use App\Models\Cinema;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        View::composer([
            'dashboard',
            'layouts.cinema',
            'layouts.guest',
            'movies.catalog',
            'movies.index',
            'profile.edit',
        ], function (ViewInstance $view): void {
            $view->with('cinema', Cinema::query()->first());
        });

        Schema::defaultStringLength(191);
    }
}
