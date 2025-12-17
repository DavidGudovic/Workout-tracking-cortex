<?php

namespace Database\Factories;

use App\Domain\Execution\ExerciseLog;
use App\Domain\Execution\SetLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Execution\SetLog>
 */
class SetLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SetLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exercise_log_id' => ExerciseLog::factory(),
            'set_number' => 1,
            'target_reps' => fake()->numberBetween(6, 15),
            'actual_reps' => fake()->numberBetween(6, 15),
            'weight_kg' => fake()->randomFloat(2, 10, 200),
            'rpe' => fake()->optional()->numberBetween(5, 10),
            'is_warmup' => false,
            'is_failure' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Create a warmup set.
     */
    public function warmup(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_warmup' => true,
            'weight_kg' => fake()->randomFloat(2, 10, 60),
            'rpe' => fake()->numberBetween(3, 6),
        ]);
    }

    /**
     * Create a set taken to failure.
     */
    public function toFailure(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_failure' => true,
            'rpe' => 10,
        ]);
    }

    /**
     * Create a duration-based set.
     */
    public function duration(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_reps' => null,
            'actual_reps' => null,
            'target_duration_seconds' => fake()->numberBetween(30, 300),
            'actual_duration_seconds' => fake()->numberBetween(30, 300),
            'weight_kg' => null,
        ]);
    }

    /**
     * Create a distance-based set.
     */
    public function distance(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_reps' => null,
            'actual_reps' => null,
            'target_distance_meters' => fake()->numberBetween(100, 5000),
            'actual_distance_meters' => fake()->numberBetween(100, 5000),
            'weight_kg' => null,
        ]);
    }

    /**
     * Set specific set number.
     */
    public function setNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'set_number' => $number,
        ]);
    }
}
