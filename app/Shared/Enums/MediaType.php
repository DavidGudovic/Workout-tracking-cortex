<?php

namespace App\Shared\Enums;

enum MediaType: string
{
    case VIDEO_URL = 'video_url';
    case IMAGE_URL = 'image_url';
    case GIF_URL = 'gif_url';

    /**
     * Get all available values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the media type.
     */
    public function label(): string
    {
        return match ($this) {
            self::VIDEO_URL => 'Video',
            self::IMAGE_URL => 'Image',
            self::GIF_URL => 'Animated GIF',
        };
    }

    /**
     * Check if the media type is a video.
     */
    public function isVideo(): bool
    {
        return $this === self::VIDEO_URL;
    }

    /**
     * Check if the media type is an image.
     */
    public function isImage(): bool
    {
        return $this === self::IMAGE_URL || $this === self::GIF_URL;
    }
}
