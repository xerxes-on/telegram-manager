<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = ['id'];

//    public function order(): HasOne
//    {
//        return $this->hasOne(Order::class);
//    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function deactivate(): bool
    {
        return $this->update([
            'status' => false,
//            'expires_at'=> now()
        ]);
    }
}
