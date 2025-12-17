<?php

namespace Database\Factories;

use App\Domain\Gym\Gym;
use App\Domain\Identity\User;
use App\Shared\Enums\GymStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Gym\Gym>
 */
class GymFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Gym::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company() . ' Fitness';

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            // slug will be auto-generated from name
            'description' => fake()->optional()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip_code' => fake()->postcode(),
            'country' => 'USA',
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'website' => fake()->optional()->url(),
            'logo_url' => fake()->optional()->imageUrl(200, 200, 'fitness'),
            'status' => GymStatus::ACTIVE,
        ];
    }

    /**
     * Indicate that the gym is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GymStatus::PENDING,
        ]);
    }

    /**
     * Indicate that the gym is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GymStatus::SUSPENDED,
        ]);
    }

    /**
     * Indicate that the gym is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GymStatus::CLOSED,
        ]);
    }
}
