<?php

namespace App\Shared\Enums;

enum FitnessGoal: string
{
    case STRENGTH = 'strength';
    case HYPERTROPHY = 'hypertrophy';
    case ENDURANCE = 'endurance';
    case WEIGHT_LOSS = 'weight_loss';
    case GENERAL_FITNESS = 'general_fitness';
    case SPORT_SPECIFIC = 'sport_specific';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the goal.
     */
    public function label(): string
    {
        return match ($this) {
            self::STRENGTH => 'Build Strength',
            self::HYPERTROPHY => 'Build Muscle (Hypertrophy)',
            self::ENDURANCE => 'Improve Endurance',
            self::WEIGHT_LOSS => 'Lose Weight',
            self::GENERAL_FITNESS => 'General Fitness',
            self::SPORT_SPECIFIC => 'Sport-Specific Training',
        };
    }
}
