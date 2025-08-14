<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Order
 *
 * @property int $id
 * @property int $price
 * @property int $plan_id
 * @property string $status
 * @property int $client_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Client $client
 * @property Plan $plan
 */

class Order extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'price' => 'integer',
        'plan_id' => 'integer',
        'client_id' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

}
