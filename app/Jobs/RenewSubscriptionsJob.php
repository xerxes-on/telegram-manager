<?php
// app/Jobs/RenewSubscriptionsJob.php

namespace App\Jobs;

use App\Models\Subscription;
use App\Telegram\Traits\CanUsePayme;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RenewSubscriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CanUsePayme;

    public function handle(): void
    {
        $now = Carbon::now();
        $expirationThreshold = $now->copy()->addHours(12);

        $subscriptions = Subscription::where('status', 1)
            ->whereBetween('expires_at', [$now, $expirationThreshold])
            ->get();

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            if (!$user) {
                continue;
            }

            $verifiedCard = $user->cards()->where('verified', true)->latest()->first();
            if (!$verifiedCard) {
                Telegraph::chat($user->chat_id)
                    ->message("Hurmatli {$user->name}, obunangizni avtomatik yangilash uchun tekshirilgan kartangiz topilmadi. Iltimos, kartangizni yangilang.")
                    ->send();
                continue;
            }

            try {
                $this->callRecurrentPay($subscription->plan, $subscription->user);
                Telegraph::chat($user->chat_id)
                    ->sticker("CAACAgIAAxkBAAExKjRnl0Nr7-7-U-Ita4YDc764z65TRwACiQADFkJrCkbL2losgrCONgQ")
                    ->send();
                Telegraph::chat($user->chat_id)
                    ->message("Hurmatli {$user->name}, obunangiz muvaffaqiyatli yangilandi.")
                    ->send();
            } catch (Exception $e) {
                Telegraph::chat($user->chat_id)
                    ->message("Hurmatli {$user->name}, obunangizni yangilashda xatolik yuz berdi. Iltimos, balansingizni yangilang 😇")
                    ->send();
            }
        }
    }
}
