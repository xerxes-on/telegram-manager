<?php

namespace Database\Factories;

use App\Enums\AnnouncementStatus;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'status' => AnnouncementStatus::IN_PROGRESS,
            'user_id' => User::factory(),
            'has_attachment' => false,
            'file_path' => null,
        ];
    }

    /**
     * Indicate that the announcement has an attachment.
     */
    public function withAttachment(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_attachment' => true,
            'file_path' => 'announcements/' . $this->faker->image('public/storage/announcements', 640, 480, null, false),
        ]);
    }

    /**
     * Indicate that the announcement is sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::SENT,
        ]);
    }

    /**
     * Indicate that the announcement failed to send.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnnouncementStatus::FAILED,
        ]);
    }
} 