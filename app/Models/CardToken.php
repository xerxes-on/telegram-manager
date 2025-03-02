<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardToken extends Model
{
    protected $table = 'card_tokens';
    protected $fillable = ['user_id', 'token', 'is_active'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    protected $hidden = [
        'token',
    ];
}
