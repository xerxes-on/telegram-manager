<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Receipt
 *
 * @property int $id
 * @property int|null $client_id
 * @property string $receipt_id
 * @property string|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Client|null $client
 */

class Receipt extends Model
{
    protected $guarded = [];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
