<?php

namespace App\Shared\Enums;

enum ExerciseType: string
{
    case SYSTEM = 'system';
    case CUSTOM = 'custom';

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
            self::SYSTEM => 'System Exercise',
            self::CUSTOM => 'Custom Exercise',
        };
    }
}
