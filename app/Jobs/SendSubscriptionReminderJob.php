<?php

namespace App\Jobs;

use App\Models\User;
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
        // Get the current date and calculate reminder dates
        $today = Carbon::now();
        $threeDaysBefore = $today->copy()->addDays(3)->toDateString();
        $twelveDaysBefore = $today->copy()->addDays(12)->toDateString();

        $users = User::whereDate('expire_date', $threeDaysBefore)
            ->orWhereDate('expire_date', $twelveDaysBefore)
            ->get();

        foreach ($users as $user) {
            $daysLeft = $user->subscription_expires_at->diffInDays($today);
            $message = "Assalomu alaykum, {$user->name}!\n\n".
                "Eslatma: Sizning obunangiz $daysLeft kundan keyin tugaydi.\n".
                "Obunani yangilashni unutmang, xizmatlarimizdan uzluksiz foydalanishingiz uchun :)";

            Telegraph::chat($user->chat_id)
                ->message($message)
                ->send();
        }
    }
}
