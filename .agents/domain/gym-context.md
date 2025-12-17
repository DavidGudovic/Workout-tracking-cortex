# Gym Management Context

## Responsibility
Gym operations, equipment inventory, subscriptions, trainer employment.

## Aggregates

### Gym Aggregate
- **Gym** (root) - owned by User
- **SubscriptionTier**
- **GymEquipment** (links to preset Equipment)

### Employment Aggregate
- **GymTrainer** (root)

## Critical Concept

**Gyms are NOT user profiles**. They are standalone entities owned by users.
- Users can own multiple gyms
- Same as owning multiple blog posts or projects
- Authorization: check `gym.owner_id === auth().id()`

## Entities

### Gym
```
- id: UUID
- owner_id: UUID (references users)
- name: VARCHAR(150)
- slug: VARCHAR(150) UNIQUE
- description: TEXT (nullable)
- logo_url: VARCHAR(500) (nullable)
- cover_image_url: VARCHAR(500) (nullable)
- address_line1: VARCHAR(255) (nullable)
- address_line2: VARCHAR(255) (nullable)
- city: VARCHAR(100) (nullable)
- state: VARCHAR(100) (nullable)
- postal_code: VARCHAR(20) (nullable)
- country: VARCHAR(100) (nullable)
- phone: VARCHAR(50) (nullable)
- website_url: VARCHAR(500) (nullable)
- status: ENUM (pending, active, suspended, closed)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Relationships**:
- belongsTo User (as owner)
- hasMany SubscriptionTier
- belongsToMany Equipment (via gym_equipment)
- belongsToMany TrainerProfile (via gym_trainers)
- hasMany GymSubscription
- hasMany TrainerContract

### GymEquipment
```
- id: UUID
- gym_id: UUID
- equipment_id: UUID (references preset equipment)
- quantity: INTEGER (default: 1)
- notes: TEXT (nullable)
- created_at: TIMESTAMP
```

**Purpose**: Track what equipment a gym has available.

**Unique Constraint**: `(gym_id, equipment_id)` - gym can't have duplicate equipment entries.

**Relationships**:
- belongsTo Gym
- belongsTo Equipment

### SubscriptionTier
```
- id: UUID
- gym_id: UUID
- name: VARCHAR(100)
- description: TEXT (nullable)
- price_cents: INTEGER
- currency: VARCHAR(3) (default: USD)
- billing_period: ENUM (monthly, quarterly, yearly)
- benefits: JSONB (structured list)
- max_members: INTEGER (nullable, for capacity limits)
- includes_trainer_access: BOOLEAN (default: false)
- status: ENUM (active, inactive)
- sort_order: INTEGER (default: 0)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Benefits JSONB Structure**:
```json
[
  "Access to all equipment",
  "Unlimited group classes",
  "1 free personal training session/month"
]
```

**Relationships**:
- belongsTo Gym
- hasMany GymSubscription

