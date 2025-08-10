<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Card>
 */
class CardFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Card::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'masked_number' => '**** **** **** ' . fake()->numerify('####'),
            'token' => fake()->unique()->uuid(),
            'expire' => fake()->date('m/y'),
            'phone' => fake()->phoneNumber(),
            'verified' => true,
            'is_main' => false,
        ];
    }

    /**
     * Indicate that the card is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified' => true,
        ]);
    }

    /**
     * Indicate that the card is not verified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified' => false,
        ]);
    }

    /**
     * Indicate that the card is the main card.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
        ]);
    }
} 