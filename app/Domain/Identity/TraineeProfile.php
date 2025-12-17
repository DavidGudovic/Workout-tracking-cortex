<?php

namespace App\Domain\Identity;

use App\Shared\Enums\ExperienceLevel;
use App\Shared\Enums\FitnessGoal;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * TraineeProfile Model
 *
 * Represents a trainee (athlete/user) profile in the system.
 * One user can have one trainee profile (1:1 relationship).
 * Trainees can perform workout sessions, track progress, and purchase content.
 *
 * @property string $id UUID primary key
 * @property string $user_id
 * @property string $display_name
 * @property string|null $avatar_url
 * @property \Illuminate\Support\Carbon|null $date_of_birth
 * @property string|null $gender
 * @property float|null $height_cm
 * @property float|null $weight_kg
 * @property FitnessGoal|null $fitness_goal
 * @property ExperienceLevel|null $experience_level
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TraineeProfile extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'trainee_profiles';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TraineeProfileFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'display_name',
        'avatar_url',
        'date_of_birth',
        'gender',
        'height_cm',
        'weight_kg',
        'fitness_goal',
        'experience_level',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'fitness_goal' => FitnessGoal::class,
        'experience_level' => ExperienceLevel::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this trainee profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workout sessions performed by this trainee.
     */
    public function workoutSessions(): HasMany
    {
        return $this->hasMany(\App\Domain\Execution\WorkoutSession::class, 'trainee_id');
    }

    /**
     * Get the progress snapshots for this trainee.
     */
    public function progressSnapshots(): HasMany
    {
        return $this->hasMany(\App\Domain\Analytics\ProgressSnapshot::class, 'trainee_id');
    }

    /**
     * Get the personal records for this trainee.
     */
    public function personalRecords(): HasMany
    {
        return $this->hasMany(\App\Domain\Analytics\PersonalRecord::class, 'trainee_id');
    }

    /**
     * Get the workout purchases made by this trainee.
     */
    public function workoutPurchases(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\WorkoutPurchase::class, 'trainee_id');
    }

    /**
     * Get the training plan purchases made by this trainee.
     */
    public function trainingPlanPurchases(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\TrainingPlanPurchase::class, 'trainee_id');
    }

    /**
     * Get the gym subscriptions for this trainee.
     */
    public function gymSubscriptions(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\GymSubscription::class, 'trainee_id');
    }

    /**
     * Get the currently active training plan for this trainee.
     */
    public function activePlan(): HasOne
    {
        return $this->hasOne(\App\Domain\Commerce\TraineeActivePlan::class, 'trainee_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee's age.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Get the trainee's BMI (Body Mass Index).
     */
    public function getBmiAttribute(): ?float
    {
        if (!$this->height_cm || !$this->weight_kg) {
            return null;
        }

        $heightMeters = $this->height_cm / 100;
        return round($this->weight_kg / ($heightMeters * $heightMeters), 2);
    }

    /**
     * Get height in feet and inches.
     */
    public function getHeightFeetInchesAttribute(): ?string
    {
        if (!$this->height_cm) {
            return null;
        }

        $totalInches = $this->height_cm / 2.54;
        $feet = floor($totalInches / 12);
        $inches = round($totalInches % 12);

        return "{$feet}'{$inches}\"";
    }

    /**
     * Get weight in pounds.
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
     * Scope to trainees with a specific fitness goal.
     */
    public function scopeWithFitnessGoal($query, FitnessGoal $goal)
    {
        return $query->where('fitness_goal', $goal);
    }

    /**
     * Scope to trainees with a specific experience level.
     */
    public function scopeWithExperienceLevel($query, ExperienceLevel $level)
    {
        return $query->where('experience_level', $level);
    }

    /**
     * Scope to search trainees by display name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('display_name', 'ilike', "%{$search}%");
    }

    /**
     * Scope to trainees within an age range.
     */
    public function scopeAgeRange($query, int $minAge, int $maxAge)
    {
        $maxDate = now()->subYears($minAge)->toDateString();
        $minDate = now()->subYears($maxAge + 1)->addDay()->toDateString();

        return $query->whereBetween('date_of_birth', [$minDate, $maxDate]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the trainee has an active training plan.
     */
    public function hasActivePlan(): bool
    {
        return $this->activePlan()->exists();
    }

    /**
     * Get the trainee's total workout count.
     */
    public function getTotalWorkoutsCompleted(): int
    {
        return $this->workoutSessions()
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Get the trainee's current streak (consecutive days with workouts).
     */
    public function getCurrentStreak(): int
    {
        $sessions = $this->workoutSessions()
            ->where('status', 'completed')
            ->orderBy('started_at', 'desc')
            ->get()
            ->pluck('started_at')
            ->map(fn ($date) => $date->toDateString())
            ->unique();

        $streak = 0;
        $currentDate = now()->toDateString();

        foreach ($sessions as $sessionDate) {
            if ($sessionDate === $currentDate) {
                $streak++;
                $currentDate = now()->subDays($streak)->toDateString();
            } else {
                break;
            }
        }

        return $streak;
    }
}
