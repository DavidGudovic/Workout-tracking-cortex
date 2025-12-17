<?php

namespace App\Domain\Execution;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SetLog Model
 *
 * Represents performance data for a single set within an exercise log.
 * Tracks reps, weight, duration, distance, RPE, and completion status.
 *
 * CRITICAL BUSINESS RULE:
 * - Logs are immutable after parent session is completed
 *
 * @property string $id UUID primary key
 * @property string $exercise_log_id
 * @property int $set_number
 * @property int|null $target_reps
 * @property int|null $actual_reps
 * @property int|null $target_duration_seconds
 * @property int|null $actual_duration_seconds
 * @property int|null $target_distance_meters
 * @property int|null $actual_distance_meters
 * @property float|null $weight_kg
 * @property int|null $rpe
 * @property bool $is_warmup
 * @property bool $is_failure
 * @property \Illuminate\Support\Carbon|null $completed_at
 */
class SetLog extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'set_logs';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\SetLogFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exercise_log_id',
        'set_number',
        'target_reps',
        'actual_reps',
        'target_duration_seconds',
        'actual_duration_seconds',
        'target_distance_meters',
        'actual_distance_meters',
        'weight_kg',
        'rpe',
        'is_warmup',
        'is_failure',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'set_number' => 'integer',
        'target_reps' => 'integer',
        'actual_reps' => 'integer',
        'target_duration_seconds' => 'integer',
        'actual_duration_seconds' => 'integer',
        'target_distance_meters' => 'integer',
        'actual_distance_meters' => 'integer',
        'weight_kg' => 'decimal:2',
        'rpe' => 'integer',
        'is_warmup' => 'boolean',
        'is_failure' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_warmup' => false,
        'is_failure' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the exercise log this set belongs to.
     */
    public function exerciseLog(): BelongsTo
    {
        return $this->belongsTo(ExerciseLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the volume for this set (weight x reps).
     */
    public function getVolumeAttribute(): ?float
    {
        if ($this->weight_kg && $this->actual_reps) {
            return round($this->weight_kg * $this->actual_reps, 2);
        }
        return null;
    }

    /**
     * Get the actual duration in minutes.
     */
    public function getActualDurationMinutesAttribute(): ?float
    {
        return $this->actual_duration_seconds
            ? round($this->actual_duration_seconds / 60, 2)
            : null;
    }

    /**
     * Get the actual distance in kilometers.
     */
    public function getActualDistanceKilometersAttribute(): ?float
    {
        return $this->actual_distance_meters
            ? round($this->actual_distance_meters / 1000, 2)
            : null;
    }

    /**
     * Get the weight in pounds.
     */
    public function getWeightPoundsAttribute(): ?float
    {
        return $this->weight_kg ? round($this->weight_kg * 2.20462, 2) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to sets for a specific exercise log.
     */
    public function scopeForExerciseLog($query, string $exerciseLogId)
    {
        return $query->where('exercise_log_id', $exerciseLogId);
    }

    /**
     * Scope to warmup sets.
     */
    public function scopeWarmup($query)
    {
        return $query->where('is_warmup', true);
    }

    /**
     * Scope to working sets (non-warmup).
     */
    public function scopeWorkingSets($query)
    {
        return $query->where('is_warmup', false);
    }

    /**
     * Scope to sets taken to failure.
     */
    public function scopeToFailure($query)
    {
        return $query->where('is_failure', true);
    }

    /**
     * Scope to completed sets.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Scope to sort sets by set number.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('set_number');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the set is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if this is a warmup set.
     */
    public function isWarmup(): bool
    {
        return $this->is_warmup;
    }

    /**
     * Check if this is a working set.
     */
    public function isWorkingSet(): bool
    {
        return !$this->is_warmup;
    }

    /**
     * Check if the set was taken to failure.
     */
    public function isToFailure(): bool
    {
        return $this->is_failure;
    }

    /**
     * Complete the set.
     */
    public function complete(): void
    {
        $this->update(['completed_at' => now()]);
    }

    /**
     * Check if the target was met.
     */
    public function targetMet(): bool
    {
        if ($this->target_reps && $this->actual_reps) {
            return $this->actual_reps >= $this->target_reps;
        }
        if ($this->target_duration_seconds && $this->actual_duration_seconds) {
            return $this->actual_duration_seconds >= $this->target_duration_seconds;
        }
        if ($this->target_distance_meters && $this->actual_distance_meters) {
            return $this->actual_distance_meters >= $this->target_distance_meters;
        }
        return false;
    }

    /**
     * Get the performance percentage vs target.
     */
    public function getPerformancePercentage(): ?float
    {
        if ($this->target_reps && $this->actual_reps) {
            return round(($this->actual_reps / $this->target_reps) * 100, 2);
        }
        if ($this->target_duration_seconds && $this->actual_duration_seconds) {
            return round(($this->actual_duration_seconds / $this->target_duration_seconds) * 100, 2);
        }
        if ($this->target_distance_meters && $this->actual_distance_meters) {
            return round(($this->actual_distance_meters / $this->target_distance_meters) * 100, 2);
        }
        return null;
    }

    /**
     * Get a summary string for this set.
     */
    public function getSummary(): string
    {
        $parts = [];

        if ($this->weight_kg) {
            $parts[] = "{$this->weight_kg}kg";
        }

        if ($this->actual_reps) {
            $parts[] = "{$this->actual_reps} reps";
        } elseif ($this->actual_duration_seconds) {
            $parts[] = "{$this->actual_duration_minutes}min";
        } elseif ($this->actual_distance_meters) {
            $parts[] = "{$this->actual_distance_meters}m";
        }

        if ($this->rpe) {
            $parts[] = "RPE {$this->rpe}";
        }

        if ($this->is_warmup) {
            $parts[] = "(Warmup)";
        }

        if ($this->is_failure) {
            $parts[] = "(Failure)";
        }

        return implode(' x ', $parts);
    }

    /**
     * Check if logs are immutable (parent session is completed).
     */
    public function logsAreImmutable(): bool
    {
        return $this->exerciseLog->logsAreImmutable();
    }
}
