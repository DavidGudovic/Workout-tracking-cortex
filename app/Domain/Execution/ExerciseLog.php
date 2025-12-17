<?php

namespace App\Domain\Execution;

use App\Domain\Training\Exercise;
use App\Domain\Training\WorkoutExercise;
use App\Shared\Enums\ExerciseLogStatus;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ExerciseLog Model
 *
 * Represents performance data for a specific exercise within a workout session.
 * Contains multiple set logs.
 *
 * CRITICAL BUSINESS RULE:
 * - Logs are immutable after parent session is completed
 *
 * @property string $id UUID primary key
 * @property string $workout_session_id
 * @property string $workout_exercise_id
 * @property string $exercise_id
 * @property ExerciseLogStatus $status
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 */
class ExerciseLog extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'exercise_logs';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ExerciseLogFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'workout_session_id',
        'workout_exercise_id',
        'exercise_id',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ExerciseLogStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the workout session this log belongs to.
     */
    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    /**
     * Get the workout exercise configuration.
     */
    public function workoutExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutExercise::class);
    }

    /**
     * Get the exercise being performed.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Get the set logs for this exercise.
     */
    public function setLogs(): HasMany
    {
        return $this->hasMany(SetLog::class)->orderBy('set_number');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the duration in seconds for this exercise.
     */
    public function getDurationSecondsAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInSeconds($this->completed_at);
        }
        return null;
    }

    /**
     * Get the duration in minutes for this exercise.
     */
    public function getDurationMinutesAttribute(): ?float
    {
        $seconds = $this->duration_seconds;
        return $seconds ? round($seconds / 60, 2) : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to logs for a specific session.
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('workout_session_id', $sessionId);
    }

    /**
     * Scope to completed exercise logs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', ExerciseLogStatus::COMPLETED);
    }

    /**
     * Scope to skipped exercise logs.
     */
    public function scopeSkipped($query)
    {
        return $query->where('status', ExerciseLogStatus::SKIPPED);
    }

    /**
     * Scope to in-progress exercise logs.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', ExerciseLogStatus::IN_PROGRESS);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the exercise log is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ExerciseLogStatus::COMPLETED;
    }

    /**
     * Check if the exercise log is skipped.
     */
    public function isSkipped(): bool
    {
        return $this->status === ExerciseLogStatus::SKIPPED;
    }

    /**
     * Check if the exercise log is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === ExerciseLogStatus::IN_PROGRESS;
    }

    /**
     * Start the exercise.
     */
    public function start(): void
    {
        $this->update([
            'status' => ExerciseLogStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the exercise.
     */
    public function complete(): void
    {
        $this->update([
            'status' => ExerciseLogStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Skip the exercise.
     */
    public function skip(): void
    {
        $this->update([
            'status' => ExerciseLogStatus::SKIPPED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get the total volume for this exercise (sum of all sets).
     */
    public function getTotalVolume(): float
    {
        $total = 0;
        foreach ($this->setLogs as $setLog) {
            if ($setLog->weight_kg && $setLog->actual_reps) {
                $total += $setLog->weight_kg * $setLog->actual_reps;
            }
        }
        return round($total, 2);
    }

    /**
     * Get the average RPE for this exercise.
     */
    public function getAverageRpe(): ?float
    {
        $sets = $this->setLogs->where('rpe', '!=', null);
        if ($sets->isEmpty()) {
            return null;
        }

        return round($sets->avg('rpe'), 1);
    }

    /**
     * Get the number of sets completed.
     */
    public function getSetsCompleted(): int
    {
        return $this->setLogs()->whereNotNull('completed_at')->count();
    }

    /**
     * Get the target number of sets from workout exercise.
     */
    public function getTargetSets(): int
    {
        return $this->workoutExercise->sets;
    }

    /**
     * Check if all sets are completed.
     */
    public function allSetsCompleted(): bool
    {
        return $this->getSetsCompleted() >= $this->getTargetSets();
    }

    /**
     * Check if logs are immutable (parent session is completed).
     */
    public function logsAreImmutable(): bool
    {
        return $this->workoutSession->logsAreImmutable();
    }
}
