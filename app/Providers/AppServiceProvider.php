<?php

namespace App\Providers;

use App\Jobs\Interfaces\SubscriptionNotifier;
use App\Jobs\services\TelegramSubscriptionNotifier;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
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
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->visible(outsidePanels: true)
                ->locales(['uz','ru', 'en']); // also accepts a closure
        });
    }
}
