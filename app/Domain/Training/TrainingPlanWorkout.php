<?php

namespace App\Domain\Training;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrainingPlanWorkout Model (Pivot/Junction Model)
 *
 * Links workouts to specific days in a training plan.
 * A day can have multiple workouts (e.g., AM and PM sessions).
 *
 * @property string $id UUID primary key
 * @property string $training_plan_day_id
 * @property string $workout_id
 * @property int $sort_order
 * @property bool $is_optional
 */
class TrainingPlanWorkout extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'training_plan_workouts';

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
        'training_plan_day_id',
        'workout_id',
        'sort_order',
        'is_optional',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_optional' => 'boolean',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_optional' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the day this workout is assigned to.
     */
    public function day(): BelongsTo
    {
        return $this->belongsTo(TrainingPlanDay::class, 'training_plan_day_id');
    }

    /**
     * Get the workout assigned to this day.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to workouts for a specific day.
     */
    public function scopeForDay($query, string $dayId)
    {
        return $query->where('training_plan_day_id', $dayId);
    }

    /**
     * Scope to only required workouts.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_optional', false);
    }

    /**
     * Scope to only optional workouts.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    /**
     * Scope to sort workouts by sort_order.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this workout is optional.
     */
    public function isOptional(): bool
    {
        return $this->is_optional;
    }

    /**
     * Check if this workout is required.
     */
    public function isRequired(): bool
    {
        return !$this->is_optional;
    }

    /**
     * Mark this workout as optional.
     */
    public function markAsOptional(): void
    {
        $this->update(['is_optional' => true]);
    }

    /**
     * Mark this workout as required.
     */
    public function markAsRequired(): void
    {
        $this->update(['is_optional' => false]);
    }

    /**
     * Get the training plan this workout belongs to (through day and week).
     */
    public function getTrainingPlan(): TrainingPlan
    {
        return $this->day->week->plan;
    }

    /**
     * Get the week number for this workout.
     */
    public function getWeekNumber(): int
    {
        return $this->day->week->week_number;
    }

    /**
     * Get the day number for this workout.
     */
    public function getDayNumber(): int
    {
        return $this->day->day_number;
    }

    /**
     * Get a formatted label for this workout (e.g., "Week 1, Day 3").
     */
    public function getLabel(): string
    {
        $week = $this->getWeekNumber();
        $day = $this->getDayNumber();
        $optional = $this->is_optional ? ' (Optional)' : '';

        return "Week {$week}, Day {$day}{$optional}";
    }

    /**
     * Check if this workout is the first workout of the day.
     */
    public function isFirstWorkoutOfDay(): bool
    {
        return $this->sort_order === 0 ||
               $this->sort_order === static::where('training_plan_day_id', $this->training_plan_day_id)
                   ->min('sort_order');
    }

    /**
     * Check if this workout is the last workout of the day.
     */
    public function isLastWorkoutOfDay(): bool
    {
        return $this->sort_order === static::where('training_plan_day_id', $this->training_plan_day_id)
            ->max('sort_order');
    }

    /**
     * Get the previous workout on the same day.
     */
    public function previousWorkout(): ?self
    {
        return static::where('training_plan_day_id', $this->training_plan_day_id)
            ->where('sort_order', '<', $this->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
    }

    /**
     * Get the next workout on the same day.
     */
    public function nextWorkout(): ?self
    {
        return static::where('training_plan_day_id', $this->training_plan_day_id)
            ->where('sort_order', '>', $this->sort_order)
            ->orderBy('sort_order')
            ->first();
    }
}
