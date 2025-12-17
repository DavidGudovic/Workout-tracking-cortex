# Muscle Groups Reference

Used in exercises for `primary_muscle_groups` and `secondary_muscle_groups` arrays.

## All Muscle Groups

### Upper Body

**Chest**:
- pectorals
- upper_chest
- lower_chest

**Back**:
- lats
- traps
- rhomboids
- erector_spinae
- lower_back

**Shoulders**:
- deltoids
- front_delts
- side_delts
- rear_delts

**Arms**:
- biceps
- triceps
- forearms

### Core

- abs
- obliques
- serratus
- lower_abs
- upper_abs

### Lower Body

**Legs**:
- quadriceps
- hamstrings
- glutes
- calves
- hip_flexors
- hip_adductors
- hip_abductors

### Full Body

- full_body (for compound movements like burpees)

## Usage in Exercise Seeder

```php
// Example: Bench Press
[
    'name' => 'Barbell Bench Press',
    'primary_muscle_groups' => ['pectorals', 'triceps', 'front_delts'],
    'secondary_muscle_groups' => ['serratus'],
    'is_compound' => true,
]

// Example: Bicep Curl
[
    'name' => 'Barbell Bicep Curl',
    'primary_muscle_groups' => ['biceps'],
    'secondary_muscle_groups' => ['forearms'],
    'is_compound' => false,
]

// Example: Deadlift
[
    'name' => 'Conventional Deadlift',
    'primary_muscle_groups' => ['hamstrings', 'glutes', 'erector_spinae'],
    'secondary_muscle_groups' => ['traps', 'lats', 'forearms'],
    'is_compound' => true,
]

// Example: Burpees
[
    'name' => 'Burpees',
    'primary_muscle_groups' => ['full_body'],
    'secondary_muscle_groups' => [],
    'is_compound' => true,
]
```

## Filtering Exercises by Muscle Group

```php
// Find all exercises targeting chest
Exercise::whereJsonContains('primary_muscle_groups', 'pectorals')->get();

// Find exercises targeting chest OR back
Exercise::where(function($query) {
    $query->whereJsonContains('primary_muscle_groups', 'pectorals')
          ->orWhereJsonContains('primary_muscle_groups', 'lats');
})->get();
```

## PostgreSQL GIN Index

The migrations include a GIN index for efficient array searches:
```sql
CREATE INDEX idx_exercises_muscle_groups ON exercises USING GIN(primary_muscle_groups);
```

This enables fast queries on muscle group filters.

## Compound vs Isolation

**Compound Movements** (`is_compound = true`):
- Multi-joint exercises
- Target multiple muscle groups
- Examples: Squat, Deadlift, Bench Press, Pull-ups

**Isolation Movements** (`is_compound = false`):
- Single-joint exercises
- Target specific muscle group
- Examples: Bicep Curl, Leg Extension, Lateral Raise

## Workout Planning by Muscle Groups

**Push Day**: pectorals, triceps, front_delts
**Pull Day**: lats, traps, rhomboids, biceps
**Leg Day**: quadriceps, hamstrings, glutes, calves
**Full Body**: Multiple groups from all categories
