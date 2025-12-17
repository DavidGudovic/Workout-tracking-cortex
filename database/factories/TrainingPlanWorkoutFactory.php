<?php

namespace Database\Factories;

use App\Domain\Training\TrainingPlanDay;
use App\Domain\Training\TrainingPlanWorkout;
use App\Domain\Training\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\TrainingPlanWorkout>
 */
class TrainingPlanWorkoutFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrainingPlanWorkout::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_plan_day_id' => TrainingPlanDay::factory(),
            'workout_id' => Workout::factory(),
            'is_optional' => false,
        ];
    }

    /**
     * Mark workout as optional.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_optional' => true,
        ]);
    }
}
