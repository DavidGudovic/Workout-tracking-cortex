<?php

namespace App\Shared\Enums;

enum RecordType: string
{
    case MAX_WEIGHT = 'max_weight';
    case MAX_REPS = 'max_reps';
    case MAX_DURATION = 'max_duration';
    case MAX_VOLUME = 'max_volume';
    case MAX_DISTANCE = 'max_distance';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the record type.
     */
    public function label(): string
    {
        return match ($this) {
            self::MAX_WEIGHT => 'Max Weight',
            self::MAX_REPS => 'Max Reps',
            self::MAX_DURATION => 'Max Duration',
            self::MAX_VOLUME => 'Max Volume',
            self::MAX_DISTANCE => 'Max Distance',
        };
    }

    /**
     * Get the unit for this record type.
     */
    public function unit(): string
    {
        return match ($this) {
            self::MAX_WEIGHT => 'kg',
            self::MAX_REPS => 'reps',
            self::MAX_DURATION => 'seconds',
            self::MAX_VOLUME => 'kg',
            self::MAX_DISTANCE => 'meters',
        };
    }
}
