<?php

namespace App\Telegram\Services;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Support\Facades\Log;
use PayzeIO\LaravelPayze\Facades\Payze;

class PayzePaymentService
{
    /**
     * Create a one-time payment order and return the Payze payment URL.
     * The user can be redirected OR you can send this link via Telegram.
     */
    public function createOneTimePayment(User $user, Plan $plan, string $currency = 'USD'): string
    {
        // Example description for the Payze checkout page
        $description = "Payment for subscription (User: {$user->phone_number})";

        // The route below is a webhook/callback route you set up in your app
        $callbackUrl = route('payze.callback');

        // Create the order
        $orderResponse = Order::create([
        ]);

        // Store the order ID in your DB if needed
        // $orderResponse->order_id
        // Example: you might store it in a "payments" or "orders" table
        // Payment::create([...]);

        // Return the URL that the user should open
        return $orderResponse->redirect_url;
    }


    /**
     * Create an order in "preauthorize" mode to tokenize (save) the user’s card.
     * The user’s card token is available AFTER they fill out Payze’s form
     * and Payze calls your callback with "preauthorize" details.
     */
    public function saveCard(User $user, string $currency = 'USD'): string
    {
        $description = "Saving card on Payze (User: {$user->phone_number})";
        $callbackUrl = route('payze.callback');

        // "preauthorize" => true means Payze will not capture the amount
        // but will store the card data and return a card token.
        // You might want to set "amount" to a small value, e.g. 1.00, just for tokenization checks.
        $orderResponse = Payze::createOrder(
            amount: 1.00,
            currency: $currency,
            callbackUrl: $callbackUrl,
            preauthorize: true,
            description: $description,
        );

        // You can store that order to track if the user finishes the card-saving flow
        // or handle everything in your callback. For now, just return the redirect URL:
        return $orderResponse->redirect_url;
    }


    /**
     * Charge the user automatically if their card is saved (tokenized).
     * In practice, you might add validations (e.g., check if the user has a valid card).
     */
    public function chargeSavedCard(User $user, float $amount, string $currency = 'USD'): bool
    {
        // Retrieve user’s card token from DB
        $paymentMethod = $user->paymentMethods()->first(); // or filter by "active" method
        if (!$paymentMethod || !$paymentMethod->card_token) {
            // No saved card token, can’t charge
            return false;
        }

        // Attempt to charge the saved card
        $chargeResponse = Payze::chargeWithToken(
            cardToken: $paymentMethod->card_token,
            amount: $amount,
            currency: $currency
        );

        // Check $chargeResponse to see if it succeeded
        // Possibly update your DB with status
        if (isset($chargeResponse->orderStatus) && $chargeResponse->orderStatus === 'Charged') {
            // Payment success
            return true;
        }

        return false;
    }


    /**
     * This method can be called by your callback controller AFTER Payze calls your callback URL,
     * letting you finalize or cancel preauthorized payments, save card tokens, etc.
     *
     * The payload from Payze typically includes:
     * - order_id
     * - status
     * - card_token (if preauthorized)
     * - ...
     */
    public function handlePayzeCallback(array $payload): void
    {
        if ($payload['status'] === 'Charged') {
            $user = User::where('payze_customer_id', $payload['customer_id'])->first();

            if ($user) {
                $this->handleSuccessfulOneTimePayment($user, $payload['amount'], $payload['currency']);
            } else {
                Log::log('user not found');

            }
        } else {
            Log::log('Not successful payment');
        }
    }

    public function handleSuccessfulOneTimePayment(User $user, float $amount, string $currency = 'UZS'): void
    {
        $expiresAt = Carbon::now()->addMonths(1); // Example: 1 month subscription

        Subscription::create([
            'user_id' => $user->id,
            'expires_at' => $expiresAt,
            'status' => 'active',
            'order_id' => 1
        ]);

        $channelLink = env('TELEGRAM_CHANNEL_LINK');
        Telegraph::chat($user->chat_id)
            ->message("Thank you for subscribing! Here's the link to our channel: $channelLink")
            ->send();
    }

}
