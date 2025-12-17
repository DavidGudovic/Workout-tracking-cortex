<?php

namespace App\Domain\Training;

use App\Shared\Enums\MediaType;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExerciseMedia Model
 *
 * Represents media (videos, images, GIFs) attached to an exercise.
 * Used for demonstration and instructional purposes.
 *
 * @property string $id UUID primary key
 * @property string $exercise_id
 * @property MediaType $type
 * @property string $url
 * @property string|null $title
 * @property bool $is_primary
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 */
class ExerciseMedia extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'exercise_media';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exercise_id',
        'type',
        'url',
        'title',
        'is_primary',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => MediaType::class,
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_primary' => false,
        'sort_order' => 0,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the exercise this media belongs to.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only primary media.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to media for a specific exercise.
     */
    public function scopeForExercise($query, string $exerciseId)
    {
        return $query->where('exercise_id', $exerciseId);
    }

    /**
     * Scope to filter by media type.
     */
    public function scopeByType($query, MediaType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to only videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('type', MediaType::VIDEO_URL);
    }

    /**
     * Scope to only images (including GIFs).
     */
    public function scopeImages($query)
    {
        return $query->whereIn('type', [MediaType::IMAGE_URL, MediaType::GIF_URL]);
    }

    /**
     * Scope to sort media by sort_order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is the primary media for the exercise.
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Check if this media is a video.
     */
    public function isVideo(): bool
    {
        return $this->type->isVideo();
    }

    /**
     * Check if this media is an image.
     */
    public function isImage(): bool
    {
        return $this->type->isImage();
    }

    /**
     * Set this media as primary for the exercise.
     * Removes primary flag from other media for the same exercise.
     */
    public function makePrimary(): void
    {
        // Remove primary flag from other media for this exercise
        static::where('exercise_id', $this->exercise_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $this->update(['is_primary' => true]);
    }

    /**
     * Get the thumbnail URL for this media.
     * For videos, this might need external service integration.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->type === MediaType::VIDEO_URL) {
            // For now, return the video URL
            // In production, you might want to extract thumbnail from video service
            return $this->url;
        }

        return $this->url;
    }
}
