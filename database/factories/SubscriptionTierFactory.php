<?php

namespace Database\Factories;

use App\Domain\Gym\Gym;
use App\Domain\Gym\SubscriptionTier;
use App\Shared\Enums\BillingPeriod;
use App\Shared\Enums\TierStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Gym\SubscriptionTier>
 */
class SubscriptionTierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = SubscriptionTier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tierNames = ['Basic', 'Standard', 'Premium', 'VIP', 'Elite'];
        $billingPeriod = fake()->randomElement(BillingPeriod::cases());

        // Price varies by billing period (monthly cheaper than annual per month)
        $basePrice = fake()->numberBetween(2999, 9999); // $29.99 - $99.99
        $months = $billingPeriod->months();
        $priceCents = $basePrice * $months * 0.9; // 10% discount for longer periods

        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->randomElement($tierNames),
            'description' => fake()->optional()->paragraph(),
            'price_cents' => (int) $priceCents,
            'billing_period' => $billingPeriod,
            'max_members' => fake()->optional()->numberBetween(50, 500),
            'status' => TierStatus::ACTIVE,
        ];
    }

    /**
     * Create a monthly subscription tier.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period' => BillingPeriod::MONTHLY,
            'price_cents' => fake()->numberBetween(2999, 9999),
        ]);
    }

    /**
     * Create a quarterly subscription tier.
     */
    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period' => BillingPeriod::QUARTERLY,
            'price_cents' => fake()->numberBetween(7999, 24999),
        ]);
    }

    /**
     * Create a yearly subscription tier.
     */
    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period' => BillingPeriod::YEARLY,
            'price_cents' => fake()->numberBetween(29999, 99999),
        ]);
    }

    /**
     * Indicate that the tier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TierStatus::INACTIVE,
        ]);
    }
}
