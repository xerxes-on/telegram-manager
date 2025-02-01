<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
