<?php

namespace App\Domain\Commerce;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\TrainingPlan;
use App\Shared\Enums\ActivePlanStatus;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TraineeActivePlan Model
 *
 * Tracks a trainee's progress through an active training plan.
 * Records current week/day and completion status.
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property string $training_plan_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property int $current_week
 * @property int $current_day
 * @property ActivePlanStatus $status
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TraineeActivePlan extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'trainee_active_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainee_id',
        'training_plan_id',
        'started_at',
        'current_week',
        'current_day',
        'status',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'current_week' => 'integer',
        'current_day' => 'integer',
        'status' => ActivePlanStatus::class,
        'completed_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'current_week' => 1,
        'current_day' => 1,
        'status' => 'active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee following this plan.
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(TraineeProfile::class, 'trainee_id');
    }

    /**
     * Get the training plan being followed.
     */
    public function trainingPlan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the number of days into the plan.
     */
    public function getDaysIntoPlansAttribute(): int
    {
        return (($this->current_week - 1) * $this->trainingPlan->days_per_week) + $this->current_day;
    }

    /**
     * Get the total days in the plan.
     */
    public function getTotalDaysAttribute(): int
    {
        return $this->trainingPlan->total_days;
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        $totalDays = $this->total_days;
        if ($totalDays === 0) {
            return 0;
        }

        return round(($this->days_into_plan / $totalDays) * 100, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', ActivePlanStatus::ACTIVE);
    }

    /**
     * Scope to paused plans.
     */
    public function scopePaused($query)
    {
        return $query->where('status', ActivePlanStatus::PAUSED);
    }

    /**
     * Scope to completed plans.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', ActivePlanStatus::COMPLETED);
    }

    /**
     * Scope to plans for a specific trainee.
     */
    public function scopeForTrainee($query, string $traineeId)
    {
        return $query->where('trainee_id', $traineeId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if plan is active.
     */
    public function isActive(): bool
    {
        return $this->status === ActivePlanStatus::ACTIVE;
    }

    /**
     * Check if plan is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === ActivePlanStatus::PAUSED;
    }

    /**
     * Check if plan is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === ActivePlanStatus::COMPLETED;
    }

    /**
     * Check if plan is abandoned.
     */
    public function isAbandoned(): bool
    {
        return $this->status === ActivePlanStatus::ABANDONED;
    }

    /**
     * Advance to next day.
     */
    public function advanceDay(): void
    {
        $plan = $this->trainingPlan;

        if ($this->current_day < $plan->days_per_week) {
            $this->increment('current_day');
        } else {
            // Move to next week
            $this->current_day = 1;
            $this->increment('current_week');

            // Check if plan is completed
            if ($this->current_week > $plan->duration_weeks) {
                $this->complete();
            }
        }

        $this->save();
    }

    /**
     * Pause the plan.
     */
    public function pause(): void
    {
        $this->update(['status' => ActivePlanStatus::PAUSED]);
    }

    /**
     * Resume the plan.
     */
    public function resume(): void
    {
        $this->update(['status' => ActivePlanStatus::ACTIVE]);
    }

    /**
     * Complete the plan.
     */
    public function complete(): void
    {
        $this->update([
            'status' => ActivePlanStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Abandon the plan.
     */
    public function abandon(): void
    {
        $this->update([
            'status' => ActivePlanStatus::ABANDONED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Reset to beginning.
     */
    public function restart(): void
    {
        $this->update([
            'current_week' => 1,
            'current_day' => 1,
            'status' => ActivePlanStatus::ACTIVE,
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Check if on last week.
     */
    public function isOnLastWeek(): bool
    {
        return $this->current_week === $this->trainingPlan->duration_weeks;
    }

    /**
     * Check if on last day of week.
     */
    public function isOnLastDayOfWeek(): bool
    {
        return $this->current_day === $this->trainingPlan->days_per_week;
    }

    /**
     * Get the current week model.
     */
    public function getCurrentWeek()
    {
        return $this->trainingPlan->weeks()
            ->where('week_number', $this->current_week)
            ->first();
    }

    /**
     * Get the current day model.
     */
    public function getCurrentDay()
    {
        $week = $this->getCurrentWeek();
        if (!$week) {
            return null;
        }

        return $week->days()->where('day_number', $this->current_day)->first();
    }
}
