<?php

namespace App\Domain\Execution;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\TrainingPlan;
use App\Domain\Training\Workout;
use App\Shared\Enums\SessionStatus;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WorkoutSession Model
 *
 * Represents an instance of a trainee performing a workout.
 * Tracks performance data, duration, and completion status.
 *
 * CRITICAL BUSINESS RULE:
 * - Logs are immutable after session is completed
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property string $workout_id
 * @property int $workout_version
 * @property string|null $training_plan_id
 * @property int|null $training_plan_week_number
 * @property int|null $training_plan_day_number
 * @property SessionStatus $status
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int|null $total_duration_seconds
 * @property float|null $total_volume_kg
 * @property string|null $notes
 * @property int|null $rating
 * @property \Illuminate\Support\Carbon $created_at
 */
class WorkoutSession extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'workout_sessions';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\WorkoutSessionFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainee_id',
        'workout_id',
        'workout_version',
        'training_plan_id',
        'training_plan_week_number',
        'training_plan_day_number',
        'status',
        'started_at',
        'completed_at',
        'total_duration_seconds',
        'total_volume_kg',
        'notes',
        'rating',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'workout_version' => 'integer',
        'training_plan_week_number' => 'integer',
        'training_plan_day_number' => 'integer',
        'status' => SessionStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_duration_seconds' => 'integer',
        'total_volume_kg' => 'decimal:2',
        'rating' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'started',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee performing this workout.
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(TraineeProfile::class, 'trainee_id');
    }

    /**
     * Get the workout being performed.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the training plan this session is part of (if any).
     */
    public function trainingPlan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class);
    }

    /**
     * Get the exercise logs for this session.
     */
    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the duration in minutes.
     */
    public function getTotalDurationMinutesAttribute(): ?float
    {
        return $this->total_duration_seconds
            ? round($this->total_duration_seconds / 60, 2)
            : null;
    }

    /**
     * Get the duration as a formatted string.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->total_duration_seconds) {
            return null;
        }

        $hours = floor($this->total_duration_seconds / 3600);
        $minutes = floor(($this->total_duration_seconds % 3600) / 60);
        $seconds = $this->total_duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        } else {
            return sprintf('%ds', $seconds);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to sessions for a specific trainee.
     */
    public function scopeForTrainee($query, string $traineeId)
    {
        return $query->where('trainee_id', $traineeId);
    }

    /**
     * Scope to sessions for a specific workout.
     */
    public function scopeForWorkout($query, string $workoutId)
    {
        return $query->where('workout_id', $workoutId);
    }

    /**
     * Scope to completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', SessionStatus::COMPLETED);
    }

    /**
     * Scope to in-progress sessions.
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [SessionStatus::STARTED, SessionStatus::IN_PROGRESS]);
    }

    /**
     * Scope to sessions within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Scope to sort sessions by most recent.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('started_at', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === SessionStatus::COMPLETED;
    }

    /**
     * Check if the session is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === SessionStatus::IN_PROGRESS ||
               $this->status === SessionStatus::STARTED;
    }

    /**
     * Check if the session is abandoned.
     */
    public function isAbandoned(): bool
    {
        return $this->status === SessionStatus::ABANDONED;
    }

    /**
     * Check if the session is part of a training plan.
     */
    public function isPartOfTrainingPlan(): bool
    {
        return $this->training_plan_id !== null;
    }

    /**
     * Complete the session.
     */
    public function complete(): void
    {
        $this->completed_at = now();
        $this->calculateTotals();

        $this->update([
            'status' => SessionStatus::COMPLETED,
            'completed_at' => $this->completed_at,
        ]);
    }

    /**
     * Abandon the session.
     */
    public function abandon(): void
    {
        $this->update([
            'status' => SessionStatus::ABANDONED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Calculate and update total duration and volume.
     */
    public function calculateTotals(): void
    {
        // Calculate total duration
        if ($this->started_at && $this->completed_at) {
            $this->total_duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        }

        // Calculate total volume (sum of all sets)
        $totalVolume = 0;
        foreach ($this->exerciseLogs as $exerciseLog) {
            foreach ($exerciseLog->setLogs as $setLog) {
                if ($setLog->weight_kg && $setLog->actual_reps) {
                    $totalVolume += $setLog->weight_kg * $setLog->actual_reps;
                }
            }
        }
        $this->total_volume_kg = $totalVolume;

        $this->save();
    }

    /**
     * Get the completion percentage (exercises completed vs total).
     */
    public function getCompletionPercentage(): float
    {
        $totalExercises = $this->workout->workoutExercises()->count();
        if ($totalExercises === 0) {
            return 0;
        }

        $completedExercises = $this->exerciseLogs()
            ->where('status', \App\Shared\Enums\ExerciseLogStatus::COMPLETED)
            ->count();

        return round(($completedExercises / $totalExercises) * 100, 2);
    }

    /**
     * Check if logs are immutable (session is completed).
     */
    public function logsAreImmutable(): bool
    {
        return $this->isCompleted();
    }
}
