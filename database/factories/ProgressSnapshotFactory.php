<?php

namespace Database\Factories;

use App\Domain\Analytics\ProgressSnapshot;
use App\Domain\Identity\TraineeProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Analytics\ProgressSnapshot>
 */
class ProgressSnapshotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ProgressSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d');

        return [
            'trainee_id' => TraineeProfile::factory(),
            'snapshot_date' => $date,
            'workouts_completed' => fake()->numberBetween(0, 300),
            'total_volume_kg' => fake()->numberBetween(0, 500000),
            'total_duration_minutes' => fake()->numberBetween(0, 50000),
            'total_reps' => fake()->numberBetween(0, 100000),
            'workout_streak_days' => fake()->numberBetween(0, 365),
            'weight_kg' => fake()->optional()->randomFloat(2, 50, 120),
            'body_fat_percentage' => fake()->optional()->randomFloat(2, 8, 30),
            'measurements' => fake()->optional()->randomElements([
                'chest_cm' => fake()->numberBetween(80, 120),
                'waist_cm' => fake()->numberBetween(60, 100),
                'bicep_cm' => fake()->numberBetween(25, 45),
            ]),
            'photos' => fake()->optional()->randomElements([
                fake()->imageUrl(640, 480, 'fitness'),
                fake()->imageUrl(640, 480, 'fitness'),
            ]),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Create a snapshot for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'snapshot_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Create a snapshot with weight measurement.
     */
    public function withWeight(float $weight): static
    {
        return $this->state(fn (array $attributes) => [
            'weight_kg' => $weight,
        ]);
    }

    /**
     * Create a snapshot with body fat percentage.
     */
    public function withBodyFat(float $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'body_fat_percentage' => $percentage,
        ]);
    }

    /**
     * Create a snapshot with photos.
     */
    public function withPhotos(array $photoUrls = null): static
    {
        return $this->state(fn (array $attributes) => [
            'photos' => $photoUrls ?? [
                fake()->imageUrl(640, 480, 'fitness'),
                fake()->imageUrl(640, 480, 'fitness'),
            ],
        ]);
    }
}
