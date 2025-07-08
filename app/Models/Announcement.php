<?php

namespace App\Models;

use App\Enums\AnnouncementStatus;
use App\Jobs\BroadcastMessageJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property bool $has_attachment
 * @property string $body
 * @property string $file_path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Announcement extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $casts = [
        'status' => AnnouncementStatus::class,
    ];

    protected static function booted(): void
    {
        static::created(function (Announcement $announcement) {
            BroadcastMessageJob::dispatch($announcement);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
