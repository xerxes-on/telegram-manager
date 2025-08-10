<?php

namespace App\Providers;

use App\Jobs\Interfaces\SubscriptionNotifier;
use App\Jobs\services\TelegramSubscriptionNotifier;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind notifier interface to Telegram implementation
        $this->app->bind(SubscriptionNotifier::class, TelegramSubscriptionNotifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
