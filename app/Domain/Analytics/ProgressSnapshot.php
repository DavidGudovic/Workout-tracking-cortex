<?php

namespace App\Domain\Analytics;

use App\Domain\Identity\TraineeProfile;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProgressSnapshot Model
 *
 * Represents a daily snapshot of a trainee's progress and statistics.
 * Used for tracking trends and generating analytics.
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property \Illuminate\Support\Carbon $snapshot_date
 * @property int $total_workouts_completed
 * @property int $total_workouts_started
 * @property float $completion_rate
 * @property float $total_volume_kg
 * @property int $total_duration_minutes
 * @property int $total_reps
 * @property int $active_training_plans
 * @property int $current_streak_days
 * @property \Illuminate\Support\Carbon $created_at
 */
class ProgressSnapshot extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'progress_snapshots';

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
        'trainee_id',
        'snapshot_date',
        'total_workouts_completed',
        'total_workouts_started',
        'completion_rate',
        'total_volume_kg',
        'total_duration_minutes',
        'total_reps',
        'active_training_plans',
        'current_streak_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'snapshot_date' => 'date',
        'total_workouts_completed' => 'integer',
        'total_workouts_started' => 'integer',
        'completion_rate' => 'decimal:2',
        'total_volume_kg' => 'decimal:2',
        'total_duration_minutes' => 'integer',
        'total_reps' => 'integer',
        'active_training_plans' => 'integer',
        'current_streak_days' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'total_workouts_completed' => 0,
        'total_workouts_started' => 0,
        'completion_rate' => 0,
        'total_volume_kg' => 0,
        'total_duration_minutes' => 0,
        'total_reps' => 0,
        'active_training_plans' => 0,
        'current_streak_days' => 0,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee this snapshot belongs to.
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(TraineeProfile::class, 'trainee_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the total duration in hours.
     */
    public function getTotalDurationHoursAttribute(): float
    {
        return round($this->total_duration_minutes / 60, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to snapshots for a specific trainee.
     */
    public function scopeForTrainee($query, string $traineeId)
    {
        return $query->where('trainee_id', $traineeId);
    }

    /**
     * Scope to snapshots within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    /**
     * Scope to recent snapshots.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('snapshot_date', '>=', now()->subDays($days))
            ->orderBy('snapshot_date', 'desc');
    }

    /**
     * Scope to sort by date.
     */
    public function scopeSortedByDate($query, string $direction = 'asc')
    {
        return $query->orderBy('snapshot_date', $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Create or update today's snapshot for a trainee.
     */
    public static function createOrUpdateToday(string $traineeId): self
    {
        $trainee = TraineeProfile::findOrFail($traineeId);
        $today = now()->toDateString();

        // Calculate stats
        $stats = static::calculateStats($trainee, $today);

        return static::updateOrCreate(
            [
                'trainee_id' => $traineeId,
                'snapshot_date' => $today,
            ],
            $stats
        );
    }

    /**
     * Calculate statistics for a trainee on a given date.
     */
    protected static function calculateStats(TraineeProfile $trainee, string $date): array
    {
        // Get all workout sessions up to this date
        $completedSessions = $trainee->workoutSessions()
            ->where('status', 'completed')
            ->whereDate('completed_at', '<=', $date)
            ->get();

        $startedSessions = $trainee->workoutSessions()
            ->whereDate('started_at', '<=', $date)
            ->get();

        $completionRate = $startedSessions->count() > 0
            ? round(($completedSessions->count() / $startedSessions->count()) * 100, 2)
            : 0;

        $totalVolume = $completedSessions->sum('total_volume_kg') ?? 0;
        $totalDuration = $completedSessions->sum(function ($session) {
            return $session->total_duration_seconds ? $session->total_duration_seconds / 60 : 0;
        });

        // Calculate total reps
        $totalReps = 0;
        foreach ($completedSessions as $session) {
            foreach ($session->exerciseLogs as $exerciseLog) {
                $totalReps += $exerciseLog->setLogs->sum('actual_reps') ?? 0;
            }
        }

        // Active training plans
        $activePlans = $trainee->activePlan()->exists() ? 1 : 0;

        // Current streak
        $currentStreak = $trainee->getCurrentStreak();

        return [
            'total_workouts_completed' => $completedSessions->count(),
            'total_workouts_started' => $startedSessions->count(),
            'completion_rate' => $completionRate,
            'total_volume_kg' => $totalVolume,
            'total_duration_minutes' => (int) $totalDuration,
            'total_reps' => $totalReps,
            'active_training_plans' => $activePlans,
            'current_streak_days' => $currentStreak,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is today's snapshot.
     */
    public function isToday(): bool
    {
        return $this->snapshot_date->isToday();
    }

    /**
     * Get the snapshot from the previous day.
     */
    public function previousSnapshot(): ?self
    {
        return static::where('trainee_id', $this->trainee_id)
            ->where('snapshot_date', '<', $this->snapshot_date)
            ->orderBy('snapshot_date', 'desc')
            ->first();
    }

    /**
     * Get the snapshot from the next day.
     */
    public function nextSnapshot(): ?self
    {
        return static::where('trainee_id', $this->trainee_id)
            ->where('snapshot_date', '>', $this->snapshot_date)
            ->orderBy('snapshot_date', 'asc')
            ->first();
    }

    /**
     * Get the change in total volume since previous snapshot.
     */
    public function getVolumeChange(): ?float
    {
        $previous = $this->previousSnapshot();
        if (!$previous) {
            return null;
        }

        return round($this->total_volume_kg - $previous->total_volume_kg, 2);
    }

    /**
     * Get the change in completion rate since previous snapshot.
     */
    public function getCompletionRateChange(): ?float
    {
        $previous = $this->previousSnapshot();
        if (!$previous) {
            return null;
        }

        return round($this->completion_rate - $previous->completion_rate, 2);
    }
}
