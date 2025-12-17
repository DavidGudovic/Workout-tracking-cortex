<?php

namespace Database\Factories;

use App\Domain\Execution\WorkoutSession;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\Workout;
use App\Shared\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Execution\WorkoutSession>
 */
class WorkoutSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = WorkoutSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trainee_id' => TraineeProfile::factory(),
            'workout_id' => Workout::factory(),
            'workout_version' => 1,
            'started_at' => now(),
            'status' => SessionStatus::STARTED,
        ];
    }

    /**
     * Create an in-progress session.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::IN_PROGRESS,
            'started_at' => fake()->dateTimeBetween('-2 hours', 'now'),
        ]);
    }

    /**
     * Create a completed session.
     */
    public function completed(): static
    {
        $startedAt = fake()->dateTimeBetween('-7 days', '-1 hour');
        $completedAt = fake()->dateTimeBetween($startedAt, 'now');
        $durationMinutes = fake()->numberBetween(30, 120);

        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::COMPLETED,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'total_duration_minutes' => $durationMinutes,
            'total_volume_kg' => fake()->numberBetween(1000, 10000),
        ]);
    }

    /**
     * Create an abandoned session.
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::ABANDONED,
            'started_at' => fake()->dateTimeBetween('-7 days', '-1 hour'),
            'completed_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
        ]);
    }
}
