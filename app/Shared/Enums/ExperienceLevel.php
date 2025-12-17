<?php

namespace App\Shared\Enums;

enum ExperienceLevel: string
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
     * Get a human-readable label for the level.
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
     * Get a description for the experience level.
     */
    public function description(): string
    {
        return match ($this) {
            self::BEGINNER => 'New to fitness or returning after a long break',
            self::INTERMEDIATE => '6+ months of consistent training',
            self::ADVANCED => '2+ years of consistent training',
            self::EXPERT => '5+ years of consistent training with competitive experience',
        };
    }
}
