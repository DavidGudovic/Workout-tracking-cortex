<?php

namespace Database\Factories;

use App\Domain\Commerce\TrainingPlanPurchase;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\TrainingPlan;
use App\Shared\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Commerce\TrainingPlanPurchase>
 */
class TrainingPlanPurchaseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrainingPlanPurchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trainee_id' => TraineeProfile::factory(),
            'training_plan_id' => TrainingPlan::factory()->premium(),
            'price_cents' => fake()->numberBetween(4999, 29999),
            'currency' => 'USD',
            'payment_status' => PaymentStatus::COMPLETED,
            'payment_reference' => 'ref_' . fake()->uuid(),
            'purchased_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a pending purchase.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::PENDING,
            'payment_reference' => null,
        ]);
    }

    /**
     * Create a completed purchase.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::COMPLETED,
            'payment_reference' => 'ref_' . fake()->uuid(),
        ]);
    }

    /**
     * Create a failed purchase.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::FAILED,
            'payment_reference' => null,
        ]);
    }

    /**
     * Create a refunded purchase.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::REFUNDED,
        ]);
    }
}
