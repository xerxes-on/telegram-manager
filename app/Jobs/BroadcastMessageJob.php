<?php

namespace App\Jobs;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\Client;
use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastMessageJob implements ShouldQueue
{
    use Queueable;

    public Announcement $announcement;

    /**
     * Create a new job instance.
     */
    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app()->setLocale('ru');
        try {
            $allClients = Client::all();
            if ($this->announcement->has_attachment) {
                foreach ($allClients as $client) {
                    try {
                        Telegraph::chat($client->chat_id)
                            ->html($this->announcement->body)
                            ->photo(url('storage/' . $this->announcement->file_path), __("filament.announcement.messages.attachment"))
                            ->send();
                    } catch (Exception $e) {
                        Log::error(__('filament.announcement.messages.send_error_client', [
//                            'client_id' => $client->id,
                            'error' => $e->getMessage()
                        ]));
                    }
                }
            } else {
                foreach ($allClients as $client) {
                    try {
                        Telegraph::chat($client->chat_id)
                            ->html($this->announcement->body)
                            ->send();
                    } catch (Exception $e) {
                        Log::error(__('filament.announcement.messages.send_error_client', [
//                            'client_id' => $client->id,
                            'error' => $e->getMessage()
                        ]));
                    }
                }
            }

            $this->announcement->update(['status' => AnnouncementStatus::SENT]);
            Notification::make()
                ->body(__('filament.announcement.messages.send_success', ['id' => $this->announcement->id]))
                ->success()
                ->sendToDatabase(User::all());
        } catch (Exception $e) {
            Log::error(__('filament.announcement.messages.send_error', [
                'id' => $this->announcement->id,
                'error' => $e->getMessage()
            ]));
            $this->announcement->update(['status' => AnnouncementStatus::FAILED]);
        }
    }
}
