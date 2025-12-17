<?php

namespace App\Domain\Analytics;

use App\Domain\Execution\WorkoutSession;
use App\Domain\Identity\TraineeProfile;
use App\Domain\Training\Exercise;
use App\Shared\Enums\RecordType;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PersonalRecord Model
 *
 * Represents a trainee's personal best for a specific exercise and metric.
 * Tracks historical progression with self-referencing previous_record.
 *
 * @property string $id UUID primary key
 * @property string $trainee_id
 * @property string $exercise_id
 * @property RecordType $record_type
 * @property float $value
 * @property float|null $weight_kg
 * @property int|null $reps
 * @property \Illuminate\Support\Carbon $achieved_at
 * @property string|null $workout_session_id
 * @property string|null $previous_record_id
 * @property \Illuminate\Support\Carbon $created_at
 */
class PersonalRecord extends Model
{
    use HasFactory, HasUuid;

    /**
     * The table associated with the model.
     */
    protected $table = 'personal_records';

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
        'exercise_id',
        'record_type',
        'value',
        'weight_kg',
        'reps',
        'achieved_at',
        'workout_session_id',
        'previous_record_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'record_type' => RecordType::class,
        'value' => 'decimal:2',
        'weight_kg' => 'decimal:2',
        'reps' => 'integer',
        'achieved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the trainee who achieved this record.
     */
    public function trainee(): BelongsTo
    {
        return $this->belongsTo(TraineeProfile::class, 'trainee_id');
    }

    /**
     * Get the exercise for this record.
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Get the workout session where this record was achieved.
     */
    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    /**
     * Get the previous personal record.
     */
    public function previousRecord(): BelongsTo
    {
        return $this->belongsTo(PersonalRecord::class, 'previous_record_id');
    }

    /**
     * Get the record that superseded this one.
     */
    public function nextRecord(): BelongsTo
    {
        return $this->belongsTo(PersonalRecord::class, 'id', 'previous_record_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the improvement over previous record.
     */
    public function getImprovementAttribute(): ?float
    {
        if (!$this->previous_record_id) {
            return null;
        }

        $previous = $this->previousRecord;
        if (!$previous) {
            return null;
        }

        return round($this->value - $previous->value, 2);
    }

    /**
     * Get the improvement percentage over previous record.
     */
    public function getImprovementPercentageAttribute(): ?float
    {
        if (!$this->previous_record_id) {
            return null;
        }

        $previous = $this->previousRecord;
        if (!$previous || $previous->value == 0) {
            return null;
        }

        return round((($this->value - $previous->value) / $previous->value) * 100, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to records for a specific trainee.
     */
    public function scopeForTrainee($query, string $traineeId)
    {
        return $query->where('trainee_id', $traineeId);
    }

    /**
     * Scope to records for a specific exercise.
     */
    public function scopeForExercise($query, string $exerciseId)
    {
        return $query->where('exercise_id', $exerciseId);
    }

    /**
     * Scope to filter by record type.
     */
    public function scopeByType($query, RecordType $type)
    {
        return $query->where('record_type', $type);
    }

    /**
     * Scope to recent records.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('achieved_at', '>=', now()->subDays($days))
            ->orderBy('achieved_at', 'desc');
    }

    /**
     * Scope to sort by achievement date.
     */
    public function scopeSortedByDate($query, string $direction = 'desc')
    {
        return $query->orderBy('achieved_at', $direction);
    }

    /**
     * Scope to only current records (not superseded by newer ones).
     */
    public function scopeCurrent($query)
    {
        return $query->whereNotExists(function ($subquery) {
            $subquery->select('id')
                ->from('personal_records as pr2')
                ->whereColumn('pr2.previous_record_id', 'personal_records.id');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if a value would be a new personal record.
     */
    public static function wouldBeNewRecord(
        string $traineeId,
        string $exerciseId,
        RecordType $recordType,
        float $value
    ): bool {
        $currentRecord = static::getCurrentRecord($traineeId, $exerciseId, $recordType);

        if (!$currentRecord) {
            return true; // First record
        }

        return $value > $currentRecord->value;
    }

    /**
     * Get the current personal record for a trainee, exercise, and type.
     */
    public static function getCurrentRecord(
        string $traineeId,
        string $exerciseId,
        RecordType $recordType
    ): ?self {
        return static::where('trainee_id', $traineeId)
            ->where('exercise_id', $exerciseId)
            ->where('record_type', $recordType)
            ->current()
            ->first();
    }

    /**
     * Create a new personal record, linking to previous if exists.
     */
    public static function createNewRecord(
        string $traineeId,
        string $exerciseId,
        RecordType $recordType,
        float $value,
        ?float $weightKg = null,
        ?int $reps = null,
        ?string $workoutSessionId = null
    ): self {
        $previousRecord = static::getCurrentRecord($traineeId, $exerciseId, $recordType);

        return static::create([
            'trainee_id' => $traineeId,
            'exercise_id' => $exerciseId,
            'record_type' => $recordType,
            'value' => $value,
            'weight_kg' => $weightKg,
            'reps' => $reps,
            'achieved_at' => now(),
            'workout_session_id' => $workoutSessionId,
            'previous_record_id' => $previousRecord?->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this is the current (latest) record.
     */
    public function isCurrent(): bool
    {
        return !static::where('previous_record_id', $this->id)->exists();
    }

    /**
     * Check if this record has been superseded.
     */
    public function isSuperseded(): bool
    {
        return !$this->isCurrent();
    }

    /**
     * Get the age of this record in days.
     */
    public function getAgeInDays(): int
    {
        return $this->achieved_at->diffInDays(now());
    }

    /**
     * Get a formatted string for the record value with context.
     */
    public function getFormattedValue(): string
    {
        $value = $this->value;

        switch ($this->record_type) {
            case RecordType::MAX_WEIGHT:
                $context = $this->reps ? " @ {$this->reps} reps" : '';
                return "{$value}kg{$context}";

            case RecordType::MAX_REPS:
                $context = $this->weight_kg ? " @ {$this->weight_kg}kg" : '';
                return "{$value} reps{$context}";

            case RecordType::MAX_DURATION:
                $minutes = floor($value / 60);
                $seconds = $value % 60;
                return "{$minutes}m {$seconds}s";

            case RecordType::MAX_VOLUME:
                return "{$value}kg total";

            case RecordType::MAX_DISTANCE:
                if ($value >= 1000) {
                    $km = round($value / 1000, 2);
                    return "{$km}km";
                }
                return "{$value}m";

            default:
                return (string) $value;
        }
    }

    /**
     * Get all records in the progression chain.
     */
    public function getProgressionChain(): array
    {
        $chain = [$this];
        $current = $this;

        // Go backward through previous records
        while ($current->previous_record_id) {
            $current = $current->previousRecord;
            if ($current) {
                array_unshift($chain, $current);
            } else {
                break;
            }
        }

        return $chain;
    }
}
