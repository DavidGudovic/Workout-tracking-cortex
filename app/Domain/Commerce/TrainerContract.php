<?php

namespace App\Domain\Commerce;

use App\Domain\Gym\Gym;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\TrainerProfile;
use App\Shared\Enums\ContractStatus;
use App\Shared\Enums\ContractType;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrainerContract Model
 *
 * Represents a paid contract between a trainee and trainer.
 * Can be session-based (X sessions) or time-based (valid for Y months).
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property string $trainer_id
 * @property string|null $gym_id
 * @property ContractType $contract_type
 * @property int|null $total_sessions
 * @property int $sessions_used
 * @property \Illuminate\Support\Carbon $valid_from
 * @property \Illuminate\Support\Carbon $valid_until
 * @property int $price_cents
 * @property string $currency
 * @property ContractStatus $status
 * @property string|null $payment_reference
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class TrainerContract extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'trainer_contracts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainee_id',
        'trainer_id',
        'gym_id',
        'contract_type',
        'total_sessions',
        'sessions_used',
        'valid_from',
        'valid_until',
        'price_cents',
        'currency',
        'status',
        'payment_reference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'contract_type' => ContractType::class,
        'total_sessions' => 'integer',
        'sessions_used' => 'integer',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'price_cents' => 'integer',
        'status' => ContractStatus::class,
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sessions_used' => 0,
        'currency' => 'USD',
        'status' => 'active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee for this contract.
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(TraineeProfile::class, 'trainee_id');
    }

    /**
     * Get the trainer for this contract.
     */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(TrainerProfile::class, 'trainer_id');
    }

    /**
     * Get the gym (if contract is gym-affiliated).
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the price in dollars.
     */
    public function getPriceDollarsAttribute(): float
    {
        return $this->price_cents / 100;
    }

    /**
     * Get remaining sessions (for session-based contracts).
     */
    public function getSessionsRemainingAttribute(): ?int
    {
        if ($this->contract_type !== ContractType::SESSION_BASED || !$this->total_sessions) {
            return null;
        }

        return max(0, $this->total_sessions - $this->sessions_used);
    }

    /**
     * Get days remaining (for time-based contracts).
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->contract_type !== ContractType::TIME_BASED) {
            return null;
        }

        return max(0, now()->diffInDays($this->valid_until, false));
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to active contracts.
     */
    public function scopeActive($query)
    {
        return $query->where('status', ContractStatus::ACTIVE);
    }

    /**
     * Scope to session-based contracts.
     */
    public function scopeSessionBased($query)
    {
        return $query->where('contract_type', ContractType::SESSION_BASED);
    }

    /**
     * Scope to time-based contracts.
     */
    public function scopeTimeBased($query)
    {
        return $query->where('contract_type', ContractType::TIME_BASED);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if contract is active.
     */
    public function isActive(): bool
    {
        return $this->status === ContractStatus::ACTIVE &&
               now()->between($this->valid_from, $this->valid_until);
    }

    /**
     * Check if contract is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === ContractStatus::EXPIRED ||
               now()->isAfter($this->valid_until);
    }

    /**
     * Check if contract has sessions remaining.
     */
    public function hasSessionsRemaining(): bool
    {
        if ($this->contract_type !== ContractType::SESSION_BASED) {
            return true; // Time-based always has sessions
        }

        return $this->sessions_remaining > 0;
    }

    /**
     * Use one session from the contract.
     */
    public function useSession(): void
    {
        if ($this->contract_type === ContractType::SESSION_BASED) {
            $this->increment('sessions_used');

            // Mark as completed if all sessions used
            if ($this->sessions_used >= $this->total_sessions) {
                $this->update(['status' => ContractStatus::COMPLETED]);
            }
        }
    }

    /**
     * Cancel the contract.
     */
    public function cancel(): void
    {
        $this->update(['status' => ContractStatus::CANCELLED]);
    }

    /**
     * Mark contract as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => ContractStatus::EXPIRED]);
    }

    /**
     * Mark contract as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => ContractStatus::COMPLETED]);
    }
}
