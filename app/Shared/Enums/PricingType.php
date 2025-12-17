<?php

namespace App\Shared\Enums;

enum PricingType: string
{
    case FREE = 'free';
    case PREMIUM = 'premium';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the pricing type.
     */
    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PREMIUM => 'Premium',
        };
    }
}
