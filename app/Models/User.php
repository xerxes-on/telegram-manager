<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'phone_number',
        'chat_id',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function hasActiveSubscription(): bool
    {
        $free_plan_id = Plan::where('price', 0)->first()->id;
        return $this->subscriptions()
            ->where('status', true)
            ->where('plan_id', '!=', $free_plan_id)
            ->where('expires_at', '>', now()->addDay()->format('Y-m-d'))
            ->exists();
    }
    public function hasUsedFreePlan(): bool
    {
        $free_plan_id = Plan::where('price', 0)->first()->id;
        return $this->subscriptions()
            ->where('plan_id', $free_plan_id)
            ->exists();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
