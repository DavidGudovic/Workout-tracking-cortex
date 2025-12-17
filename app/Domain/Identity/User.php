<?php

namespace App\Domain\Identity;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 *
 * Represents a user in the FitTrack system.
 * Users can have multiple roles:
 * - Trainer (via TrainerProfile)
 * - Trainee (via TraineeProfile)
 * - Gym Owner (via owned Gyms)
 *
 * Note: A single user can hold multiple roles simultaneously.
 *
 * @property string $id UUID primary key
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuid, HasApiTokens;

    /**
     * The table associated with the model.
     */
    protected $table = 'users';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainer profile for this user.
     */
    public function trainerProfile(): HasOne
    {
        return $this->hasOne(TrainerProfile::class);
    }

    /**
     * Get the trainee profile for this user.
     */
    public function traineeProfile(): HasOne
    {
        return $this->hasOne(TraineeProfile::class);
    }

    /**
     * Get the gyms owned by this user.
     * Note: Gyms are owned by users, not profiles.
     */
    public function ownedGyms(): HasMany
    {
        return $this->hasMany(\App\Domain\Gym\Gym::class, 'owner_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Role Check Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the user has a trainer profile.
     */
    public function isTrainer(): bool
    {
        return $this->trainerProfile()->exists();
    }

    /**
     * Check if the user has a trainee profile.
     */
    public function isTrainee(): bool
    {
        return $this->traineeProfile()->exists();
    }

    /**
     * Check if the user owns any gyms.
     */
    public function isGymOwner(): bool
    {
        return $this->ownedGyms()->exists();
    }

    /**
     * Check if the user owns a specific gym.
     */
    public function ownsGym(string $gymId): bool
    {
        return $this->ownedGyms()->where('id', $gymId)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only users with trainer profiles.
     */
    public function scopeTrainers($query)
    {
        return $query->whereHas('trainerProfile');
    }

    /**
     * Scope to only users with trainee profiles.
     */
    public function scopeTrainees($query)
    {
        return $query->whereHas('traineeProfile');
    }

    /**
     * Scope to only users who own gyms.
     */
    public function scopeGymOwners($query)
    {
        return $query->whereHas('ownedGyms');
    }

    /**
     * Scope to only verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }
}
