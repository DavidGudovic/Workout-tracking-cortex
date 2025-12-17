<?php

namespace App\Shared\Enums;

enum ContractType: string
{
    case SESSION_BASED = 'session_based';
    case TIME_BASED = 'time_based';

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
            self::SESSION_BASED => 'Session-Based',
            self::TIME_BASED => 'Time-Based',
        };
    }
}
