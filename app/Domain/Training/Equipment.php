<?php

namespace App\Domain\Training;

use App\Shared\Enums\EquipmentCategory;
use App\Shared\Traits\Cacheable;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Equipment Model
 *
 * Represents preset equipment from the system catalog.
 *
 * CRITICAL BUSINESS RULES:
 * - Equipment is PRESET - users cannot create custom equipment
 * - ~60 equipment items seeded from catalog
 * - Gyms select from this catalog (many-to-many via gym_equipment)
 * - Exercises link to equipment (many-to-many via exercise_equipment)
 * - Foundation of workout-gym compatibility checking
 *
 * @property string $id UUID primary key
 * @property string $name
 * @property EquipmentCategory $category
 * @property string|null $description
 * @property string|null $icon_url
 * @property bool $is_common
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon $created_at
 */
class Equipment extends Model
{
    use HasFactory, HasUuid, Cacheable;

    /**
     * The table associated with the model.
     */
    protected $table = 'equipment';

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
        'name',
        'category',
        'description',
        'icon_url',
        'is_common',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'category' => EquipmentCategory::class,
        'is_common' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_common' => true,
        'sort_order' => 0,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the exercises that use this equipment.
     */
    public function exercises(): BelongsToMany
    {
        return $this->belongsToMany(
            Exercise::class,
            'exercise_equipment',
            'equipment_id',
            'exercise_id'
        )->withPivot(['id', 'is_primary', 'notes']);
    }

    /**
     * Get the gyms that have this equipment.
     */
    public function gyms(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Domain\Gym\Gym::class,
            'gym_equipment',
            'equipment_id',
            'gym_id'
        )->withPivot(['id', 'quantity', 'notes'])
          ->withTimestamps();
    }

    /**
     * Get the exercise equipment pivot records.
     */
    public function exerciseEquipment(): HasMany
    {
        return $this->hasMany(ExerciseEquipment::class);
    }

    /**
     * Get the gym equipment pivot records.
     */
    public function gymEquipment(): HasMany
    {
        return $this->hasMany(\App\Domain\Gym\GymEquipment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to only common equipment.
     */
    public function scopeCommon($query)
    {
        return $query->where('is_common', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, EquipmentCategory $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search equipment by name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'ilike', "%{$search}%");
    }

    /**
     * Scope to sort equipment by sort_order and name.
     */
    public function scopeSorted($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this equipment is common.
     */
    public function isCommon(): bool
    {
        return $this->is_common;
    }

    /**
     * Check if this equipment is in a specific category.
     */
    public function isInCategory(EquipmentCategory $category): bool
    {
        return $this->category === $category;
    }

    /**
     * Check if this equipment is bodyweight.
     */
    public function isBodyweight(): bool
    {
        return $this->category === EquipmentCategory::BODYWEIGHT;
    }

    /**
     * Get the number of gyms that have this equipment.
     */
    public function getGymCount(): int
    {
        return $this->gymEquipment()->count();
    }

    /**
     * Get the number of exercises that use this equipment.
     */
    public function getExerciseCount(): int
    {
        return $this->exerciseEquipment()->count();
    }
}
