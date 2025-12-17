<?php

namespace App\Domain\Gym;

use App\Domain\Identity\TrainerProfile;
use App\Shared\Enums\GymTrainerStatus;
use App\Shared\Enums\TrainerRole;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GymTrainer Model (Pivot Model)
 *
 * Represents the employment relationship between a gym and a trainer.
 * Tracks hiring status, role, compensation, and employment history.
 *
 * @property string $id UUID primary key
 * @property string $gym_id
 * @property string $trainer_id
 * @property GymTrainerStatus $status
 * @property TrainerRole $role
 * @property int|null $hourly_rate_cents
 * @property float|null $commission_percentage
 * @property \Illuminate\Support\Carbon|null $hired_at
 * @property \Illuminate\Support\Carbon|null $terminated_at
 * @property string|null $termination_reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class GymTrainer extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'gym_trainers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gym_id',
        'trainer_id',
        'status',
        'role',
        'hourly_rate_cents',
        'commission_percentage',
        'hired_at',
        'terminated_at',
        'termination_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => GymTrainerStatus::class,
        'role' => TrainerRole::class,
        'hourly_rate_cents' => 'integer',
        'commission_percentage' => 'decimal:2',
        'hired_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'role' => 'staff_trainer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the gym for this employment.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Get the trainer profile for this employment.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class, 'trainer_id');
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
     * Get the duration of employment in days.
     */
    public function getEmploymentDurationDaysAttribute(): ?int
    {
        if (!$this->hired_at) {
            return null;
        }

        $endDate = $this->terminated_at ?? now();
        return $this->hired_at->diffInDays($endDate);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only active employments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', GymTrainerStatus::ACTIVE);
    }

    /**
     * Scope to only pending employments.
     */
    public function scopePending($query)
    {
        return $query->where('status', GymTrainerStatus::PENDING);
    }

    /**
     * Scope to only terminated employments.
     */
    public function scopeTerminated($query)
    {
        return $query->where('status', GymTrainerStatus::TERMINATED);
    }

    /**
     * Scope to employments for a specific gym.
     */
    public function scopeForGym($query, string $gymId)
    {
        return $query->where('gym_id', $gymId);
    }

    /**
     * Scope to employments for a specific trainer.
     */
    public function scopeForTrainer($query, string $trainerId)
    {
        return $query->where('trainer_id', $trainerId);
    }

    /**
     * Scope to filter by role.
     */
    public function scopeWithRole($query, TrainerRole $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to head trainers only.
     */
    public function scopeHeadTrainers($query)
    {
        return $query->where('role', TrainerRole::HEAD_TRAINER);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the employment is active.
     */
    public function isActive(): bool
    {
        return $this->status === GymTrainerStatus::ACTIVE;
    }

    /**
     * Check if the employment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === GymTrainerStatus::PENDING;
    }

    /**
     * Check if the employment is terminated.
     */
    public function isTerminated(): bool
    {
        return $this->status === GymTrainerStatus::TERMINATED;
    }

    /**
     * Activate the employment.
     */
    public function activate(): void
    {
        $this->update([
            'status' => GymTrainerStatus::ACTIVE,
            'hired_at' => $this->hired_at ?? now(),
        ]);
    }

    /**
     * Terminate the employment.
     */
    public function terminate(string $reason = null): void
    {
        $this->update([
            'status' => GymTrainerStatus::TERMINATED,
            'terminated_at' => now(),
            'termination_reason' => $reason,
        ]);
    }

    /**
     * Check if the trainer is a head trainer.
     */
    public function isHeadTrainer(): bool
    {
        return $this->role === TrainerRole::HEAD_TRAINER;
    }

    /**
     * Check if the trainer is a contractor.
     */
    public function isContractor(): bool
    {
        return $this->role === TrainerRole::CONTRACTOR;
    }
}
