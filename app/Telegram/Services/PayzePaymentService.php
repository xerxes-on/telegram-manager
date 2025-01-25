<?php

namespace App\Telegram\Services;

use App\Models\User;
use App\Models\UserPaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PayzeIO\LaravelPayze\Facades\Payze;

class PayzePaymentService
{
    /**
     * Create a one-time payment order and return the Payze payment URL.
     * The user can be redirected OR you can send this link via Telegram.
     */
    public function createOneTimePayment(User $user, float $amount, string $currency = 'USD'): string
    {
        // Example description for the Payze checkout page
        $description = "Payment for subscription (User: {$user->phone_number})";

        // The route below is a webhook/callback route you set up in your app
        $callbackUrl = route('payze.callback');

        // Create the order
        $orderResponse = Payze::createOrder(
            amount: $amount,
            currency: $currency,
            callbackUrl: $callbackUrl,
            preauthorize: false, // false = immediate payment (not tokenizing the card here)
            description: $description,
        );

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
        // Inspect $payload
        // Example: $payload['status'], $payload['order_id'], $payload['cardToken']
        $status     = $payload['status'] ?? null;
        $orderId    = $payload['order_id'] ?? null;
        $cardToken  = $payload['cardToken'] ?? null;
        $cardLast4  = $payload['cardMasked'] ?? null;  // often masked like ****1111

        // If we were "preauthorizing", we might finalize or store the card token
        if ($status === 'Preauthorized' && $cardToken) {
            // Find the user/order from DB by $orderId
            // ...
            /** @var User $user **/
            $user = /* find user somehow */ null;

            if ($user) {
                // Save or update the user’s payment method with the token
                $paymentMethod = $user->paymentMethods()->firstOrCreate([
                    'user_id' => $user->id,
                ]);

                $paymentMethod->card_token  = $cardToken;
                $paymentMethod->card_last4  = $cardLast4;
                $paymentMethod->save();

                // (Optional) You may want to "capture" the preauthorized amount if needed
                // Payze::capturePreauthorization($orderId);
            }
        }

        // If $status === 'Charged', then it was a successful immediate payment
        // If $status === 'Cancelled', handle it, etc.
    }
}