### GymTrainer
```
- id: UUID
- gym_id: UUID
- trainer_id: UUID (references trainer_profiles)
- status: ENUM (pending, active, terminated)
- role: ENUM (staff_trainer, head_trainer, contractor)
- hourly_rate_cents: INTEGER (nullable)
- commission_percentage: DECIMAL(5,2) (nullable)
- hired_at: TIMESTAMP (nullable)
- terminated_at: TIMESTAMP (nullable)
- termination_reason: TEXT (nullable)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Unique Constraint**: `(gym_id, trainer_id)` - one active employment per gym-trainer pair.

**Relationships**:
- belongsTo Gym
- belongsTo TrainerProfile

## Business Rules

### Gym Rules
- User can own multiple gyms (gym chains, multiple locations)
- Gym slug must be globally unique (for URLs)
- Gym must have at least one active subscription tier to accept members
- Gym status transitions:
  - **pending** → active (after setup complete)
  - **active** → suspended (policy violation)
  - **active** → closed (permanently closed)
  - **suspended** → active (after review)

### Equipment Rules
- Gyms select equipment from **preset catalog only**
- Cannot create custom equipment
- Quantity is optional (tracking actual count vs just presence)
- Equipment enables workout compatibility checking

### Subscription Tier Rules
- Cannot delete tier if active subscriptions exist
- Price changes create new tier (old subscribers grandfathered)
- Must have at least one active tier
- Billing period affects renewal logic

### Employment Rules
- Trainer can only have **one active employment per gym**
- Trainer can work at multiple gyms simultaneously
- Employment status flow:
  - **pending** → active (trainer accepts)
  - **active** → terminated (employment ends)
- Terminated employment cannot be reactivated (create new)
- Termination requires reason (for records)

## Critical Feature: Workout-Gym Compatibility

**Algorithm**: Check if workout can be performed at gym.

**Rule**: For EVERY exercise in workout, gym must have AT LEAST ONE compatible equipment.

**Implementation**:
```php
function isWorkoutCompatibleWithGym(Workout $workout, Gym $gym): bool
{
    $gymEquipmentIds = $gym->equipment->pluck('id')->toArray();

    foreach ($workout->exercises as $workoutExercise) {
        $exerciseEquipmentIds = $workoutExercise->exercise
            ->equipment->pluck('id')->toArray();

        // Check intersection - gym must have at least one compatible equipment
        if (empty(array_intersect($gymEquipmentIds, $exerciseEquipmentIds))) {
            return false; // Missing equipment for this exercise
        }
    }

    return true; // All exercises compatible
}
```

**Location**: `app/Domain/Gym/Actions/CheckWorkoutCompatibilityAction.php`

**Used By**:
- `GET /api/v1/gyms/{id}/compatible-workouts` - Show workouts trainee can do at gym
- `GET /api/v1/workouts/{id}/compatible-gyms` - Show gyms where workout can be performed
- `POST /api/v1/sessions` - Validate before starting session
- Search/filter features

## Key Use Cases

### Create Gym
1. User authenticated
2. Validate gym details
3. Generate unique slug from name
4. Create Gym with status=pending
5. Owner adds equipment
6. Owner creates subscription tiers
7. Set status=active

### Add Equipment to Gym
1. Gym owner authenticated
2. Load preset equipment catalog
3. Owner selects equipment items
4. Create GymEquipment records
5. Optionally set quantity
6. Compatibility checking now available

### Hire Trainer
1. Gym owner searches trainers
2. Send employment offer (create GymTrainer with status=pending)
3. Trainer receives notification
4. Trainer accepts/declines
5. If accepted: status=active, set hired_at
6. If declined: delete GymTrainer record

### Check Compatibility
1. Trainee selects workout
2. System loads workout exercises
3. System loads gym equipment
4. Run compatibility algorithm
5. Return boolean + incompatible exercises (if any)

## API Endpoints

See `/api/gym-endpoints.md` for full specifications.

**Summary**:
- POST /gyms (create gym)
- GET /gyms (list all gyms)
- GET /gyms/{slug} (gym details)
- PATCH /gyms/{id} (update gym)
- DELETE /gyms/{id} (close gym)
- POST /gyms/{id}/equipment (add equipment)
- DELETE /gyms/{id}/equipment/{equipmentId} (remove equipment)
- POST /gyms/{id}/subscription-tiers (create tier)
- POST /gyms/{id}/trainers (hire trainer)
- GET /gyms/{id}/compatible-workouts (compatibility check)

## Caching Strategy

**Gym Details**:
- Key: `gym:{id}`
- TTL: 1 hour
- Invalidate: on gym update, equipment change, tier change

**Gym Equipment List**:
- Key: `gym:{id}:equipment`
- TTL: 6 hours
- Invalidate: on equipment add/remove

**Compatible Workouts**:
- Key: `gym:{id}:compatible_workouts`
- TTL: 30 minutes
- Invalidate: on equipment change, new workouts published
