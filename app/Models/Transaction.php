<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'transaction',
        'code',
        'state',
        'owner_id',
        'amount',
        'reason',
        'payme_time',
        'cancel_time',
        'create_time',
        'perform_time',
    ];
    public static function getTransactionsByTimeRange($from, $to)
    {
        return self::whereBetween('paycom_time', [$from, $to])
            ->get();
    }
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
