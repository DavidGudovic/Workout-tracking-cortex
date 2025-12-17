<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * HasUuid Trait
 *
 * Provides UUID primary key functionality for Eloquent models.
 * All models in the FitTrack system use UUIDs instead of auto-incrementing integers.
 *
 * Usage:
 * ```php
 * class User extends Model
 * {
 *     use HasUuid;
 * }
 * ```
 *
 * Database requirements:
 * - Primary key column must be UUID type (CHAR(36) or PostgreSQL UUID)
 * - Primary key must have default value: gen_random_uuid()
 */
trait HasUuid
{
    /**
     * Boot the trait.
     * Automatically generates UUID for new models if not already set.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the data type of the primary key ID.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
