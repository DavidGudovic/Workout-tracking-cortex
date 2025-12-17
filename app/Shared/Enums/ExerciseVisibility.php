<?php

namespace App\Shared\Enums;

enum ExerciseVisibility: string
{
    case PRIVATE = 'private';
    case PUBLIC_POOL = 'public_pool';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the visibility.
     */
    public function label(): string
    {
        return match ($this) {
            self::PRIVATE => 'Private',
            self::PUBLIC_POOL => 'Public Pool',
        };
    }

    /**
     * Get a description for the visibility.
     */
    public function description(): string
    {
        return match ($this) {
            self::PRIVATE => 'Only visible to the creator',
            self::PUBLIC_POOL => 'Available for all trainers to use',
        };
    }
}
