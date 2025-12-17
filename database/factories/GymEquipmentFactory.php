<?php

namespace Database\Factories;

use App\Domain\Gym\Gym;
use App\Domain\Gym\GymEquipment;
use App\Domain\Training\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Gym\GymEquipment>
 */
class GymEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = GymEquipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'equipment_id' => Equipment::factory(),
            'quantity' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Set specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }
}
