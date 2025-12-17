<?php

namespace App\Domain\Training;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkoutExercise Model
 *
 * Represents an exercise within a workout with its specific parameters.
 * This is the junction table between workouts and exercises with extra attributes.
 *
 * CRITICAL BUSINESS RULE:
 * - At least one target must be set (reps, duration, or distance)
 * - CHECK constraint enforced at database level
 *
 * @property string $id UUID primary key
 * @property string $workout_id
 * @property string $exercise_id
 * @property int $sort_order
 * @property int $sets
 * @property int|null $target_reps
 * @property int|null $target_duration_seconds
 * @property int|null $target_distance_meters
 * @property int $rest_seconds
 * @property string|null $notes
 * @property int|null $superset_group
 * @property bool $is_optional
 * @property \Illuminate\Support\Carbon $created_at
 */
class WorkoutExercise extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'workout_exercises';

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
        'workout_id',
        'exercise_id',
        'sort_order',
        'sets',
        'target_reps',
        'target_duration_seconds',
        'target_distance_meters',
        'rest_seconds',
        'notes',
        'superset_group',
        'is_optional',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'sets' => 'integer',
        'target_reps' => 'integer',
        'target_duration_seconds' => 'integer',
        'target_distance_meters' => 'integer',
        'rest_seconds' => 'integer',
        'superset_group' => 'integer',
        'is_optional' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sets' => 1,
        'rest_seconds' => 60,
        'is_optional' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update workout totals after creating/deleting workout exercises
        static::created(function ($workoutExercise) {
            $workoutExercise->workout->updateTotals();
        });

        static::deleted(function ($workoutExercise) {
            $workoutExercise->workout->updateTotals();
        });

        static::updated(function ($workoutExercise) {
            if ($workoutExercise->wasChanged('sets')) {
                $workoutExercise->workout->updateTotals();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the workout this exercise belongs to.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the exercise for this workout exercise.
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
     * Scope to exercises in a specific workout.
     */
    public function scopeForWorkout($query, string $workoutId)
    {
        return $query->where('workout_id', $workoutId);
    }

    /**
     * Scope to only optional exercises.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    /**
     * Scope to only required exercises.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_optional', false);
    }

    /**
     * Scope to exercises in a specific superset group.
     */
    public function scopeInSupersetGroup($query, int $group)
    {
        return $query->where('superset_group', $group);
    }

    /**
     * Scope to exercises that are part of supersets.
     */
    public function scopeSupersets($query)
    {
        return $query->whereNotNull('superset_group');
    }

    /**
     * Scope to sort exercises by sort_order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the target duration in minutes.
     */
    public function getTargetDurationMinutesAttribute(): ?float
    {
        return $this->target_duration_seconds
            ? round($this->target_duration_seconds / 60, 2)
            : null;
    }

    /**
     * Get the target distance in kilometers.
     */
    public function getTargetDistanceKilometersAttribute(): ?float
    {
        return $this->target_distance_meters
            ? round($this->target_distance_meters / 1000, 2)
            : null;
    }

    /**
     * Get the rest duration in minutes.
     */
    public function getRestMinutesAttribute(): float
    {
        return round($this->rest_seconds / 60, 2);
    }

    /**
     * Get the estimated time for this exercise (including rest).
     */
    public function getEstimatedTimeSecondsAttribute(): int
    {
        $exerciseTime = 0;

        if ($this->target_duration_seconds) {
            $exerciseTime = $this->target_duration_seconds * $this->sets;
        } elseif ($this->target_reps) {
            // Rough estimate: 3 seconds per rep
            $exerciseTime = $this->target_reps * $this->sets * 3;
        } elseif ($this->target_distance_meters) {
            // Rough estimate: 1 minute per 200 meters
            $exerciseTime = ($this->target_distance_meters / 200) * 60 * $this->sets;
        }

        // Add rest time between sets (not after last set)
        $restTime = $this->rest_seconds * ($this->sets - 1);

        return (int) ($exerciseTime + $restTime);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this exercise is optional.
     */
    public function isOptional(): bool
    {
        return $this->is_optional;
    }

    /**
     * Check if this exercise is part of a superset.
     */
    public function isSuperset(): bool
    {
        return $this->superset_group !== null;
    }

    /**
     * Check if this exercise uses reps for tracking.
     */
    public function usesReps(): bool
    {
        return $this->target_reps !== null;
    }

    /**
     * Check if this exercise uses duration for tracking.
     */
    public function usesDuration(): bool
    {
        return $this->target_duration_seconds !== null;
    }

    /**
     * Check if this exercise uses distance for tracking.
     */
    public function usesDistance(): bool
    {
        return $this->target_distance_meters !== null;
    }

    /**
     * Get the performance type for this workout exercise.
     */
    public function getPerformanceType(): string
    {
        if ($this->target_reps) {
            return 'repetition';
        } elseif ($this->target_duration_seconds) {
            return 'duration';
        } elseif ($this->target_distance_meters) {
            return 'distance';
        }

        return 'unknown';
    }

    /**
     * Get a human-readable target description.
     */
    public function getTargetDescription(): string
    {
        $parts = [];

        $parts[] = "{$this->sets} sets";

        if ($this->target_reps) {
            $parts[] = "{$this->target_reps} reps";
        } elseif ($this->target_duration_seconds) {
            $minutes = floor($this->target_duration_seconds / 60);
            $seconds = $this->target_duration_seconds % 60;
            if ($minutes > 0) {
                $parts[] = "{$minutes}m {$seconds}s";
            } else {
                $parts[] = "{$seconds}s";
            }
        } elseif ($this->target_distance_meters) {
            if ($this->target_distance_meters >= 1000) {
                $km = $this->target_distance_kilometers;
                $parts[] = "{$km}km";
            } else {
                $parts[] = "{$this->target_distance_meters}m";
            }
        }

        $parts[] = "{$this->rest_seconds}s rest";

        return implode(' x ', $parts);
    }
}
