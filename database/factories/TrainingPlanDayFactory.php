<?php

namespace Database\Factories;

use App\Domain\Training\TrainingPlanDay;
use App\Domain\Training\TrainingPlanWeek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Training\TrainingPlanDay>
 */
class TrainingPlanDayFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrainingPlanDay::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dayNumber = fake()->numberBetween(1, 7);

        return [
            'training_plan_week_id' => TrainingPlanWeek::factory(),
            'day_number' => $dayNumber,
            'name' => 'Day ' . $dayNumber,
            'is_rest_day' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Create a rest day.
     */
    public function restDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_rest_day' => true,
            'name' => 'Day ' . $attributes['day_number'] . ' - Rest',
        ]);
    }

    /**
     * Set specific day number.
     */
    public function dayNumber(int $number): static
    {
        return $this->state(fn (array $attributes) => [
            'day_number' => $number,
            'name' => 'Day ' . $number,
        ]);
    }

    /**
     * Create with specific name (e.g., "Upper Body Day").
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
}
