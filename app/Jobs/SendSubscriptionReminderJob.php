<?php

namespace App\Jobs;

use App\Models\User;
use App\Telegram\Services\HandleChannel;
use Carbon\Carbon;
use DefStudio\Telegraph\Facades\Telegraph;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSubscriptionReminderJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::now();
        $threeDaysBefore = $today->copy()->addDays(3)->toDateString();
        $twelveDaysBefore = $today->copy()->addWeek()->toDateString();

        $users = User::whereDate('expire_date', $threeDaysBefore)
            ->orWhereDate('expire_date', $twelveDaysBefore)
            ->orWhere('expire_date', '<', $today->toDateString())
            ->get();

        foreach ($users as $user) {
            $daysLeft = $user->subscription_expires_at->diffInDays($today);

            if ($daysLeft > 0) {
                $message = "Assalomu alaykum, {$user->name}!\n\n".
                    "Eslatma: Sizning obunangiz $daysLeft kundan keyin tugaydi.\n".
                    "Obunani yangilashni unutmang, xizmatlarimizdan uzluksiz foydalanishingiz uchun :)";

                Telegraph::chat($user->chat_id)
                    ->message($message)
                    ->send();
            } else {
                $handleChannel = new HandleChannel($user);
                $handleChannel->kickUser();
            }
        }
    }
}
