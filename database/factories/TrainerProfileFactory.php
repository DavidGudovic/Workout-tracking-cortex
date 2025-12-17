<?php

namespace Database\Factories;

use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;
use App\Shared\Enums\TrainerStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Identity\TrainerProfile>
 */
class TrainerProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrainerProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $displayName = fake()->name();

        return [
            'user_id' => User::factory(),
            'display_name' => $displayName,
            // slug will be auto-generated from display_name
            'bio' => fake()->optional()->paragraph(),
            'specializations' => fake()->randomElements(
                ['Strength Training', 'Bodybuilding', 'Powerlifting', 'CrossFit', 'Olympic Lifting', 'Functional Fitness'],
                fake()->numberBetween(1, 3)
            ),
            'certifications' => fake()->optional()->randomElements(
                ['NASM-CPT', 'ACE', 'ISSA', 'CSCS', 'NSCA-CPT'],
                fake()->numberBetween(1, 2)
            ),
            'years_experience' => fake()->numberBetween(1, 20),
            'hourly_rate_cents' => fake()->numberBetween(3000, 15000), // $30-$150/hr
            'status' => TrainerStatus::ACTIVE,
        ];
    }

    /**
     * Indicate that the trainer is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrainerStatus::PENDING,
        ]);
    }

    /**
     * Indicate that the trainer is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrainerStatus::SUSPENDED,
        ]);
    }

    /**
     * Create a trainer with specific specialization.
     */
    public function withSpecialization(string $specialization): static
    {
        return $this->state(fn (array $attributes) => [
            'specializations' => [$specialization],
        ]);
    }
}
