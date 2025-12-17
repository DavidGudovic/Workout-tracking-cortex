<?php

namespace App\Domain\Gym;

use App\Shared\Enums\BillingPeriod;
use App\Shared\Enums\TierStatus;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SubscriptionTier Model
 *
 * Represents a membership tier for a gym.
 * Each gym can have multiple subscription tiers (e.g., Basic, Premium, VIP).
 *
 * @property string $id UUID primary key
 * @property string $gym_id
 * @property string $name
 * @property string|null $description
 * @property int $price_cents
 * @property string $currency
 * @property BillingPeriod $billing_period
 * @property array|null $benefits
 * @property int|null $max_members
 * @property bool $includes_trainer_access
 * @property TierStatus $status
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SubscriptionTier extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'subscription_tiers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gym_id',
        'name',
        'description',
        'price_cents',
        'currency',
        'billing_period',
        'benefits',
        'max_members',
        'includes_trainer_access',
        'status',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_cents' => 'integer',
        'billing_period' => BillingPeriod::class,
        'benefits' => 'array',
        'max_members' => 'integer',
        'includes_trainer_access' => 'boolean',
        'status' => TierStatus::class,
        'sort_order' => 'integer',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'USD',
        'includes_trainer_access' => false,
        'status' => 'active',
        'sort_order' => 0,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the gym this tier belongs to.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Get the gym subscriptions for this tier.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\GymSubscription::class, 'tier_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the tier's price in dollars.
     */
    public function getPriceDollarsAttribute(): float
    {
        return $this->price_cents / 100;
    }

    /**
     * Set the tier's price from dollars.
     */
    public function setPriceDollarsAttribute(float $value): void
    {
        $this->price_cents = (int) ($value * 100);
    }

    /**
     * Get the monthly equivalent price for comparison across billing periods.
     */
    public function getMonthlyEquivalentPriceAttribute(): float
    {
        $months = $this->billing_period->months();
        return round($this->price_cents / $months / 100, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only active tiers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', TierStatus::ACTIVE);
    }

    /**
     * Scope to tiers for a specific gym.
     */
    public function scopeForGym($query, string $gymId)
    {
        return $query->where('gym_id', $gymId);
    }

    /**
     * Scope to tiers with trainer access.
     */
    public function scopeWithTrainerAccess($query)
    {
        return $query->where('includes_trainer_access', true);
    }

    /**
     * Scope to sort tiers by sort_order and price.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('price_cents');
    }

    /**
     * Scope to filter by billing period.
     */
    public function scopeWithBillingPeriod($query, BillingPeriod $period)
    {
        return $query->where('billing_period', $period);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the tier is active.
     */
    public function isActive(): bool
    {
        return $this->status === TierStatus::ACTIVE;
    }

    /**
     * Check if the tier has reached its member limit.
     */
    public function hasReachedMemberLimit(): bool
    {
        if (!$this->max_members) {
            return false;
        }

        $activeSubscriptions = $this->subscriptions()
            ->where('status', 'active')
            ->count();

        return $activeSubscriptions >= $this->max_members;
    }

    /**
     * Get the number of available slots.
     */
    public function getAvailableSlots(): ?int
    {
        if (!$this->max_members) {
            return null; // Unlimited
        }

        $activeSubscriptions = $this->subscriptions()
            ->where('status', 'active')
            ->count();

        return max(0, $this->max_members - $activeSubscriptions);
    }

    /**
     * Check if a benefit is included in this tier.
     */
    public function hasBenefit(string $benefit): bool
    {
        return in_array($benefit, $this->benefits ?? []);
    }
}
