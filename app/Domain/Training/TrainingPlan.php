<?php

namespace App\Domain\Training;

use App\Domain\Identity\TrainerProfile;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\FitnessGoal;
use App\Shared\Enums\PricingType;
use App\Shared\Enums\WorkoutStatus;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TrainingPlan Model
 *
 * Represents a multi-week training program created by a trainer.
 * A training plan contains structured weeks, days, and workouts.
 *
 * Structure:
 * - TrainingPlan (this)
 *   - TrainingPlanWeek (weeks within plan)
 *     - TrainingPlanDay (days within week)
 *       - TrainingPlanWorkout (workouts assigned to day)
 *
 * CRITICAL BUSINESS RULES:
 * - Premium plans must have price > 0
 * - days_per_week must be 1-7
 * - Published plans create versions on edit (versioning system)
 *
 * @property string $id UUID primary key
 * @property string $creator_id
 * @property string $name
 * @property string|null $description
 * @property string|null $cover_image_url
 * @property FitnessGoal|null $goal
 * @property Difficulty $difficulty
 * @property int $duration_weeks
 * @property int $days_per_week
 * @property PricingType $pricing_type
 * @property int|null $price_cents
 * @property string $currency
 * @property WorkoutStatus $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $published_at
 */
class TrainingPlan extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'training_plans';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_id',
        'name',
        'description',
        'cover_image_url',
        'goal',
        'difficulty',
        'duration_weeks',
        'days_per_week',
        'pricing_type',
        'price_cents',
        'currency',
        'status',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'goal' => FitnessGoal::class,
        'difficulty' => Difficulty::class,
        'duration_weeks' => 'integer',
        'days_per_week' => 'integer',
        'pricing_type' => PricingType::class,
        'price_cents' => 'integer',
        'status' => WorkoutStatus::class,
        'published_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'pricing_type' => 'free',
        'currency' => 'USD',
        'status' => 'draft',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainer who created this training plan.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class, 'creator_id');
    }

    /**
     * Get the weeks in this training plan.
     */
    public function weeks(): HasMany
    {
        return $this->hasMany(TrainingPlanWeek::class)->orderBy('week_number');
    }

    /**
     * Get the purchases for this training plan.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\TrainingPlanPurchase::class);
    }

    /**
     * Get the active trainees currently following this plan.
     */
    public function activeTrainees(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\TraineeActivePlan::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the plan's price in dollars.
     */
    public function getPriceDollarsAttribute(): ?float
    {
        return $this->price_cents ? $this->price_cents / 100 : null;
    }

    /**
     * Set the plan's price from dollars.
     */
    public function setPriceDollarsAttribute(float $value): void
    {
        $this->price_cents = (int) ($value * 100);
    }

    /**
     * Get the total number of days in the plan.
     */
    public function getTotalDaysAttribute(): int
    {
        return $this->duration_weeks * $this->days_per_week;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only published plans.
     */
    public function scopePublished($query)
    {
        return $query->where('status', WorkoutStatus::PUBLISHED);
    }

    /**
     * Scope to only draft plans.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', WorkoutStatus::DRAFT);
    }

    /**
     * Scope to only archived plans.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', WorkoutStatus::ARCHIVED);
    }

    /**
     * Scope to only free plans.
     */
    public function scopeFree($query)
    {
        return $query->where('pricing_type', PricingType::FREE);
    }

    /**
     * Scope to only premium plans.
     */
    public function scopePremium($query)
    {
        return $query->where('pricing_type', PricingType::PREMIUM);
    }

    /**
     * Scope to plans created by a specific trainer.
     */
    public function scopeByCreator($query, string $creatorId)
    {
        return $query->where('creator_id', $creatorId);
    }

    /**
     * Scope to filter by difficulty.
     */
    public function scopeByDifficulty($query, Difficulty $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Scope to filter by goal.
     */
    public function scopeByGoal($query, FitnessGoal $goal)
    {
        return $query->where('goal', $goal);
    }

    /**
     * Scope to filter by duration range.
     */
    public function scopeByDuration($query, int $minWeeks, int $maxWeeks = null)
    {
        $query->where('duration_weeks', '>=', $minWeeks);
        if ($maxWeeks) {
            $query->where('duration_weeks', '<=', $maxWeeks);
        }
        return $query;
    }

    /**
     * Scope to search plans by name or description.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('description', 'ilike', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the plan is published.
     */
    public function isPublished(): bool
    {
        return $this->status === WorkoutStatus::PUBLISHED;
    }

    /**
     * Check if the plan is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === WorkoutStatus::DRAFT;
    }

    /**
     * Check if the plan is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === WorkoutStatus::ARCHIVED;
    }

    /**
     * Check if the plan is free.
     */
    public function isFree(): bool
    {
        return $this->pricing_type === PricingType::FREE;
    }

    /**
     * Check if the plan is premium.
     */
    public function isPremium(): bool
    {
        return $this->pricing_type === PricingType::PREMIUM;
    }

    /**
     * Publish the plan.
     */
    public function publish(): void
    {
        $this->update([
            'status' => WorkoutStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Archive the plan.
     */
    public function archive(): void
    {
        $this->update(['status' => WorkoutStatus::ARCHIVED]);
    }

    /**
     * Revert to draft.
     */
    public function revertToDraft(): void
    {
        $this->update(['status' => WorkoutStatus::DRAFT]);
    }

    /**
     * Check if the plan is complete (all weeks created).
     */
    public function isComplete(): bool
    {
        return $this->weeks()->count() === $this->duration_weeks;
    }

    /**
     * Get completion percentage (based on weeks created).
     */
    public function getCompletionPercentage(): float
    {
        $weeksCreated = $this->weeks()->count();
        if ($this->duration_weeks === 0) {
            return 0;
        }
        return round(($weeksCreated / $this->duration_weeks) * 100, 2);
    }

    /**
     * Get the total number of workouts in the plan.
     */
    public function getTotalWorkouts(): int
    {
        return TrainingPlanWorkout::whereHas('day.week', function ($query) {
            $query->where('training_plan_id', $this->id);
        })->count();
    }

    /**
     * Generate the plan structure (weeks and days).
     * Called after creating a new plan.
     */
    public function generateStructure(): void
    {
        for ($weekNum = 1; $weekNum <= $this->duration_weeks; $weekNum++) {
            $week = $this->weeks()->create([
                'week_number' => $weekNum,
                'name' => "Week {$weekNum}",
            ]);

            for ($dayNum = 1; $dayNum <= $this->days_per_week; $dayNum++) {
                $week->days()->create([
                    'day_number' => $dayNum,
                    'name' => "Day {$dayNum}",
                    'is_rest_day' => false,
                ]);
            }
        }
    }
}
