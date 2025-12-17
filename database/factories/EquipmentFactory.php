<?php

namespace Database\Factories;

use App\Domain\Training\Equipment;
use App\Shared\Enums\EquipmentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Equipment::class;

    /**
     * Define the model's default state.
     *
     * Note: Equipment is a PRESET catalog. This factory is primarily for testing.
     * In production, equipment is seeded via EquipmentSeeder.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'category' => fake()->randomElement(EquipmentCategory::cases()),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Create free weights equipment.
     */
    public function freeWeights(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => EquipmentCategory::FREE_WEIGHTS,
        ]);
    }

    /**
     * Create machine equipment.
     */
    public function machine(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => EquipmentCategory::MACHINES,
        ]);
    }

    /**
     * Create bodyweight equipment.
     */
    public function bodyweight(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => EquipmentCategory::BODYWEIGHT,
        ]);
    }
}
