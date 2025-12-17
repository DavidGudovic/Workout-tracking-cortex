<?php

namespace App\Shared\Enums;

enum EquipmentCategory: string
{
    case FREE_WEIGHTS = 'free_weights';
    case MACHINES = 'machines';
    case CARDIO = 'cardio';
    case BODYWEIGHT = 'bodyweight';
    case ACCESSORIES = 'accessories';
    case CABLE = 'cable';
    case PLYOMETRIC = 'plyometric';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE_WEIGHTS => 'Free Weights',
            self::MACHINES => 'Machines',
            self::CARDIO => 'Cardio Equipment',
            self::BODYWEIGHT => 'Bodyweight',
            self::ACCESSORIES => 'Accessories',
            self::CABLE => 'Cable Systems',
            self::PLYOMETRIC => 'Plyometric Equipment',
        };
    }
}
