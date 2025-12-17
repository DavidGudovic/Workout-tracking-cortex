<?php

namespace App\Shared\Enums;

enum Difficulty: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';
    case EXPERT = 'expert';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the difficulty.
     */
    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'Beginner',
            self::INTERMEDIATE => 'Intermediate',
            self::ADVANCED => 'Advanced',
            self::EXPERT => 'Expert',
        };
    }

    /**
     * Get a color code for UI representation.
     */
    public function color(): string
    {
        return match ($this) {
            self::BEGINNER => 'green',
            self::INTERMEDIATE => 'yellow',
            self::ADVANCED => 'orange',
            self::EXPERT => 'red',
        };
    }
}
