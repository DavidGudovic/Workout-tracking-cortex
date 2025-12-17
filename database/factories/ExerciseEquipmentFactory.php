<?php

namespace Database\Factories;

use App\Domain\Training\Equipment;
use App\Domain\Training\Exercise;
use App\Domain\Training\ExerciseEquipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\ExerciseEquipment>
 */
class ExerciseEquipmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = ExerciseEquipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exercise_id' => Exercise::factory(),
            'equipment_id' => Equipment::factory(),
            'is_primary' => false,
        ];
    }

    /**
     * Mark as primary equipment.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
