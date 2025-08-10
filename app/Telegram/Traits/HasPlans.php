<?php

namespace App\Telegram\Traits;

use App\Enums\ConversationStates;
use App\Models\Client;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use DefStudio\Telegraph\Keyboard\ReplyButton;
use DefStudio\Telegraph\Keyboard\ReplyKeyboard;
use Illuminate\Support\Facades\Cache;
use JetBrains\PhpStorm\NoReturn;

/**
 * Trait for handling subscription plans and card input flow.
 */
trait HasPlans
{
    #[NoReturn] public function processCardDetails(Client $client, string $card): void
    {
        $card = str_replace(' ', '', $card);
        $rules = '/^\d{16}$/';
        if (empty($card) || !preg_match($rules, $card)) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.invalid_card_number'))
                ->send();
        } else {
            if (!Cache::has($this->chat->chat_id . "card")) {
                Cache::put($this->chat->chat_id . "card", $card, now()->addMinutes(10));
            }
            $this->setState($client, ConversationStates::waiting_card_expire);
            Telegraph::chat($client->chat_id)->message(__('telegram.ask_for_card_expiry'))
                ->replyKeyboard(ReplyKeyboard::make()
                    ->row([
                        ReplyButton::make(__('telegram.help_button')),
                        ReplyButton::make(__('telegram.home_button')),
                    ])->chunk(2)
                    ->row([
                        ReplyButton::make(__('telegram.change_language_button')),
                    ])->chunk(1)
                    ->resize()
                )->send();
        }
        return;
    }

    #[NoReturn] public function processCardExpire(Client $client, string $expire): void
    {
        $card = Cache::get($this->chat->chat_id . "card");
        if (empty($card)) {
            $this->askForCardDetails($client);
        }
        $expire = trim($expire);
        $rules = '/^(0[1-9]|1[0-2])\/\d{2}$/';
        if (empty($expire) || !preg_match($rules, $expire)) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.invalid_expiry_date'))
                ->send();
            return;
        }
        list($month, $year) = explode('/', $expire);
        $month = (int)$month;
        $year = (int)('20' . $year);
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.card_expired'))
                ->send();
            return;
        }

        list($month, $year) = explode('/', $expire);
        $expire = $month . $year;
        $success = $this->callCreateCard($card, $expire, $client);
        
        // If card creation failed, the PaycomSubscriptionService will handle asking for card again
        // We just need to clear the cache
        if (!$success) {
            Cache::forget($this->chat->chat_id . "card");
        }
        return;
    }

    #[NoReturn] public function askForCardDetails(Client $client): void
    {
        Telegraph::chat($this->chat->chat_id)->deleteMessage($this->messageId)->send();
        $this->setState($client, ConversationStates::waiting_card);
        Telegraph::chat($this->chat->chat_id)->message(__('telegram.ask_for_card_number'))
            ->replyKeyboard(ReplyKeyboard::make()
                ->row([
                    ReplyButton::make(__('telegram.help_button')),
                    ReplyButton::make(__('telegram.home_button')),
                ])->chunk(2)
                ->row([
                    ReplyButton::make(__('telegram.change_language_button')),
                ])->chunk(1)
                ->resize()
            )->send();
        return;
    }

    #[NoReturn] public function processVerificationCode(Client $client, string $code): void
    {
        $card = $client->cards()->latest()->first();
        if (empty($card)) {
            $this->askForCardDetails($client);
        }
        $verified = $this->callVerifyCard($client, $code, $card);
        Cache::forget($this->chat->chat_id . "card");
        if ($verified) {
            // Check if user had selected a plan before
            $selectedPlanId = cache()->get("selected_plan_{$client->id}");
            if ($selectedPlanId) {
                cache()->forget("selected_plan_{$client->id}");
                // Trigger plan selection with the previously selected plan
                $plan = \App\Models\Plan::find($selectedPlanId);
                if ($plan) {
                    $this->savePlan($plan->name);
                    return;
                }
            }
            $this->sendPlans();
        } else {
            $this->askForCardDetails($client);
        }
        return;
    }

    private function sendPlans(): void
    {
        Telegraph::chat($this->chat->chat_id)
            ->message(__('telegram.select_plan_duration'))
            ->keyboard(
                Keyboard::make()->buttons([
                    Button::make(__('telegram.one_week_free'))->action('savePlan')->param('plan', 'one-week-free')->width(0.3),
                    Button::make(__('telegram.one_month'))->action('savePlan')->param('plan', 'one-month')->width(0.3),
                    Button::make(__('telegram.two_months'))->action('savePlan')->param('plan', 'two-months')->width(0.3),
                    Button::make(__('telegram.six_months'))->action('savePlan')->param('plan', 'six-months')->width(0.5),
                    Button::make(__('telegram.one_year'))->action('savePlan')->param('plan', 'one-year')->width(0.5),
                ])
            )
            ->send();
    }

    /**
     * Show plans for selection
     */
    public function showPlans(): void
    {
        $this->sendPlans();
    }

    /**
     * Handle immediate subscription renewal
     */
    public function renewSubscriptionNow(): void
    {
        $subscriptionId = $this->data->get('subscription_id');
        $subscription = \App\Models\Subscription::find($subscriptionId);
        
        if (!$subscription || !$subscription->canRenewEarly()) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.renewal_not_available'))
                ->send();
            return;
        }

        $client = $subscription->client;
        app()->setLocale($client->lang ?? 'uz');

        // Check if user has cards
        $cards = $client->cards()
            ->where('verified', true)
            ->orderBy('is_main', 'desc')
            ->get();

        if ($cards->isEmpty()) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.no_cards_for_renewal'))
                ->keyboard(
                    Keyboard::make()->buttons([
                        Button::make(__('telegram.add_card_button'))
                            ->action('addCardForRenewal')
                            ->param('subscription_id', $subscription->id)
                    ])
                )
                ->send();
            return;
        }

        // Attempt renewal with all cards
        $service = app(\App\Services\SubscriptionService::class);
        $renewed = false;
        $lastError = null;

        foreach ($cards as $card) {
            try {
                $newSubscription = $service->renewSubscription($subscription, $card);
                if ($newSubscription) {
                    $renewed = true;
                    Telegraph::chat($this->chat->chat_id)
                        ->message(__('telegram.subscription_renewed_success', [
                            'plan' => $newSubscription->plan->name,
                            'expires_at' => $newSubscription->expires_at->format('d.m.Y')
                        ]))
                        ->replyKeyboard($this->getDefaultKeyboard())
                        ->send();
                    break;
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }
        }

        if (!$renewed) {
            Telegraph::chat($this->chat->chat_id)
                ->message(__('telegram.renewal_payment_failed'))
                ->keyboard(
                    Keyboard::make()->buttons([
                        Button::make(__('telegram.add_new_card_button'))
                            ->action('addCardForRenewal')
                            ->param('subscription_id', $subscription->id),
                        Button::make(__('telegram.retry_button'))
                            ->action('renewSubscriptionNow')
                            ->param('subscription_id', $subscription->id)
                    ])
                )
                ->send();
        }
    }

    /**
     * Add card for renewal
     */
    public function addCardForRenewal(): void
    {
        $subscriptionId = $this->data->get('subscription_id');
        $client = $this->getCreateClient();
        
        // Store subscription ID for later use
        cache()->put("renewal_subscription_{$client->id}", $subscriptionId, now()->addHours(1));
        
        $this->askForCardDetails($client);
    }
}
