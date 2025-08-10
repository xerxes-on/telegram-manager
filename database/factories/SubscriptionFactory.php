<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'plan_id' => Plan::factory(),
            'status' => true,
            'receipt_id' => fake()->unique()->uuid(),
            'expires_at' => Carbon::now()->addDays(30),
        ];
    }

    /**
     * Indicate that the subscription is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => true,
        ]);
    }

    /**
     * Indicate that the subscription is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => false,
        ]);
    }

    /**
     * Indicate that the subscription expires today.
     */
    public function expiresToday(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->endOfDay(),
        ]);
    }

    /**
     * Indicate that the subscription expires in a specific number of days.
     */
    public function expiresInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->addDays($days),
        ]);
    }

    /**
     * Indicate that the subscription expired a specific number of days ago.
     */
    public function expiredDaysAgo(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->subDays($days),
        ]);
    }

    /**
     * Indicate that the subscription expires within the next 12 hours.
     */
    public function expiresSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->addHours(fake()->numberBetween(1, 12)),
        ]);
    }

    /**
     * Indicate that the subscription expires in exactly 3 days.
     */
    public function expiresInThreeDays(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->addDays(3),
        ]);
    }

    /**
     * Indicate that the subscription expires in exactly 7 days.
     */
    public function expiresInSevenDays(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => Carbon::now()->addDays(7),
        ]);
    }
} 