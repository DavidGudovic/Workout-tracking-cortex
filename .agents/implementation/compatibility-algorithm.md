# Workout-Gym Compatibility Algorithm

**THE CORE UNIQUE FEATURE** of FitTrack.

## Problem Statement

Determine if a trainee can perform a workout at a specific gym based on equipment availability.

## Algorithm

**Rule**: For EVERY exercise in the workout, the gym must have AT LEAST ONE compatible equipment option.

**Set Theory**: For each exercise, the intersection of gym equipment and exercise equipment must not be empty.

```
For each exercise E in workout W:
    gym_equipment ∩ exercise_equipment ≠ ∅

If ANY exercise fails this check → workout NOT compatible
If ALL exercises pass this check → workout IS compatible
```

## Implementation

### Location
`app/Domain/Gym/Actions/CheckWorkoutCompatibilityAction.php`

### Code

```php
<?php

namespace App\Domain\Gym\Actions;

use App\Domain\Gym\Models\Gym;
use App\Domain\Training\Models\Workout;

class CheckWorkoutCompatibilityAction
{
    public function execute(Workout $workout, Gym $gym): array
    {
        // Load gym equipment IDs
        $gymEquipmentIds = $gym->equipment->pluck('id')->toArray();

        $isCompatible = true;
        $incompatibleExercises = [];

        // Check each exercise in workout
        foreach ($workout->workoutExercises as $workoutExercise) {
            $exerciseEquipmentIds = $workoutExercise->exercise
                ->equipment
                ->pluck('id')
                ->toArray();

            // Check intersection - gym must have at least one compatible equipment
            $intersection = array_intersect($gymEquipmentIds, $exerciseEquipmentIds);

            if (empty($intersection)) {
                $isCompatible = false;
                $incompatibleExercises[] = [
                    'exercise_id' => $workoutExercise->exercise_id,
                    'exercise_name' => $workoutExercise->exercise->name,
                    'required_equipment' => $workoutExercise->exercise->equipment->pluck('name'),
                ];
            }
        }

        return [
            'is_compatible' => $isCompatible,
            'incompatible_exercises' => $incompatibleExercises,
        ];
    }
}
```

### Usage

```php
// In controller
$result = app(CheckWorkoutCompatibilityAction::class)
    ->execute($workout, $gym);

if ($result['is_compatible']) {
    // Allow trainee to start session
} else {
    // Show which exercises are incompatible
    return response()->json([
        'message' => 'Workout not compatible with gym',
        'incompatible_exercises' => $result['incompatible_exercises'],
    ], 422);
}
```

## Examples

### Example 1: Compatible Workout

**Workout**: "Full Body Strength"
- Exercise 1: Barbell Squat (equipment: Barbell, Smith Machine)
- Exercise 2: Dumbbell Bench Press (equipment: Dumbbells, Barbell)
- Exercise 3: Pull-ups (equipment: Pull-up Bar)

**Gym Equipment**: Barbell, Dumbbells, Pull-up Bar, Treadmill

**Check**:
- Squat: Gym has Barbell ✓ (intersection not empty)
- Bench: Gym has Dumbbells ✓ (intersection not empty)
- Pull-ups: Gym has Pull-up Bar ✓ (intersection not empty)

**Result**: Compatible ✓

### Example 2: Incompatible Workout

**Workout**: "Cable Day"
- Exercise 1: Cable Flyes (equipment: Cable Machine, Cable Crossover)
- Exercise 2: Lat Pulldown (equipment: Lat Pulldown Machine, Cable Machine)
- Exercise 3: Cable Curls (equipment: Cable Machine)

**Gym Equipment**: Barbell, Dumbbells, Pull-up Bar, Treadmill

**Check**:
- Cable Flyes: Gym has NO Cable Machine or Cable Crossover ✗
- Lat Pulldown: Gym has NO Lat Pulldown Machine or Cable Machine ✗
- Cable Curls: Gym has NO Cable Machine ✗

**Result**: Incompatible ✗

### Example 3: Partially Compatible

**Workout**: "Upper Body"
- Exercise 1: Push-ups (equipment: None (Bodyweight))
- Exercise 2: Cable Rows (equipment: Cable Machine, Seated Row Machine)
- Exercise 3: Dumbbell Press (equipment: Dumbbells)

