<?php

namespace App\Shared\Enums;

enum GymTrainerStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case TERMINATED = 'terminated';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACTIVE => 'Active',
            self::TERMINATED => 'Terminated',
        };
    }
}
