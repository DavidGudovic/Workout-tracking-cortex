<?php

namespace Database\Factories;

use App\Domain\Training\Exercise;
use App\Domain\Training\Workout;
use App\Domain\Training\WorkoutExercise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\WorkoutExercise>
 */
class WorkoutExerciseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = WorkoutExercise::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'exercise_id' => Exercise::factory(),
            'sort_order' => 0,
            'sets' => fake()->numberBetween(2, 5),
            'target_reps' => fake()->numberBetween(6, 15),
            'target_duration_seconds' => null,
            'target_distance_meters' => null,
            'rest_seconds' => fake()->numberBetween(30, 180),
            'notes' => fake()->optional()->sentence(),
            'superset_group' => null,
        ];
    }

    /**
     * Create a duration-based exercise.
     */
    public function duration(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_reps' => null,
            'target_duration_seconds' => fake()->numberBetween(30, 300),
            'target_distance_meters' => null,
        ]);
    }

    /**
     * Create a distance-based exercise.
     */
    public function distance(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_reps' => null,
            'target_duration_seconds' => null,
            'target_distance_meters' => fake()->numberBetween(100, 5000),
        ]);
    }

    /**
     * Set as part of a superset.
     */
    public function superset(int $group = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'superset_group' => $group,
        ]);
    }
}
