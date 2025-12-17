<?php

namespace App\Domain\Gym;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * GymEquipment Model (Pivot Model)
 *
 * Represents the relationship between a gym and preset equipment from the catalog.
 * Tracks quantity and location notes for each piece of equipment.
 *
 * CRITICAL: Equipment is preset - gyms select from the catalog.
 * This table is essential for workout-gym compatibility checking.
 *
 * @property string $id UUID primary key
 * @property string $gym_id
 * @property string $equipment_id
 * @property int $quantity
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 */
class GymEquipment extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'gym_equipment';

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
        'gym_id',
        'equipment_id',
        'quantity',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'quantity' => 1,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the gym that has this equipment.
     */
    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    /**
     * Get the equipment from the preset catalog.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Training\Equipment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to equipment for a specific gym.
     */
    public function scopeForGym($query, string $gymId)
    {
        return $query->where('gym_id', $gymId);
    }

    /**
     * Scope to equipment of a specific type.
     */
    public function scopeOfEquipment($query, string $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the gym has enough of this equipment (quantity check).
     */
    public function hasAvailableQuantity(int $required = 1): bool
    {
        return $this->quantity >= $required;
    }

    /**
     * Increment the quantity of this equipment.
     */
    public function incrementQuantity(int $amount = 1): void
    {
        $this->increment('quantity', $amount);
    }

    /**
     * Decrement the quantity of this equipment.
     */
    public function decrementQuantity(int $amount = 1): void
    {
        $this->decrement('quantity', max(1, $amount));
    }
}
