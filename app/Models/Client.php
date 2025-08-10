<?php

namespace App\Models;

use App\Enums\ConversationStates;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 *
 * @property int $id
 * @property string $first_name
 * @property string $telegram_id
 * @property string|null $last_name
 * @property string|null $phone_number
 * @property ConversationStates $state
 * @property string|null $username
 * @property string $chat_id
 * @property string $lang
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property Collection|Card[] $cards
 * @property Collection|Order[] $orders
 * @property Collection|Subscription[] $subscriptions
 * @property Collection|Receipt[] $receipts
 */
class Client extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected $casts = [
        'state' => ConversationStates::class,
    ];
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
    public function mainCard(): HasOne
    {
        return $this->hasOne(Card::class)->where('is_main', true);
    }

    public function setMainCard(Card $newMainCard): void
    {
        $this->cards()->where('id', '!=', $newMainCard->id)->update(['is_main' => false]);
        $newMainCard->is_main = true;
        $newMainCard->save();
    }
    public function hasUsedFreePlan(): bool
    {
        $free_plan = Plan::query()->where('price', 0)->first();
        if (!$free_plan) {
            return false;
        }
        return $this->subscriptions()->where('plan_id', $free_plan->id)->exists();
    }
    public function hasActiveSubscription(): bool
    {
        $free_plan = Plan::query()->where('price', 0)->first();
        $query = $this->subscriptions()->where('status', true)
            ->where('expires_at', '>', now()->addDay()->format('Y-m-d'));
        if ($free_plan) {
            $query->where('plan_id', '!=', $free_plan->id);
        }
        return $query->exists();
    }
}
