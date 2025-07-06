<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Plan
 *
 * @property int $id
 * @property string $name
 * @property int $price
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|Subscription[] $subscriptions
 * @property Collection|Order[] $orders
 */
class Plan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($plan) {
            if (isset($plan->price)) {
                $plan->price *= 100;
            }
        });

        static::updating(function ($plan) {
            if ($plan->isDirty('price')) {
                $plan->price *= 100;
            }
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
