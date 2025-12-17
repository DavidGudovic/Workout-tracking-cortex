<?php

namespace Database\Factories;

use App\Domain\Analytics\PersonalRecord;
use App\Domain\Execution\SetLog;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\Exercise;
use App\Shared\Enums\RecordType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domain\Analytics\PersonalRecord>
 */
class PersonalRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PersonalRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recordType = fake()->randomElement(RecordType::cases());

        $value = match($recordType) {
            RecordType::MAX_WEIGHT => fake()->randomFloat(2, 50, 300),
            RecordType::MAX_REPS => fake()->numberBetween(10, 50),
            RecordType::MAX_DURATION => fake()->numberBetween(60, 600),
            RecordType::MAX_VOLUME => fake()->randomFloat(2, 1000, 10000),
            RecordType::MAX_DISTANCE => fake()->randomFloat(2, 1000, 10000),
        };

        return [
            'trainee_id' => TraineeProfile::factory(),
            'exercise_id' => Exercise::factory(),
            'record_type' => $recordType,
            'value' => $value,
            'set_log_id' => SetLog::factory(),
            'achieved_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Create a max weight record.
     */
    public function maxWeight(float $weight = null): static
    {
        return $this->state(fn (array $attributes) => [
            'record_type' => RecordType::MAX_WEIGHT,
            'value' => $weight ?? fake()->randomFloat(2, 50, 300),
        ]);
    }

    /**
     * Create a max reps record.
     */
    public function maxReps(int $reps = null): static
    {
        return $this->state(fn (array $attributes) => [
            'record_type' => RecordType::MAX_REPS,
            'value' => $reps ?? fake()->numberBetween(10, 50),
        ]);
    }

    /**
     * Create a max duration record.
     */
    public function maxDuration(int $seconds = null): static
    {
        return $this->state(fn (array $attributes) => [
            'record_type' => RecordType::MAX_DURATION,
            'value' => $seconds ?? fake()->numberBetween(60, 600),
        ]);
    }

    /**
     * Create a max volume record.
     */
    public function maxVolume(float $volume = null): static
    {
        return $this->state(fn (array $attributes) => [
            'record_type' => RecordType::MAX_VOLUME,
            'value' => $volume ?? fake()->randomFloat(2, 1000, 10000),
        ]);
    }

    /**
     * Create a max distance record.
     */
    public function maxDistance(float $meters = null): static
    {
        return $this->state(fn (array $attributes) => [
            'record_type' => RecordType::MAX_DISTANCE,
            'value' => $meters ?? fake()->randomFloat(2, 1000, 10000),
        ]);
    }

    /**
     * Link to previous record.
     */
    public function withPreviousRecord(string $previousRecordId): static
    {
        return $this->state(fn (array $attributes) => [
            'previous_record_id' => $previousRecordId,
        ]);
    }
}
