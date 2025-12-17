<?php

namespace Database\Factories;

use App\Domain\Identity\TrainerProfile;
use App\Domain\Training\TrainingPlan;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\FitnessGoal;
use App\Shared\Enums\PricingType;
use App\Shared\Enums\WorkoutStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\TrainingPlan>
 */
class TrainingPlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrainingPlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'creator_id' => TrainerProfile::factory(),
            'name' => fake()->words(3, true) . ' Program',
            'description' => fake()->optional()->paragraph(),
            'duration_weeks' => fake()->randomElement([4, 6, 8, 12, 16]),
            'days_per_week' => fake()->numberBetween(3, 6),
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'goal' => fake()->randomElement(FitnessGoal::cases()),
            'pricing_type' => PricingType::FREE,
            'price_cents' => null,
            'status' => WorkoutStatus::DRAFT,
        ];
    }

    /**
     * Create a free training plan.
     */
    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::FREE,
            'price_cents' => null,
        ]);
    }

    /**
     * Create a premium training plan.
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing_type' => PricingType::PREMIUM,
            'price_cents' => fake()->numberBetween(4999, 29999), // $49.99 - $299.99
        ]);
    }

    /**
     * Create a published training plan.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WorkoutStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Create a 4-week plan.
     */
    public function fourWeeks(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_weeks' => 4,
        ]);
    }

    /**
     * Create an 8-week plan.
     */
    public function eightWeeks(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_weeks' => 8,
        ]);
    }

    /**
     * Create a 12-week plan.
     */
    public function twelveWeeks(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_weeks' => 12,
        ]);
    }
}
