<?php

namespace Database\Factories;

use App\Domain\Training\Exercise;
use App\Domain\Training\ExerciseMedia;
use App\Shared\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\ExerciseMedia>
 */
class ExerciseMediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ExerciseMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(MediaType::cases());

        $url = match($type) {
            MediaType::VIDEO_URL => 'https://www.youtube.com/watch?v=' . fake()->regexify('[A-Za-z0-9]{11}'),
            MediaType::IMAGE_URL => fake()->imageUrl(640, 480, 'fitness'),
            MediaType::GIF_URL => fake()->imageUrl(640, 480, 'fitness', true, 'gif'),
        };

        return [
            'exercise_id' => Exercise::factory(),
            'type' => $type,
            'url' => $url,
            'caption' => fake()->optional()->sentence(),
            'is_primary' => false,
        ];
    }

    /**
     * Create a video media.
     */
    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MediaType::VIDEO_URL,
            'url' => 'https://www.youtube.com/watch?v=' . fake()->regexify('[A-Za-z0-9]{11}'),
        ]);
    }

    /**
     * Create an image media.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MediaType::IMAGE_URL,
            'url' => fake()->imageUrl(640, 480, 'fitness'),
        ]);
    }

    /**
     * Create a GIF media.
     */
    public function gif(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MediaType::GIF_URL,
            'url' => fake()->imageUrl(640, 480, 'fitness', true, 'gif'),
        ]);
    }

    /**
     * Mark as primary media.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
