<?php

namespace App\Telegram\Traits;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;

/**
 * Trait for handling subscription plans and card input flow.
 */
trait HasPlans
{
    /**
     * Show the available subscription plans.
     *
     * @return void
     */
    private function sendPlans(): void
    {
        // Create inline buttons with plan parameters
        Telegraph::chat($this->chat_id())
            ->message('Obuna muddatini tanlang')
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make('1-hafta bepul')->action('savePlan')->param('plan', 'one-week-free')->width(0.3),
                    Button::make('1 oy')->action('savePlan')->param('plan', 'one-month')->width(0.3),
                    Button::make('2 oy')->action('savePlan')->param('plan', 'two-months')->width(0.3),
                    Button::make('6 oy')->action('savePlan')->param('plan', 'six-months')->width(0.5),
                    Button::make('1 yil')->action('savePlan')->param('plan', 'one-year')->width(0.5),
                ])
            )->send();
    }
}
