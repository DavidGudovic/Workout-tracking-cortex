# Training Content Context

## Responsibility
Equipment catalog, exercise and workout content management, training plan creation.

## Aggregates

### Equipment Aggregate (System-managed)
- **Equipment** (root) - preset catalog, immutable by users

### Exercise Aggregate
- **Exercise** (root)
- **ExerciseMedia**
- **ExerciseEquipment** (links to preset Equipment)

### Workout Aggregate
- **Workout** (root)
- **WorkoutExercise**

### Training Plan Aggregate
- **TrainingPlan** (root)
- **TrainingPlanWeek**
- **TrainingPlanDay**
- **TrainingPlanWorkout**

## Entities

### Equipment (Preset Catalog)
```
- id: UUID
- name: VARCHAR(100) UNIQUE
- category: ENUM
- description: TEXT (nullable)
- icon_url: VARCHAR(500) (nullable)
- is_common: BOOLEAN (default: true)
- sort_order: INTEGER (default: 0)
- created_at: TIMESTAMP
```

**Categories**:
- free_weights
- machines
- cardio
- bodyweight
- accessories
- cable
- plyometric

**CRITICAL**: Equipment is system-managed. Users CANNOT create equipment.

**Seeded Data**: ~60 items from preset catalog (see `/reference/equipment-catalog.md`)

### Exercise
```
- id: UUID
- creator_id: UUID (nullable for system, required for custom)
- type: ENUM (system, custom)
- visibility: ENUM (private, public_pool)
- name: VARCHAR(100)
- description: TEXT (nullable)
- instructions: TEXT (nullable)
- exercise_type: ENUM (repetition, duration, distance)
- difficulty: ENUM (beginner, intermediate, advanced, expert)
- primary_muscle_groups: TEXT[] (array)
- secondary_muscle_groups: TEXT[] (array)
- is_compound: BOOLEAN (default: false)
- calories_per_minute: DECIMAL(5,2) (nullable)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
- published_at: TIMESTAMP (nullable)
```

**Exercise Types**:
- **repetition**: Counted reps (e.g., bench press, squats)
- **duration**: Timed (e.g., plank, wall sit)
- **distance**: Distance-based (e.g., running, rowing)

**Muscle Groups**: See `/reference/muscle-groups.md`

**Relationships**:
- belongsTo TrainerProfile (as creator, nullable for system)
- belongsToMany Equipment (via exercise_equipment)
- hasMany ExerciseMedia
- belongsToMany Workout (via workout_exercises)

### ExerciseEquipment
```
- id: UUID
- exercise_id: UUID
- equipment_id: UUID
- is_primary: BOOLEAN (default: false)
- notes: VARCHAR(255) (nullable)
```

**Purpose**: Define which equipment can be used for an exercise.

**Example**:
- Bench Press: Barbell (primary), Dumbbells (alternative), Smith Machine (alternative)
- Push-ups: None/Bodyweight (primary)
- Pull-ups: Pull-up Bar (primary), Assisted Pull-up Machine (alternative)

**Unique Constraint**: `(exercise_id, equipment_id)`

### ExerciseMedia
```
- id: UUID
- exercise_id: UUID
- type: ENUM (video_url, image_url, gif_url)
- url: VARCHAR(1000)
- title: VARCHAR(200) (nullable)
- is_primary: BOOLEAN (default: false)
- sort_order: INTEGER (default: 0)
- created_at: TIMESTAMP
```

**Media hosted externally** (YouTube, Vimeo, image CDN).

### Workout
```
- id: UUID
- creator_id: UUID (trainer)
- name: VARCHAR(150)
- description: TEXT (nullable)
- cover_image_url: VARCHAR(500) (nullable)
- difficulty: ENUM
- estimated_duration_minutes: INTEGER (nullable)
- pricing_type: ENUM (free, premium)
- price_cents: INTEGER (nullable, required if premium)
- currency: VARCHAR(3) (default: USD)
- status: ENUM (draft, published, archived)
- version: INTEGER (default: 1)
- tags: TEXT[] (array)
- total_exercises: INTEGER (default: 0)
- total_sets: INTEGER (default: 0)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
- published_at: TIMESTAMP (nullable)
```

**Constraint**: `pricing_type = 'premium'` requires `price_cents IS NOT NULL`

**Relationships**:
- belongsTo TrainerProfile (as creator)
- belongsToMany Exercise (via workout_exercises)
- hasMany WorkoutExercise
- hasMany WorkoutSession
- hasMany WorkoutPurchase

### WorkoutExercise
```
- id: UUID
- workout_id: UUID
- exercise_id: UUID
- sort_order: INTEGER
- sets: INTEGER (default: 1)
- target_reps: INTEGER (nullable)
- target_duration_seconds: INTEGER (nullable)
- target_distance_meters: INTEGER (nullable)
- rest_seconds: INTEGER (default: 60)
- notes: TEXT (nullable)
- superset_group: INTEGER (nullable)
- is_optional: BOOLEAN (default: false)
- created_at: TIMESTAMP
```

