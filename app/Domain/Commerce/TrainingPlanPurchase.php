<?php

namespace App\Domain\Commerce;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\TrainingPlan;
use App\Shared\Enums\PaymentStatus;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrainingPlanPurchase Model
 *
 * Represents a trainee's purchase of a premium training plan.
 * One-time purchase grants lifetime access to the plan.
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property string $training_plan_id
 * @property int $price_cents
 * @property string $currency
 * @property PaymentStatus $payment_status
 * @property string|null $payment_reference
 * @property \Illuminate\Support\Carbon $purchased_at
 */
class TrainingPlanPurchase extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'training_plan_purchases';

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
        'training_plan_id',
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
     * Get the purchased training plan.
     */
    public function trainingPlan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class);
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
