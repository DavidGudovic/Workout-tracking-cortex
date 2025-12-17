<?php

namespace Database\Factories;

use App\Domain\Execution\ExerciseLog;
use App\Domain\Execution\WorkoutSession;
use App\Domain\Training\Exercise;
use App\Shared\Enums\ExerciseLogStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Execution\ExerciseLog>
 */
class ExerciseLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ExerciseLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workout_session_id' => WorkoutSession::factory(),
            'workout_exercise_id' => \App\Domain\Training\WorkoutExercise::factory(),
            'exercise_id' => Exercise::factory(),
            'status' => ExerciseLogStatus::PENDING,
        ];
    }

    /**
     * Create a pending exercise log.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExerciseLogStatus::PENDING,
        ]);
    }

    /**
     * Create an in-progress exercise log.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExerciseLogStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Create a completed exercise log.
     */
    public function completed(): static
    {
        $startedAt = fake()->dateTimeBetween('-2 hours', '-30 minutes');
        $completedAt = fake()->dateTimeBetween($startedAt, 'now');

        return $this->state(fn (array $attributes) => [
            'status' => ExerciseLogStatus::COMPLETED,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'notes' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * Create a skipped exercise log.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExerciseLogStatus::SKIPPED,
            'notes' => fake()->optional()->sentence(),
        ]);
    }
}
