<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Card
 *
 * @property int $id
 * @property int $client_id
 * @property string $masked_number
 * @property string $token
 * @property string|null $expire
 * @property string|null $phone
 * @property bool|null $verified
 * @property bool $is_main
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Client $client
 */

class Card extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'client_id', 'token', 'masked_number', 'expire', 'phone', 'verified_at', 'verified'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
