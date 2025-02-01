<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Card extends Model
{
    protected $fillable = [
        'user_id', 'token', 'masked_number', 'expire', 'phone', 'verified_at', 'verified'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
