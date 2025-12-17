<?php

namespace App\Domain\Commerce;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\Workout;
use App\Shared\Enums\PaymentStatus;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WorkoutPurchase Model
 *
 * Represents a trainee's purchase of a premium workout.
 * One-time purchase grants lifetime access to the workout.
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property string $workout_id
 * @property int $workout_version
 * @property int $price_cents
 * @property string $currency
 * @property PaymentStatus $payment_status
 * @property string|null $payment_reference
 * @property \Illuminate\Support\Carbon $purchased_at
 */
class WorkoutPurchase extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'workout_purchases';

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
        'trainee_id',
        'workout_id',
        'workout_version',
        'price_cents',
        'currency',
        'payment_status',
        'payment_reference',
        'purchased_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'workout_version' => 'integer',
        'price_cents' => 'integer',
        'payment_status' => PaymentStatus::class,
        'purchased_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'payment_status' => 'pending',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee who made this purchase.
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(TraineeProfile::class, 'trainee_id');
    }

    /**
     * Get the purchased workout.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
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

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to purchases for a specific trainee.
     */
    public function scopeForTrainee($query, string $traineeId)
    {
        return $query->where('trainee_id', $traineeId);
    }

    /**
     * Scope to completed purchases.
     */
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', PaymentStatus::COMPLETED);
    }

    /**
     * Scope to pending purchases.
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', PaymentStatus::PENDING);
    }

    /**
     * Scope to failed purchases.
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', PaymentStatus::FAILED);
    }

    /**
     * Scope to refunded purchases.
     */
    public function scopeRefunded($query)
    {
        return $query->where('payment_status', PaymentStatus::REFUNDED);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->payment_status === PaymentStatus::COMPLETED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->payment_status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->payment_status === PaymentStatus::FAILED;
    }

    /**
     * Check if purchase was refunded.
     */
    public function isRefunded(): bool
    {
        return $this->payment_status === PaymentStatus::REFUNDED;
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(string $paymentReference = null): void
    {
        $this->update([
            'payment_status' => PaymentStatus::COMPLETED,
            'payment_reference' => $paymentReference,
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['payment_status' => PaymentStatus::FAILED]);
    }

    /**
     * Refund the purchase.
     */
    public function refund(): void
    {
        $this->update(['payment_status' => PaymentStatus::REFUNDED]);
    }
}
