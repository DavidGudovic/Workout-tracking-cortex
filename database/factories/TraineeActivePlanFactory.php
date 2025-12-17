<?php

namespace Database\Factories;

use App\Domain\Commerce\TraineeActivePlan;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\TrainingPlan;
use App\Shared\Enums\ActivePlanStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Commerce\TraineeActivePlan>
 */
class TraineeActivePlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TraineeActivePlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trainee_id' => TraineeProfile::factory(),
            'training_plan_id' => TrainingPlan::factory(),
            'started_at' => fake()->dateTimeBetween('-3 months', 'now'),
            'current_week' => fake()->numberBetween(1, 4),
            'current_day' => fake()->numberBetween(1, 5),
            'status' => ActivePlanStatus::ACTIVE,
        ];
    }

    /**
     * Create an active plan.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivePlanStatus::ACTIVE,
            'completed_at' => null,
        ]);
    }

    /**
     * Create a paused plan.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivePlanStatus::PAUSED,
        ]);
    }

    /**
     * Create a completed plan.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivePlanStatus::COMPLETED,
            'completed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Create an abandoned plan.
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ActivePlanStatus::ABANDONED,
            'completed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Set at specific week and day.
     */
    public function atProgress(int $week, int $day): static
    {
        return $this->state(fn (array $attributes) => [
            'current_week' => $week,
            'current_day' => $day,
        ]);
    }
}