**Constraint**: At least one target must be set (reps, duration, or distance)

**Superset Support**: Exercises with same `superset_group` value are performed back-to-back.

**Relationships**:
- belongsTo Workout
- belongsTo Exercise

### TrainingPlan
```
- id: UUID
- creator_id: UUID (trainer)
- name: VARCHAR(150)
- description: TEXT (nullable)
- cover_image_url: VARCHAR(500) (nullable)
- goal: ENUM (nullable)
- difficulty: ENUM
- duration_weeks: INTEGER
- days_per_week: INTEGER (1-7)
- pricing_type: ENUM (free, premium)
- price_cents: INTEGER (nullable)
- currency: VARCHAR(3) (default: USD)
- status: ENUM (draft, published, archived)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
- published_at: TIMESTAMP (nullable)
```

**Goals**:
- strength
- hypertrophy
- endurance
- weight_loss
- general_fitness
- sport_specific

**Constraint**: `days_per_week BETWEEN 1 AND 7`

### TrainingPlanWeek
```
- id: UUID
- training_plan_id: UUID
- week_number: INTEGER
- name: VARCHAR(100) (nullable, e.g., "Deload Week")
- notes: TEXT (nullable)
```

**Unique Constraint**: `(training_plan_id, week_number)`

### TrainingPlanDay
```
- id: UUID
- training_plan_week_id: UUID
- day_number: INTEGER (1-7)
- name: VARCHAR(100) (nullable, e.g., "Push Day", "Rest")
- is_rest_day: BOOLEAN (default: false)
- notes: TEXT (nullable)
```

**Unique Constraint**: `(training_plan_week_id, day_number)`

### TrainingPlanWorkout
```
- id: UUID
- training_plan_day_id: UUID
- workout_id: UUID
- sort_order: INTEGER (default: 0)
- is_optional: BOOLEAN (default: false)
```

**Purpose**: Assign workouts to specific days in training plan.

## Business Rules

### Equipment Rules
- **Preset catalog only** - ~60 items
- Seeded on initial deployment
- Admin-managed (future feature for adding)
- Users select, never create

### Exercise Rules
- **System exercises**: immutable by trainers, available to all
- **Custom exercises**: creator can edit until published to pool
- **Public pool**: exercises become community property (creator credited)
- Exercise must specify at least one equipment option
- "None (Bodyweight)" is valid equipment for bodyweight exercises
- Primary/secondary muscle groups from predefined list

### Workout Rules
- Must have at least one exercise
- Premium workouts require `price_cents > 0`
- Status flow: draft → published → archived
- **Versioning**: Edits to published workouts create new versions
- Purchasers keep access to purchased version
- Cannot delete workout if sessions/purchases exist (archive instead)

### Training Plan Rules
- Must have at least one workout
- Week structure must be complete (all weeks have days)
- `days_per_week` determines how many days per week are active
- Rest days explicitly marked
- Cannot delete plan if active trainees exist (archive instead)

### Workout-Gym Compatibility
- See `/domain/gym-context.md` for algorithm
- Used for recommendations and filtering

## Key Use Cases

### Create System Exercise (Seeder)
1. Define exercise details
2. Assign muscle groups
3. Link to equipment via ExerciseEquipment
4. Set type='system', visibility='public_pool'
5. Set creator_id=NULL

### Create Custom Exercise (Trainer)
1. Trainer authenticated
2. Validate exercise details
3. Select equipment from catalog
4. Create Exercise with type='custom', visibility='private'
5. Optionally publish to pool (visibility='public_pool', published_at=now)

### Create Workout
1. Trainer authenticated
2. Create Workout (status='draft')
3. Add exercises via WorkoutExercise
4. Set targets (reps/duration), rest periods
5. Optionally set pricing
6. Publish (status='published', published_at=now)

### Create Training Plan
1. Trainer authenticated
2. Create TrainingPlan (status='draft')
3. Create weeks (TrainingPlanWeek)
4. Create days (TrainingPlanDay) for each week
5. Assign workouts to days (TrainingPlanWorkout)
6. Publish (status='published')

## Caching Strategy

**Equipment Catalog**:
- Key: `equipment:catalog`
- TTL: 24 hours (rarely changes)
- Invalidate: on admin equipment update

**Exercise by ID**:
- Key: `exercise:{id}`
- TTL: 1 hour
- Invalidate: on exercise update

**Workout by ID**:
- Key: `workout:{id}`
- TTL: 1 hour
- Invalidate: on workout update, exercise changes

**Trainer's Workouts**:
- Key: `trainer:{id}:workouts`
- TTL: 30 minutes
- Invalidate: on workout create/update/delete

## API Endpoints

See `/api/training-content-endpoints.md` for full specifications.

**Summary**:
- GET /equipment (list preset catalog)
- POST /exercises (create custom)
- GET /exercises (search/filter)
- GET /exercises/{id}
- POST /workouts
- GET /workouts
- GET /workouts/{id}
- POST /training-plans
- GET /training-plans
- GET /training-plans/{id}
