<?php

namespace App\Domain\Training;

use App\Domain\Identity\TrainerProfile;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\ExerciseType;
use App\Shared\Enums\ExerciseVisibility;
use App\Shared\Enums\PerformanceType;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Exercise Model
 *
 * Represents a physical exercise (movement) in the system.
 *
 * CRITICAL BUSINESS RULES:
 * - System exercises (type='system') are immutable - only admins can modify
 * - Custom exercises (type='custom') can be created by trainers
 * - Exercises can be private or shared to public pool
 * - Each exercise links to compatible equipment (many-to-many)
 *
 * @property string $id UUID primary key
 * @property string|null $creator_id
 * @property ExerciseType $type
 * @property ExerciseVisibility $visibility
 * @property string $name
 * @property string|null $description
 * @property string|null $instructions
 * @property PerformanceType $exercise_type
 * @property Difficulty $difficulty
 * @property array|null $primary_muscle_groups
 * @property array|null $secondary_muscle_groups
 * @property bool $is_compound
 * @property float|null $calories_per_minute
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class Exercise extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'exercises';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ExerciseFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_id',
        'type',
        'visibility',
        'name',
        'description',
        'instructions',
        'exercise_type',
        'difficulty',
        'primary_muscle_groups',
        'secondary_muscle_groups',
        'is_compound',
        'calories_per_minute',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => ExerciseType::class,
        'visibility' => ExerciseVisibility::class,
        'exercise_type' => PerformanceType::class,
        'difficulty' => Difficulty::class,
        'primary_muscle_groups' => 'array',
        'secondary_muscle_groups' => 'array',
        'is_compound' => 'boolean',
        'calories_per_minute' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'custom',
        'visibility' => 'private',
        'is_compound' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainer who created this exercise.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class, 'creator_id');
    }

    /**
     * Get the equipment that can be used for this exercise.
     */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(
            Equipment::class,
            'exercise_equipment',
            'exercise_id',
            'equipment_id'
        )->withPivot(['id', 'is_primary', 'notes']);
    }

    /**
     * Get the exercise equipment pivot records.
     */
    public function exerciseEquipment(): HasMany
    {
        return $this->hasMany(ExerciseEquipment::class);
    }

    /**
     * Get the media for this exercise.
     */
    public function media(): HasMany
    {
        return $this->hasMany(ExerciseMedia::class)->orderBy('sort_order');
    }

    /**
     * Get the workout exercises that use this exercise.
     */
    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the primary media for this exercise.
     */
    public function getPrimaryMediaAttribute(): ?ExerciseMedia
    {
        return $this->media()->where('is_primary', true)->first()
            ?? $this->media()->first();
    }

    /**
     * Get all muscle groups (primary + secondary combined).
     */
    public function getAllMuscleGroupsAttribute(): array
    {
        return array_unique(array_merge(
            $this->primary_muscle_groups ?? [],
            $this->secondary_muscle_groups ?? []
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only system exercises.
     */
    public function scopeSystem($query)
    {
        return $query->where('type', ExerciseType::SYSTEM);
    }

    /**
     * Scope to only custom exercises.
     */
    public function scopeCustom($query)
    {
        return $query->where('type', ExerciseType::CUSTOM);
    }

    /**
     * Scope to only public exercises.
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', ExerciseVisibility::PUBLIC_POOL);
    }

    /**
     * Scope to only private exercises.
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', ExerciseVisibility::PRIVATE);
    }

    /**
     * Scope to only published exercises.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    /**
     * Scope to exercises created by a specific trainer.
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
     * Scope to filter by performance type.
     */
    public function scopeByPerformanceType($query, PerformanceType $type)
    {
        return $query->where('exercise_type', $type);
    }

    /**
     * Scope to filter by muscle group (primary or secondary).
     */
    public function scopeByMuscleGroup($query, string $muscleGroup)
    {
        return $query->where(function ($q) use ($muscleGroup) {
            $q->whereJsonContains('primary_muscle_groups', $muscleGroup)
              ->orWhereJsonContains('secondary_muscle_groups', $muscleGroup);
        });
    }

    /**
     * Scope to only compound exercises.
     */
    public function scopeCompound($query)
    {
        return $query->where('is_compound', true);
    }

    /**
     * Scope to search exercises by name or description.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('description', 'ilike', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is a system exercise.
     */
    public function isSystem(): bool
    {
        return $this->type === ExerciseType::SYSTEM;
    }

    /**
     * Check if this is a custom exercise.
     */
    public function isCustom(): bool
    {
        return $this->type === ExerciseType::CUSTOM;
    }

    /**
     * Check if this exercise is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === ExerciseVisibility::PUBLIC_POOL;
    }

    /**
     * Check if this exercise is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    /**
     * Check if this exercise is compound.
     */
    public function isCompound(): bool
    {
        return $this->is_compound;
    }

    /**
     * Check if the exercise targets a specific muscle group.
     */
    public function targetsMuscleGroup(string $muscleGroup): bool
    {
        return in_array($muscleGroup, $this->all_muscle_groups);
    }

    /**
     * Publish the exercise.
     */
    public function publish(): void
    {
        $this->update(['published_at' => now()]);
    }

    /**
     * Unpublish the exercise.
     */
    public function unpublish(): void
    {
        $this->update(['published_at' => null]);
    }

    /**
     * Get the equipment IDs for this exercise.
     * Used for compatibility checking.
     */
    public function getEquipmentIds(): array
    {
        return $this->equipment()->pluck('equipment_id')->toArray();
    }
}