**Gym Equipment**: None (Bodyweight), Dumbbells, Barbell

**Check**:
- Push-ups: Gym has Bodyweight ✓
- Cable Rows: Gym has NO Cable Machine or Seated Row Machine ✗
- Dumbbell Press: Gym has Dumbbells ✓

**Result**: Incompatible ✗ (Cable Rows fails)

## API Endpoints Using This

### 1. Get Compatible Workouts for Gym
```
GET /api/v1/gyms/{gymId}/compatible-workouts
```

**Process**:
1. Load all published workouts
2. For each workout, run compatibility check
3. Return only compatible workouts
4. Include compatibility score (% of exercises compatible)

### 2. Get Compatible Gyms for Workout
```
GET /api/v1/workouts/{workoutId}/compatible-gyms
```

**Process**:
1. Load all active gyms (optionally filter by location)
2. For each gym, run compatibility check
3. Return only compatible gyms
4. Include distance if location provided

### 3. Validate Before Starting Session
```
POST /api/v1/sessions
```

**Body**:
```json
{
  "workout_id": "uuid",
  "gym_id": "uuid" (optional)
}
```

**Process**:
1. If `gym_id` provided, run compatibility check
2. If incompatible, reject with 422 and list issues
3. If compatible or no gym specified, allow session start

## Performance Optimization

### Caching

**Cache compatible workouts per gym**:
```php
$key = "gym:{$gymId}:compatible_workouts";
$ttl = 1800; // 30 minutes

$compatibleWorkoutIds = Cache::remember($key, $ttl, function() use ($gym) {
    // Run compatibility check for all workouts
    return Workout::published()
        ->get()
        ->filter(fn($w) => $this->isCompatible($w, $gym))
        ->pluck('id');
});
```

**Invalidation**:
- When gym equipment changes
- When new workouts published
- When workout exercises modified

### Eager Loading

Always eager load relationships:
```php
$workout = Workout::with([
    'workoutExercises.exercise.equipment'
])->find($id);

$gym = Gym::with('equipment')->find($id);
```

### Database Query Optimization

Pre-filter at database level when possible:
```sql
-- Find gyms that have specific equipment
SELECT DISTINCT g.*
FROM gyms g
INNER JOIN gym_equipment ge ON g.id = ge.gym_id
WHERE ge.equipment_id IN (?, ?, ?)  -- Required equipment IDs
```

## Testing

### Unit Tests

```php
public function test_workout_is_compatible_with_gym()
{
    $gym = Gym::factory()->create();
    $barbell = Equipment::where('name', 'Barbell')->first();
    $gym->equipment()->attach($barbell->id);

    $exercise = Exercise::factory()->create();
    $exercise->equipment()->attach($barbell->id);

    $workout = Workout::factory()->create();
    $workout->exercises()->attach($exercise->id);

    $result = app(CheckWorkoutCompatibilityAction::class)
        ->execute($workout, $gym);

    $this->assertTrue($result['is_compatible']);
    $this->assertEmpty($result['incompatible_exercises']);
}

public function test_workout_is_not_compatible_with_gym()
{
    $gym = Gym::factory()->create();
    $dumbbells = Equipment::where('name', 'Dumbbells')->first();
    $gym->equipment()->attach($dumbbells->id);

    $exercise = Exercise::factory()->create();
    $cableMachine = Equipment::where('name', 'Cable Machine')->first();
    $exercise->equipment()->attach($cableMachine->id);

    $workout = Workout::factory()->create();
    $workout->exercises()->attach($exercise->id);

    $result = app(CheckWorkoutCompatibilityAction::class)
        ->execute($workout, $gym);

    $this->assertFalse($result['is_compatible']);
    $this->assertCount(1, $result['incompatible_exercises']);
}
```

## Business Value

This algorithm enables:
1. **Smart Workout Discovery**: Trainees only see workouts they can actually do
2. **Gym Search**: Find gyms with equipment for specific workouts
3. **Equipment Gap Analysis**: Gyms know what equipment to add
4. **Better UX**: No frustration from incompatible workouts
5. **Competitive Advantage**: Unique feature not found in basic workout trackers
