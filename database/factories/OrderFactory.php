<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'price' => 100000,
            'plan_id' => 1,
            'client_id' => Client::all()->first()->id,
            'status' => 'created',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
