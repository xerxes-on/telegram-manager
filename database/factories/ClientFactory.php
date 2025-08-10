<?php

namespace Database\Factories;

use App\Enums\ConversationStates;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Client::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'telegram_id' => fake()->unique()->numerify('##########'),
            'phone_number' => fake()->phoneNumber(),
            'state' => ConversationStates::chat,
            'username' => fake()->userName(),
            'chat_id' => fake()->unique()->numerify('##########'),
            'lang' => fake()->randomElement(['uz', 'ru', 'oz']),
        ];
    }

    /**
     * Indicate that the client has a specific language.
     */
    public function withLanguage(string $lang): static
    {
        return $this->state(fn (array $attributes) => [
            'lang' => $lang,
        ]);
    }

    /**
     * Indicate that the client has a specific chat ID.
     */
    public function withChatId(string $chatId): static
    {
        return $this->state(fn (array $attributes) => [
            'chat_id' => $chatId,
        ]);
    }
} 