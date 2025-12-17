<?php

namespace App\Domain\Training;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ExerciseEquipment Model (Pivot Model)
 *
 * Represents the relationship between an exercise and equipment.
 * CRITICAL: This is the foundation of workout-gym compatibility checking.
 *
 * An exercise can have multiple equipment options (alternatives).
 * Each equipment can be marked as primary (main) or alternative.
 *
 * @property string $id UUID primary key
 * @property string $exercise_id
 * @property string $equipment_id
 * @property bool $is_primary
 * @property string|null $notes
 */
class ExerciseEquipment extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'exercise_equipment';

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
        'equipment_id',
        'is_primary',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_primary' => 'boolean',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_primary' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the exercise for this equipment link.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Get the equipment for this link.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only primary equipment.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to only alternative equipment.
     */
    public function scopeAlternatives($query)
    {
        return $query->where('is_primary', false);
    }

    /**
     * Scope to equipment for a specific exercise.
     */
    public function scopeForExercise($query, string $exerciseId)
    {
        return $query->where('exercise_id', $exerciseId);
    }

    /**
     * Scope to exercises using specific equipment.
     */
    public function scopeForEquipment($query, string $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is the primary equipment for the exercise.
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Check if this is an alternative equipment option.
     */
    public function isAlternative(): bool
    {
        return !$this->is_primary;
    }

    /**
     * Set this equipment as primary for the exercise.
     * Removes primary flag from other equipment for the same exercise.
     */
    public function makePrimary(): void
    {
        // Remove primary flag from other equipment for this exercise
        static::where('exercise_id', $this->exercise_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        // Set this as primary
        $this->update(['is_primary' => true]);
    }
}
