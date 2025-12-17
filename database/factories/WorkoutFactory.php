<?php

namespace Database\Factories;

use App\Domain\Identity\TrainerProfile;
use App\Domain\Training\Workout;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\PricingType;
use App\Shared\Enums\WorkoutStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\Workout>
 */
class WorkoutFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Workout::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trainer_id' => TrainerProfile::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'estimated_duration_minutes' => fake()->numberBetween(30, 120),
            'pricing_type' => PricingType::FREE,
            'price_cents' => null,
            'status' => WorkoutStatus::DRAFT,
        ];
    }

    /**
     * Create a free workout.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::FREE,
            'price_cents' => null,
        ]);
    }

    /**
     * Create a premium workout.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::PREMIUM,
            'price_cents' => fake()->numberBetween(999, 9999), // $9.99 - $99.99
        ]);
    }

    /**
     * Create a published workout.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkoutStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Create a draft workout.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkoutStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    /**
     * Create an archived workout.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkoutStatus::ARCHIVED,
        ]);
    }
}
