<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Subscription
 *
 * @property int $id
 * @property int $client_id
 * @property bool $status
 * @property string $receipt_id
 * @property Carbon $expires_at
 * @property int $plan_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Client $client
 * @property Plan $plan
 */

class Subscription extends Model
{
    protected $guarded = ['id'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
