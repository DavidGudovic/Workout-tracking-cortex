<?php

namespace Database\Factories;

use App\Domain\Training\TrainingPlan;
use App\Domain\Training\TrainingPlanWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\TrainingPlanWeek>
 */
class TrainingPlanWeekFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrainingPlanWeek::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weekNumber = fake()->numberBetween(1, 12);

        return [
            'training_plan_id' => TrainingPlan::factory(),
            'week_number' => $weekNumber,
            'name' => 'Week ' . $weekNumber,
            'description' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Create a deload week.
     */
    public function deload(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Week ' . $attributes['week_number'] . ' - Deload',
            'description' => 'Recovery week with reduced volume and intensity',
        ]);
    }

    /**
     * Set specific week number.
     */
    public function weekNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'week_number' => $number,
            'name' => 'Week ' . $number,
        ]);
    }
}
