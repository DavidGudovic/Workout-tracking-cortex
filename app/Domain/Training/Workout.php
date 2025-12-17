<?php

namespace App\Domain\Training;

use App\Domain\Identity\TrainerProfile;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\PricingType;
use App\Shared\Enums\WorkoutStatus;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Workout Model
 *
 * Represents a workout (collection of exercises) created by a trainer.
 *
 * CRITICAL BUSINESS RULES:
 * - Premium workouts must have price > 0
 * - Published workouts create versions on edit (versioning system)
 * - Workout-gym compatibility: ALL exercises must have compatible equipment at gym
 *
 * @property string $id UUID primary key
 * @property string $creator_id
 * @property string $name
 * @property string|null $description
 * @property string|null $cover_image_url
 * @property Difficulty $difficulty
 * @property int|null $estimated_duration_minutes
 * @property PricingType $pricing_type
 * @property int|null $price_cents
 * @property string $currency
 * @property WorkoutStatus $status
 * @property int $version
 * @property array|null $tags
 * @property int $total_exercises
 * @property int $total_sets
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class Workout extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'workouts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_id',
        'name',
        'description',
        'cover_image_url',
        'difficulty',
        'estimated_duration_minutes',
        'pricing_type',
        'price_cents',
        'currency',
        'status',
        'version',
        'tags',
        'total_exercises',
        'total_sets',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'difficulty' => Difficulty::class,
        'estimated_duration_minutes' => 'integer',
        'pricing_type' => PricingType::class,
        'price_cents' => 'integer',
        'status' => WorkoutStatus::class,
        'version' => 'integer',
        'tags' => 'array',
        'total_exercises' => 'integer',
        'total_sets' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'pricing_type' => 'free',
        'currency' => 'USD',
        'status' => 'draft',
        'version' => 1,
        'total_exercises' => 0,
        'total_sets' => 0,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainer who created this workout.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class, 'creator_id');
    }

    /**
     * Get the exercises in this workout.
     */
    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('sort_order');
    }

    /**
     * Get the workout sessions performed using this workout.
     */
    public function workoutSessions(): HasMany
    {
        return $this->hasMany(\App\Domain\Execution\WorkoutSession::class);
    }

    /**
     * Get the workout purchases for this workout.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\WorkoutPurchase::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the workout's price in dollars.
     */
    public function getPriceDollarsAttribute(): ?float
    {
        return $this->price_cents ? $this->price_cents / 100 : null;
    }

    /**
     * Set the workout's price from dollars.
     */
    public function setPriceDollarsAttribute(float $value): void
    {
        $this->price_cents = (int) ($value * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only published workouts.
     */
    public function scopePublished($query)
    {
        return $query->where('status', WorkoutStatus::PUBLISHED);
    }

    /**
     * Scope to only draft workouts.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', WorkoutStatus::DRAFT);
    }

    /**
     * Scope to only archived workouts.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', WorkoutStatus::ARCHIVED);
    }

    /**
     * Scope to only free workouts.
     */
    public function scopeFree($query)
    {
        return $query->where('pricing_type', PricingType::FREE);
    }

    /**
     * Scope to only premium workouts.
     */
    public function scopePremium($query)
    {
        return $query->where('pricing_type', PricingType::PREMIUM);
    }

    /**
     * Scope to workouts created by a specific trainer.
     */
    public function scopeByCreator($query, string $creatorId)
    {
        return $query->where('creator_id', $creatorId);
    }

    /**
     * Scope to filter by difficulty.
     */
    public function scopeByDifficulty($query, Difficulty $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope to search workouts by name or description.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('description', 'ilike', "%{$search}%");
        });
    }

    /**
     * Scope to filter by tag.
     */
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the workout is published.
     */
    public function isPublished(): bool
    {
        return $this->status === WorkoutStatus::PUBLISHED;
    }

    /**
     * Check if the workout is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === WorkoutStatus::DRAFT;
    }

    /**
     * Check if the workout is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === WorkoutStatus::ARCHIVED;
    }

    /**
     * Check if the workout is free.
     */
    public function isFree(): bool
    {
        return $this->pricing_type === PricingType::FREE;
    }

    /**
     * Check if the workout is premium.
     */
    public function isPremium(): bool
    {
        return $this->pricing_type === PricingType::PREMIUM;
    }

    /**
     * Publish the workout.
     */
    public function publish(): void
    {
        $this->update([
            'status' => WorkoutStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Archive the workout.
     */
    public function archive(): void
    {
        $this->update(['status' => WorkoutStatus::ARCHIVED]);
    }

    /**
     * Revert to draft.
     */
    public function revertToDraft(): void
    {
        $this->update(['status' => WorkoutStatus::DRAFT]);
    }

    /**
     * Update total exercises and sets count.
     * Should be called after adding/removing exercises.
     */
    public function updateTotals(): void
    {
        $this->update([
            'total_exercises' => $this->workoutExercises()->count(),
            'total_sets' => $this->workoutExercises()->sum('sets'),
        ]);
    }

    /**
     * Check if workout has a specific tag.
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags ?? []);
    }

    /**
     * Check workout-gym compatibility.
     * Returns true if ALL exercises have compatible equipment at the gym.
     *
     * Algorithm: For each exercise in workout, check if gym has
     * at least one compatible equipment.
     */
    public function isCompatibleWithGym(string $gymId): bool
    {
        // Get gym equipment IDs
        $gym = \App\Domain\Gym\Gym::find($gymId);
        if (!$gym) {
            return false;
        }

        $gymEquipmentIds = $gym->getEquipmentIds();

        // Check each exercise
        foreach ($this->workoutExercises as $workoutExercise) {
            $exerciseEquipmentIds = $workoutExercise->exercise->getEquipmentIds();

            // Check if there's any intersection
            if (empty(array_intersect($gymEquipmentIds, $exerciseEquipmentIds))) {
                return false; // No compatible equipment for this exercise
            }
        }

        return true;
    }

    /**
     * Get all equipment IDs required for this workout.
     */
    public function getRequiredEquipmentIds(): array
    {
        $equipmentIds = [];

        foreach ($this->workoutExercises as $workoutExercise) {
            $equipmentIds = array_merge(
                $equipmentIds,
                $workoutExercise->exercise->getEquipmentIds()
            );
        }

        return array_unique($equipmentIds);
    }
}
