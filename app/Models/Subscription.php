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
 * @property int $payment_retry_count
 * @property Carbon|null $last_payment_attempt
 * @property string|null $last_payment_error
 * @property int|null $previous_subscription_id
 * @property bool $is_renewal
 * @property Carbon|null $reminder_sent_at
 * @property int $reminder_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Client $client
 * @property Plan $plan
 * @property Subscription|null $previousSubscription
 */

class Subscription extends Model
{
    protected $guarded = ['id'];
    
    protected $casts = [
        'status' => 'boolean',
        'expires_at' => 'date',
        'last_payment_attempt' => 'datetime',
        'payment_retry_count' => 'integer',
        'is_renewal' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'reminder_count' => 'integer',
    ];
    
    protected $attributes = [
        'payment_retry_count' => 0,
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    public function previousSubscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'previous_subscription_id');
    }
    
    public function isActive(): bool
    {
        return $this->status && $this->expires_at->isFuture();
    }
    
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    
    public function daysUntilExpiry(): int
    {
        return max(0, $this->expires_at->diffInDays(now()));
    }
    
    public function canRetryPayment(): bool
    {
        $maxRetries = config('services.payment.max_retries', 3);
        return $this->payment_retry_count < $maxRetries;
    }
    
    public function canRenewEarly(): bool
    {
        // Allow renewal 3 days before expiry
        return $this->isActive() && now()->diffInDays($this->expires_at, false) <= 3;
    }
    
    public function canChangePlan(): bool
    {
        // Free plans can upgrade anytime, paid plans only 3 days before expiry
        return $this->plan->price == 0 || $this->canRenewEarly();
    }

    public function deactivate(): void
    {
        $this->update(['status' => false]);
    }
}
