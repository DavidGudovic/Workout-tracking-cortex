<?php

namespace App\Shared\Enums;

enum PerformanceType: string
{
    case REPETITION = 'repetition';
    case DURATION = 'duration';
    case DISTANCE = 'distance';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the type.
     */
    public function label(): string
    {
        return match ($this) {
            self::REPETITION => 'Repetition-Based',
            self::DURATION => 'Duration-Based',
            self::DISTANCE => 'Distance-Based',
        };
    }

    /**
     * Get the unit for this performance type.
     */
    public function unit(): string
    {
        return match ($this) {
            self::REPETITION => 'reps',
            self::DURATION => 'seconds',
            self::DISTANCE => 'meters',
        };
    }
}
