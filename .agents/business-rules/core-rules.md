# Core Business Rules

Critical business rules that MUST be enforced throughout the application.

## Equipment System Rules

### Rule 1: Equipment is Preset
- Equipment catalog is system-managed
- Users **CANNOT** create custom equipment
- ~60 standardized items in catalog
- Admin-only modifications (future feature)

**Enforcement**:
- No create/update/delete endpoints for equipment (users)
- Equipment seeded at deployment
- Gyms and exercises SELECT from catalog

### Rule 2: Equipment Relationships Required
- Every exercise MUST have at least one equipment option
- "None (Bodyweight)" is valid for bodyweight exercises
- Gyms must select equipment to enable compatibility checking

**Enforcement**:
- Database constraint: exercise_equipment required before exercise publish
- Validation: workout compatibility checks equipment exists

## Exercise Rules

### Rule 3: System Exercises are Immutable
- System exercises (type='system') cannot be edited by trainers
- Only system administrators can modify
- Available to all trainers

**Enforcement**:
- Policy check: `$exercise->type === 'system' → deny edit`
- Controller validation

### Rule 4: Published Exercises Become Community Property
- Custom exercises with visibility='public_pool' are community-owned
- Creator credited but cannot delete
- Edits create new versions

**Enforcement**:
- Policy: cannot delete if `visibility='public_pool'`
- Version tracking on edits

## Workout Rules

### Rule 5: Workouts Must Have Exercises
- Workout cannot be published without at least one exercise
- Draft workouts can be empty

**Enforcement**:
```php
if ($workout->status === 'published' && $workout->exercises()->count() === 0) {
    throw ValidationException::withMessages([
        'exercises' => 'Published workouts must have at least one exercise',
    ]);
}
```

### Rule 6: Premium Workouts Require Price
- If `pricing_type='premium'`, then `price_cents` must be > 0
- Free workouts: `pricing_type='free'`, `price_cents=null`

**Enforcement**:
- Database CHECK constraint
- Laravel validation rule

### Rule 7: Workout Versioning
- Edits to published workouts create new versions
- Purchasers keep access to purchased version
- Version increments automatically

**Enforcement**:
- `workout_purchases` stores `workout_version`
- Access check: purchased version or newer

## Training Plan Rules

### Rule 8: Plans Must Have Complete Structure
- Plan must have weeks
- Each week must have days (up to `days_per_week`)
- Days must have workouts (unless rest day)

**Enforcement**:
- Validation before publish
- Cannot publish incomplete plan

### Rule 9: Plans Cannot Be Deleted If Active
- If trainees have active plans, cannot delete
- Must archive instead

**Enforcement**:
```php
if ($plan->activeTrainees()->exists()) {
    throw new \Exception('Cannot delete plan with active trainees. Archive instead.');
}
```

## Workout Execution Rules

### Rule 10: Sessions Require Workout Access
- Trainee must have access to workout before starting session
- Access = created by trainee's trainer, purchased, or free

**Enforcement**:
```php
if (!$trainee->canAccessWorkout($workout)) {
    abort(403, 'You do not have access to this workout');
}
```

### Rule 11: Exercise Logs are Immutable After Completion
- Once session status = 'completed', logs cannot be edited
- Prevents retroactive cheating/data manipulation

**Enforcement**:
```php
if ($session->status === 'completed') {
    throw new \Exception('Cannot edit completed session');
}
```

### Rule 12: Set Logs Require Valid Targets
- Must have at least one: actual_reps, actual_duration, or actual_distance
- Must match exercise type (repetition, duration, distance)

**Enforcement**:
- Validation rule based on exercise type

## Gym Rules

### Rule 13: Gyms Owned by Users
- Gyms are NOT user profiles
- Any user can own multiple gyms
- Authorization: check `gym.owner_id === auth()->id()`

**Enforcement**:
- GymPolicy: `update()` and `delete()` check ownership

### Rule 14: One Active Employment Per Gym-Trainer
- Trainer can only have one active employment at a gym
- Can work at multiple gyms simultaneously
- Terminated employment cannot be reactivated

**Enforcement**:
- Unique constraint: `(gym_id, trainer_id)` where status='active'
- New employment required after termination

### Rule 15: Subscription Tiers Cannot Be Deleted If Active Subscriptions
- Cannot delete tier with active subscribers
- Must mark as inactive and create new tier

**Enforcement**:
```php
if ($tier->activeSubscriptions()->exists()) {
    throw new \Exception('Cannot delete tier with active subscriptions');
}
```

## Compatibility Rules

### Rule 16: Workout-Gym Compatibility
**THE CORE RULE**

For EVERY exercise in workout, gym must have AT LEAST ONE compatible equipment.

**Enforcement**:
- `CheckWorkoutCompatibilityAction` before session start
- Filter workouts by compatibility
- Show incompatible exercises to user

**Formula**:
```
For each exercise E in workout W:
    gym_equipment ∩ exercise_equipment ≠ ∅
```

## User & Profile Rules

### Rule 17: One Profile Per Type Per User
- User can have max one TrainerProfile
- User can have max one TraineeProfile
- User can have both simultaneously

**Enforcement**:
- Unique constraint: `trainer_profiles.user_id`
- Unique constraint: `trainee_profiles.user_id`

### Rule 18: Email Must Be Unique
- Email addresses are unique across all users
- Case-insensitive comparison

**Enforcement**:
- Unique constraint on `users.email`
- Laravel validation: `unique:users,email`

## Commerce Rules

### Rule 19: One Purchase Per Workout Per Trainee
- Trainee cannot purchase same workout twice
- Duplicate purchase attempts rejected

**Enforcement**:
- Unique constraint: `(trainee_id, workout_id)` on workout_purchases

### Rule 20: Subscriptions Auto-Renew Unless Cancelled
- Active subscriptions renew at `current_period_end`
- Cancelled subscriptions do not renew
- Status changes: active → cancelled → expired

**Enforcement**:
- Scheduled job checks subscriptions daily
- Process renewals for active, non-cancelled

### Rule 21: Contract Sessions Cannot Exceed Total
- Trainer contracts have `total_sessions` limit
- `sessions_used` incremented per session
- Cannot book if `sessions_used >= total_sessions`

**Enforcement**:
```php
if ($contract->sessions_used >= $contract->total_sessions) {
    throw new \Exception('Contract sessions exhausted');
}
```

## Progress & Analytics Rules

### Rule 22: Progress Snapshots Are Daily
- One snapshot per trainee per day
- Generated by scheduled job
- Immutable once created

**Enforcement**:
- Unique constraint: `(trainee_id, snapshot_date)`
- Job runs once daily

### Rule 23: Personal Records Auto-Detected
- PRs detected automatically from set logs
- Cannot be manually created
- Previous PR reference maintained

**Enforcement**:
- Created by `PersonalRecordDetector` service
- No create endpoint for users

## Validation Rules

### Rule 24: UUIDs Required Throughout
- All primary keys are UUIDs
- Foreign keys reference UUIDs
- No auto-incrementing integers

**Enforcement**:
- `HasUuid` trait on all models
- `$keyType = 'string'`, `$incrementing = false`

### Rule 25: Status Transitions Must Be Valid
- Cannot skip status steps (e.g., draft → archived without published)
- Some transitions are irreversible (e.g., terminated employment)

**Enforcement**:
- State machine or validation logic in model methods

## Testing Requirements

### Rule 26: All Business Rules Must Have Tests
- Every rule above requires feature or unit test
- Test both valid and invalid cases
- Test edge cases

**Enforcement**:
- Code review checklist
- Test coverage >80%
