<?php

namespace App\Domain\Training;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TrainingPlanDay Model
 *
 * Represents a day within a training plan week.
 * Each day can have multiple workouts assigned or be marked as a rest day.
 *
 * @property string $id UUID primary key
 * @property string $training_plan_week_id
 * @property int $day_number
 * @property string|null $name
 * @property bool $is_rest_day
 * @property string|null $notes
 */
class TrainingPlanDay extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'training_plan_days';

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
        'training_plan_week_id',
        'day_number',
        'name',
        'is_rest_day',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'day_number' => 'integer',
        'is_rest_day' => 'boolean',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_rest_day' => false,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the week this day belongs to.
     */
    public function week(): BelongsTo
    {
        return $this->belongsTo(TrainingPlanWeek::class, 'training_plan_week_id');
    }

    /**
     * Get the workouts assigned to this day.
     */
    public function workouts(): HasMany
    {
        return $this->hasMany(TrainingPlanWorkout::class)->orderBy('sort_order');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to days for a specific week.
     */
    public function scopeForWeek($query, string $weekId)
    {
        return $query->where('training_plan_week_id', $weekId);
    }

    /**
     * Scope to only rest days.
     */
    public function scopeRestDays($query)
    {
        return $query->where('is_rest_day', true);
    }

    /**
     * Scope to only training days.
     */
    public function scopeTrainingDays($query)
    {
        return $query->where('is_rest_day', false);
    }

    /**
     * Scope to sort days by day number.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('day_number');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is a rest day.
     */
    public function isRestDay(): bool
    {
        return $this->is_rest_day;
    }

    /**
     * Check if this is a training day (not rest).
     */
    public function isTrainingDay(): bool
    {
        return !$this->is_rest_day;
    }

    /**
     * Mark this day as a rest day.
     */
    public function markAsRestDay(): void
    {
        $this->update(['is_rest_day' => true]);
        // Remove any workouts if marked as rest
        $this->workouts()->delete();
    }

    /**
     * Mark this day as a training day.
     */
    public function markAsTrainingDay(): void
    {
        $this->update(['is_rest_day' => false]);
    }

    /**
     * Get the previous day (might be in previous week).
     */
    public function previousDay(): ?self
    {
        // Try previous day in same week
        $prevDay = static::where('training_plan_week_id', $this->training_plan_week_id)
            ->where('day_number', $this->day_number - 1)
            ->first();

        if ($prevDay) {
            return $prevDay;
        }

        // If no previous day in this week, get last day of previous week
        $prevWeek = $this->week->previousWeek();
        if ($prevWeek) {
            return $prevWeek->days()->orderBy('day_number', 'desc')->first();
        }

        return null;
    }

    /**
     * Get the next day (might be in next week).
     */
    public function nextDay(): ?self
    {
        // Try next day in same week
        $nextDay = static::where('training_plan_week_id', $this->training_plan_week_id)
            ->where('day_number', $this->day_number + 1)
            ->first();

        if ($nextDay) {
            return $nextDay;
        }

        // If no next day in this week, get first day of next week
        $nextWeek = $this->week->nextWeek();
        if ($nextWeek) {
            return $nextWeek->days()->orderBy('day_number')->first();
        }

        return null;
    }

    /**
     * Get the day of week name (Monday, Tuesday, etc.).
     */
    public function getDayOfWeekName(): string
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $index = ($this->day_number - 1) % 7;
        return $days[$index];
    }

    /**
     * Check if this day has any workouts assigned.
     */
    public function hasWorkouts(): bool
    {
        return $this->workouts()->exists();
    }

    /**
     * Get the total estimated duration for all workouts on this day.
     */
    public function getTotalEstimatedDuration(): int
    {
        $total = 0;
        foreach ($this->workouts as $planWorkout) {
            if ($planWorkout->workout->estimated_duration_minutes) {
                $total += $planWorkout->workout->estimated_duration_minutes;
            }
        }
        return $total;
    }

    /**
     * Check if this day is complete (has workouts or is rest day).
     */
    public function isComplete(): bool
    {
        return $this->is_rest_day || $this->hasWorkouts();
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TrainingPlanDayFactory::new();
    }
}
