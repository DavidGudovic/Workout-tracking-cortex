<?php

namespace App\Domain\Training;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TrainingPlanWeek Model
 *
 * Represents a week within a training plan.
 * Each week contains multiple days (based on plan's days_per_week).
 *
 * @property string $id UUID primary key
 * @property string $training_plan_id
 * @property int $week_number
 * @property string|null $name
 * @property string|null $notes
 */
class TrainingPlanWeek extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'training_plan_weeks';

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
        'training_plan_id',
        'week_number',
        'name',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'week_number' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the training plan this week belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class, 'training_plan_id');
    }

    /**
     * Get the days in this week.
     */
    public function days(): HasMany
    {
        return $this->hasMany(TrainingPlanDay::class)->orderBy('day_number');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to weeks for a specific training plan.
     */
    public function scopeForPlan($query, string $planId)
    {
        return $query->where('training_plan_id', $planId);
    }

    /**
     * Scope to sort weeks by week number.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('week_number');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is the first week.
     */
    public function isFirstWeek(): bool
    {
        return $this->week_number === 1;
    }

    /**
     * Check if this is the last week of the plan.
     */
    public function isLastWeek(): bool
    {
        return $this->week_number === $this->plan->duration_weeks;
    }

    /**
     * Check if this is a deload week (can be customized based on name).
     */
    public function isDeloadWeek(): bool
    {
        return stripos($this->name ?? '', 'deload') !== false;
    }

    /**
     * Get the previous week.
     */
    public function previousWeek(): ?self
    {
        return static::where('training_plan_id', $this->training_plan_id)
            ->where('week_number', $this->week_number - 1)
            ->first();
    }

    /**
     * Get the next week.
     */
    public function nextWeek(): ?self
    {
        return static::where('training_plan_id', $this->training_plan_id)
            ->where('week_number', $this->week_number + 1)
            ->first();
    }

    /**
     * Get the total number of workouts in this week.
     */
    public function getTotalWorkouts(): int
    {
        return TrainingPlanWorkout::whereHas('day', function ($query) {
            $query->where('training_plan_week_id', $this->id);
        })->count();
    }

    /**
     * Get the total number of rest days in this week.
     */
    public function getRestDaysCount(): int
    {
        return $this->days()->where('is_rest_day', true)->count();
    }

    /**
     * Get the total number of training days in this week.
     */
    public function getTrainingDaysCount(): int
    {
        return $this->days()->where('is_rest_day', false)->count();
    }

    /**
     * Check if this week is complete (all days have workouts assigned or marked as rest).
     */
    public function isComplete(): bool
    {
        $days = $this->days;

        foreach ($days as $day) {
            if (!$day->is_rest_day && $day->workouts()->count() === 0) {
                return false;
            }
        }

        return true;
    }
}
