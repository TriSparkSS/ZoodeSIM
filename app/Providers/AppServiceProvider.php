<?php

namespace App\Providers;

use App\Services\Locale\LocaleManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocaleManager::class);
    }

    public function boot(): void
    {
        //
    }
}
