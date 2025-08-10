<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionTransaction extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
    
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
    
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
    
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
    
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}