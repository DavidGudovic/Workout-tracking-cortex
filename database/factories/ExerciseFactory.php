<?php

namespace Database\Factories;

use App\Domain\Identity\TrainerProfile;
use App\Domain\Training\Exercise;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\ExerciseType;
use App\Shared\Enums\ExerciseVisibility;
use App\Shared\Enums\PerformanceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\Exercise>
 */
class ExerciseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Exercise::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $muscleGroups = ['Chest', 'Back', 'Shoulders', 'Arms', 'Legs', 'Core', 'Glutes', 'Calves'];

        return [
            'trainer_id' => TrainerProfile::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'instructions' => fake()->optional()->paragraphs(3, true),
            'primary_muscle_groups' => fake()->randomElements($muscleGroups, fake()->numberBetween(1, 2)),
            'secondary_muscle_groups' => fake()->optional()->randomElements($muscleGroups, fake()->numberBetween(1, 2)),
            'difficulty' => fake()->randomElement(Difficulty::cases()),
            'performance_type' => fake()->randomElement(PerformanceType::cases()),
            'type' => ExerciseType::CUSTOM,
            'visibility' => ExerciseVisibility::PRIVATE,
        ];
    }

    /**
     * Create a system exercise (immutable, no trainer).
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'trainer_id' => null,
            'type' => ExerciseType::SYSTEM,
            'visibility' => ExerciseVisibility::PUBLIC_POOL,
        ]);
    }

    /**
     * Create a public exercise.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => ExerciseVisibility::PUBLIC_POOL,
        ]);
    }

    /**
     * Create a private exercise.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => ExerciseVisibility::PRIVATE,
        ]);
    }

    /**
     * Create a repetition-based exercise.
     */
    public function repetition(): static
    {
        return $this->state(fn (array $attributes) => [
            'performance_type' => PerformanceType::REPETITION,
        ]);
    }

    /**
     * Create a duration-based exercise.
     */
    public function duration(): static
    {
        return $this->state(fn (array $attributes) => [
            'performance_type' => PerformanceType::DURATION,
        ]);
    }

    /**
     * Create a distance-based exercise.
     */
    public function distance(): static
    {
        return $this->state(fn (array $attributes) => [
            'performance_type' => PerformanceType::DISTANCE,
        ]);
    }
}
