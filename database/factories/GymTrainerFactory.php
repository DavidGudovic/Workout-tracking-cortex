<?php

namespace Database\Factories;

use App\Domain\Gym\Gym;
use App\Domain\Gym\GymTrainer;
use App\Domain\Identity\TrainerProfile;
use App\Shared\Enums\GymTrainerStatus;
use App\Shared\Enums\TrainerRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Gym\GymTrainer>
 */
class GymTrainerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = GymTrainer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'trainer_id' => TrainerProfile::factory(),
            'role' => fake()->randomElement(TrainerRole::cases()),
            'hourly_rate_cents' => fake()->numberBetween(3000, 15000),
            'commission_percentage' => fake()->numberBetween(5, 30),
            'status' => GymTrainerStatus::ACTIVE,
            'hired_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }

    /**
     * Indicate that the trainer is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GymTrainerStatus::PENDING,
            'hired_at' => null,
        ]);
    }

    /**
     * Indicate that the trainer is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GymTrainerStatus::TERMINATED,
            'terminated_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'termination_reason' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * Set as staff trainer role.
     */
    public function staffTrainer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TrainerRole::STAFF_TRAINER,
        ]);
    }

    /**
     * Set as head trainer role.
     */
    public function headTrainer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TrainerRole::HEAD_TRAINER,
            'commission_percentage' => fake()->numberBetween(15, 40),
        ]);
    }

    /**
     * Set as contractor role.
     */
    public function contractor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => TrainerRole::CONTRACTOR,
            'commission_percentage' => fake()->numberBetween(20, 50),
        ]);
    }
}
