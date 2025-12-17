<?php

namespace Database\Factories;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\User;
use App\Shared\Enums\ExperienceLevel;
use App\Shared\Enums\FitnessGoal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Identity\TraineeProfile>
 */
class TraineeProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TraineeProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => fake()->name(),
            'avatar_url' => fake()->optional()->imageUrl(200, 200, 'people'),
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years'),
            'gender' => fake()->optional()->randomElement(['male', 'female', 'other', 'prefer_not_to_say']),
            'height_cm' => fake()->numberBetween(150, 210),
            'weight_kg' => fake()->numberBetween(50, 120),
            'fitness_goal' => fake()->randomElement(FitnessGoal::cases()),
            'experience_level' => fake()->randomElement(ExperienceLevel::cases()),
        ];
    }

    /**
     * Indicate that the trainee is a beginner.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => ExperienceLevel::BEGINNER,
        ]);
    }

    /**
     * Indicate that the trainee is intermediate.
     */
    public function intermediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => ExperienceLevel::INTERMEDIATE,
        ]);
    }

    /**
     * Indicate that the trainee is advanced.
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => ExperienceLevel::ADVANCED,
        ]);
    }

    /**
     * Set specific fitness goal.
     */
    public function withGoal(FitnessGoal $goal): static
    {
        return $this->state(fn (array $attributes) => [
            'fitness_goal' => $goal,
        ]);
    }
}
