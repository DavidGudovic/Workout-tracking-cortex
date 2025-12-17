<?php

namespace App\Shared\Enums;

enum TrainerRole: string
{
    case STAFF_TRAINER = 'staff_trainer';
    case HEAD_TRAINER = 'head_trainer';
    case CONTRACTOR = 'contractor';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::STAFF_TRAINER => 'Staff Trainer',
            self::HEAD_TRAINER => 'Head Trainer',
            self::CONTRACTOR => 'Contractor',
        };
    }

    /**
     * Get a description for the role.
     */
    public function description(): string
    {
        return match ($this) {
            self::STAFF_TRAINER => 'Regular gym staff trainer',
            self::HEAD_TRAINER => 'Lead trainer with management responsibilities',
            self::CONTRACTOR => 'Independent contractor',
        };
    }
}
