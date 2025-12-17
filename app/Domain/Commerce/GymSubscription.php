<?php

namespace App\Domain\Commerce;

use App\Domain\Gym\Gym;
use App\Domain\Gym\SubscriptionTier;
use App\Domain\Identity\TraineeProfile;
use App\Shared\Enums\SubscriptionStatus;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GymSubscription Model
 *
 * Represents a trainee's subscription to a gym.
 * Recurring subscription with billing periods.
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property string $gym_id
 * @property string $subscription_tier_id
 * @property SubscriptionStatus $status
 * @property \Illuminate\Support\Carbon $current_period_start
 * @property \Illuminate\Support\Carbon $current_period_end
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string|null $payment_reference
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class GymSubscription extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'gym_subscriptions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'trainee_id',
        'gym_id',
        'subscription_tier_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'cancellation_reason',
        'payment_reference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => SubscriptionStatus::class,
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee who has this subscription.
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(TraineeProfile::class, 'trainee_id');
    }

    /**
     * Get the gym for this subscription.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Get the subscription tier.
     */
    public function tier(): BelongsTo
    {
        return $this->belongsTo(SubscriptionTier::class, 'subscription_tier_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    /**
     * Scope to cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', SubscriptionStatus::CANCELLED);
    }

    /**
     * Scope to expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', SubscriptionStatus::EXPIRED);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE &&
               $this->current_period_end->isFuture();
    }

    /**
     * Check if subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::CANCELLED;
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === SubscriptionStatus::EXPIRED ||
               $this->current_period_end->isPast();
    }

    /**
     * Cancel the subscription.
     */
    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Renew the subscription for the next period.
     */
    public function renew(): void
    {
        $billingPeriod = $this->tier->billing_period;
        $monthsToAdd = $billingPeriod->months();

        $this->update([
            'current_period_start' => $this->current_period_end,
            'current_period_end' => $this->current_period_end->copy()->addMonths($monthsToAdd),
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    /**
     * Mark subscription as expired.
     */
    public function markAsExpired(): void
    {
        $this->update(['status' => SubscriptionStatus::EXPIRED]);
    }

    /**
     * Get days remaining in current period.
     */
    public function getDaysRemaining(): int
    {
        return max(0, now()->diffInDays($this->current_period_end, false));
    }
}
