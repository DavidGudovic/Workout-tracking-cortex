<?php

namespace App\Domain\Gym;

use App\Domain\Identity\User;
use App\Shared\Enums\GymStatus;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Database\Factories\GymFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Gym Model
 *
 * Represents a gym (fitness facility) in the system.
 * Gyms are owned by users (NOT profiles) and users can own multiple gyms.
 * Gyms have equipment from the preset catalog and can have associated trainers.
 *
 * @property string $id UUID primary key
 * @property string $owner_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_url
 * @property string|null $cover_image_url
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $phone
 * @property string|null $website_url
 * @property GymStatus $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Gym extends Model
{
    use HasFactory, HasUuid, Cacheable;

    static function newFactory(): GymFactory
    {
        return GymFactory::new();
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'gyms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'logo_url',
        'cover_image_url',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'country',
        'phone',
        'website_url',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => GymStatus::class,
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
    | Model Events
    |--------------------------------------------------------------------------
    */

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug from name if not provided
        static::creating(function ($gym) {
            if (empty($gym->slug) && !empty($gym->name)) {
                $gym->slug = Str::slug($gym->name);

                // Ensure uniqueness
                $count = 1;
                $originalSlug = $gym->slug;
                while (static::where('slug', $gym->slug)->exists()) {
                    $gym->slug = $originalSlug . '-' . $count++;
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
     * Get the owner of this gym.
     * Note: Gyms are owned by users, not profiles.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the subscription tiers for this gym.
     */
    public function subscriptionTiers(): HasMany
    {
        return $this->hasMany(SubscriptionTier::class);
    }

    /**
     * Get the equipment in this gym (via gym_equipment pivot).
     * Returns the preset equipment catalog items available at this gym.
     */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Training\Equipment::class,
            'gym_equipment',
            'gym_id',
            'equipment_id'
        )->withPivot(['id', 'quantity', 'notes'])
          ->withTimestamps();
    }

    /**
     * Get the gym equipment pivot records.
     */
    public function gymEquipment(): HasMany
    {
        return $this->hasMany(GymEquipment::class);
    }

    /**
     * Get the trainer associations for this gym.
     */
    public function trainerAssociations(): HasMany
    {
        return $this->hasMany(GymTrainer::class);
    }

    /**
     * Get the gym subscriptions for this gym.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(\App\Domain\Commerce\GymSubscription::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the full address as a single string.
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Get the gym's profile URL.
     */
    public function getProfileUrlAttribute(): string
    {
        return '/gyms/' . $this->slug;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only active gyms.
     */
    public function scopeActive($query)
    {
        return $query->where('status', GymStatus::ACTIVE);
    }

    /**
     * Scope to gyms owned by a specific user.
     */
    public function scopeOwnedBy($query, string $userId)
    {
        return $query->where('owner_id', $userId);
    }

    /**
     * Scope to search gyms by name or location.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('city', 'ilike', "%{$search}%")
              ->orWhere('state', 'ilike', "%{$search}%")
              ->orWhere('slug', 'ilike', "%{$search}%");
        });
    }

    /**
     * Scope to filter gyms by location.
     */
    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'ilike', $city);
    }

    /**
     * Scope to filter gyms by state.
     */
    public function scopeInState($query, string $state)
    {
        return $query->where('state', 'ilike', $state);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the gym is active.
     */
    public function isActive(): bool
    {
        return $this->status === GymStatus::ACTIVE;
    }

    /**
     * Check if a user owns this gym.
     */
    public function isOwnedBy(string $userId): bool
    {
        return $this->owner_id === $userId;
    }

    /**
     * Check if the gym has a specific equipment.
     */
    public function hasEquipment(string $equipmentId): bool
    {
        return $this->equipment()->where('equipment_id', $equipmentId)->exists();
    }

    /**
     * Get the IDs of all equipment in this gym.
     * Used for workout-gym compatibility checking.
     */
    public function getEquipmentIds(): array
    {
        return $this->equipment()->pluck('equipment_id')->toArray();
    }
}
