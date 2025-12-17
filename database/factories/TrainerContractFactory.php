<?php

namespace Database\Factories;

use App\Domain\Commerce\TrainerContract;
use App\Domain\Gym\Gym;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\TrainerProfile;
use App\Shared\Enums\ContractStatus;
use App\Shared\Enums\ContractType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Commerce\TrainerContract>
 */
class TrainerContractFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrainerContract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $contractType = fake()->randomElement(ContractType::cases());
        $validFrom = fake()->dateTimeBetween('-6 months', 'now');
        $validUntil = fake()->dateTimeBetween($validFrom, '+6 months');

        return [
            'trainee_id' => TraineeProfile::factory(),
            'trainer_id' => TrainerProfile::factory(),
            'gym_id' => fake()->optional()->passthrough(Gym::factory()),
            'contract_type' => $contractType,
            'total_sessions' => $contractType === ContractType::SESSION_BASED ? fake()->numberBetween(5, 50) : null,
            'sessions_used' => $contractType === ContractType::SESSION_BASED ? fake()->numberBetween(0, 10) : 0,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'price_cents' => fake()->numberBetween(10000, 100000),
            'currency' => 'USD',
            'status' => ContractStatus::ACTIVE,
            'payment_reference' => 'contract_' . fake()->uuid(),
        ];
    }

    /**
     * Create a session-based contract.
     */
    public function sessionBased(int $totalSessions = null): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_type' => ContractType::SESSION_BASED,
            'total_sessions' => $totalSessions ?? fake()->numberBetween(5, 50),
            'sessions_used' => 0,
        ]);
    }

    /**
     * Create a time-based contract.
     */
    public function timeBased(int $months = 3): static
    {
        $validFrom = fake()->dateTimeBetween('-1 month', 'now');
        $validUntil = (clone $validFrom)->modify("+{$months} months");

        return $this->state(fn (array $attributes) => [
            'contract_type' => ContractType::TIME_BASED,
            'total_sessions' => null,
            'sessions_used' => 0,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
        ]);
    }

    /**
     * Create an active contract.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::ACTIVE,
        ]);
    }

    /**
     * Create a completed contract.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::COMPLETED,
        ]);
    }

    /**
     * Create a cancelled contract.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::CANCELLED,
        ]);
    }

    /**
     * Create an expired contract.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::EXPIRED,
            'valid_until' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
