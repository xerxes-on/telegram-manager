<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'paycom_transaction_id',
        'paycom_time',
        'paycom_time_datetime',
        'create_time',
        'perform_time',
        'cancel_time',
        'amount',
        'state',
        'reason',
        'receivers',
        'order_id',
        'perform_time_unix',
    ];

    protected $casts = [
        'paycom_time' => 'string',
        'perform_time_unix' => 'string',
        'cancel_time' => 'string',
        'amount' => 'integer',
        'state' => 'integer',
        'reason' => 'integer',
        'order_id' => 'integer',
        'create_time' => 'datetime',
        'perform_time' => 'datetime',
        'paycom_time_datetime' => 'datetime',
    ];

    public static function getTransactionsByTimeRange($from, $to)
    {
        return self::whereBetween('paycom_time', [$from, $to])
            ->get();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
