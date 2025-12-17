<?php

namespace Database\Factories;

use App\Domain\Commerce\GymSubscription;
use App\Domain\Gym\Gym;
use App\Domain\Gym\SubscriptionTier;
use App\Domain\Identity\TraineeProfile;
use App\Shared\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Commerce\GymSubscription>
 */
class GymSubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = GymSubscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now');
        $endDate = fake()->dateTimeBetween($startDate, '+3 months');

        return [
            'trainee_id' => TraineeProfile::factory(),
            'gym_id' => Gym::factory(),
            'subscription_tier_id' => SubscriptionTier::factory(),
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_start' => $startDate,
            'current_period_end' => $endDate,
            'payment_reference' => 'sub_' . fake()->uuid(),
        ];
    }

    /**
     * Create an active subscription.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'current_period_end' => fake()->dateTimeBetween('now', '+3 months'),
        ]);
    }

    /**
     * Create a cancelled subscription.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'cancellation_reason' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * Create an expired subscription.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::EXPIRED,
            'current_period_end' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create a suspended subscription.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::SUSPENDED,
        ]);
    }
}
