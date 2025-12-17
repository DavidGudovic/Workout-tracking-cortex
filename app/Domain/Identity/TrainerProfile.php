<?php

namespace App\Domain\Identity;

use App\Shared\Enums\TrainerStatus;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * TrainerProfile Model
 *
 * Represents a trainer profile in the system.
 * One user can have one trainer profile (1:1 relationship).
 * Trainers can create workouts, training plans, and be associated with gyms.
 *
 * @property string $id UUID primary key
 * @property string $user_id
 * @property string $display_name
 * @property string $slug
 * @property string|null $bio
 * @property string|null $avatar_url
 * @property string|null $cover_image_url
 * @property array|null $specializations
 * @property array|null $certifications
 * @property int|null $years_experience
 * @property int|null $hourly_rate_cents
 * @property string $currency
 * @property bool $is_available_for_hire
 * @property TrainerStatus $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TrainerProfile extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'trainer_profiles';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TrainerProfileFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'display_name',
        'slug',
        'bio',
        'avatar_url',
        'cover_image_url',
        'specializations',
        'certifications',
        'years_experience',
        'hourly_rate_cents',
        'currency',
        'is_available_for_hire',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'specializations' => 'array',
        'certifications' => 'array',
        'years_experience' => 'integer',
        'hourly_rate_cents' => 'integer',
        'is_available_for_hire' => 'boolean',
        'status' => TrainerStatus::class,
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
        'is_available_for_hire' => true,
        'status' => 'active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from display_name if not provided
        static::creating(function ($profile) {
            if (empty($profile->slug) && !empty($profile->display_name)) {
                $profile->slug = Str::slug($profile->display_name);

                // Ensure uniqueness
                $count = 1;
                $originalSlug = $profile->slug;
                while (static::where('slug', $profile->slug)->exists()) {
                    $profile->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns this trainer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workouts created by this trainer.
     */
    public function workouts(): HasMany
    {
        return $this->hasMany(\App\Domain\Training\Workout::class, 'trainer_id');
    }

    /**
     * Get the training plans created by this trainer.
     */
    public function trainingPlans(): HasMany
    {
        return $this->hasMany(\App\Domain\Training\TrainingPlan::class, 'trainer_id');
    }

    /**
     * Get the gym associations for this trainer (via gym_trainers pivot).
     */
    public function gymAssociations(): HasMany
    {
        return $this->hasMany(\App\Domain\Gym\GymTrainer::class, 'trainer_id');
    }

    /**
     * Get the trainer contracts for this trainer.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\TrainerContract::class, 'trainer_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainer's hourly rate in dollars.
     */
    public function getHourlyRateDollarsAttribute(): ?float
    {
        return $this->hourly_rate_cents ? $this->hourly_rate_cents / 100 : null;
    }

    /**
     * Set the trainer's hourly rate from dollars.
     */
    public function setHourlyRateDollarsAttribute(float $value): void
    {
        $this->hourly_rate_cents = (int) ($value * 100);
    }

    /**
     * Get the full profile URL.
     */
    public function getProfileUrlAttribute(): string
    {
        return '/trainers/' . $this->slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only active trainers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', TrainerStatus::ACTIVE);
    }

    /**
     * Scope to only trainers available for hire.
     */
    public function scopeAvailableForHire($query)
    {
        return $query->where('is_available_for_hire', true)
                     ->where('status', TrainerStatus::ACTIVE);
    }

    /**
     * Scope to trainers with specific specialization.
     */
    public function scopeWithSpecialization($query, string $specialization)
    {
        return $query->whereJsonContains('specializations', $specialization);
    }

    /**
     * Scope to search trainers by name or slug.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('display_name', 'ilike', "%{$search}%")
              ->orWhere('slug', 'ilike', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the trainer is active.
     */
    public function isActive(): bool
    {
        return $this->status === TrainerStatus::ACTIVE;
    }

    /**
     * Check if the trainer is available for hire.
     */
    public function isAvailableForHire(): bool
    {
        return $this->is_available_for_hire && $this->isActive();
    }

    /**
     * Check if the trainer has a specific specialization.
     */
    public function hasSpecialization(string $specialization): bool
    {
        return in_array($specialization, $this->specializations ?? []);
    }
}
