# Identity & Access Context

## Responsibility
User authentication, authorization, and profile management.

## Aggregates

### User Aggregate
- **User** (root)
- **TrainerProfile** (optional, 1:1 with User)
- **TraineeProfile** (optional, 1:1 with User)

## Entities

### User
```
- id: UUID
- email: VARCHAR(255) UNIQUE
- password: VARCHAR(255)
- email_verified_at: TIMESTAMP (nullable)
- remember_token: VARCHAR(100) (nullable)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Relationships**:
- hasOne TrainerProfile
- hasOne TraineeProfile
- hasMany Gym (as owner)

### TrainerProfile
```
- id: UUID
- user_id: UUID (unique)
- display_name: VARCHAR(100)
- slug: VARCHAR(100) UNIQUE
- bio: TEXT (nullable)
- avatar_url: VARCHAR(500) (nullable)
- cover_image_url: VARCHAR(500) (nullable)
- specializations: TEXT[] (array)
- certifications: JSONB
- years_experience: INTEGER (nullable)
- hourly_rate_cents: INTEGER (nullable)
- currency: VARCHAR(3) (default: USD)
- is_available_for_hire: BOOLEAN (default: true)
- status: ENUM (pending, active, suspended)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Relationships**:
- belongsTo User
- hasMany Exercise (as creator)
- hasMany Workout (as creator)
- hasMany TrainingPlan (as creator)
- belongsToMany Gym (via gym_trainers)
- hasMany TrainerContract

### TraineeProfile
```
- id: UUID
- user_id: UUID (unique)
- display_name: VARCHAR(100)
- avatar_url: VARCHAR(500) (nullable)
- date_of_birth: DATE (nullable)
- gender: VARCHAR(20) (nullable)
- height_cm: DECIMAL(5,2) (nullable)
- weight_kg: DECIMAL(5,2) (nullable)
- fitness_goal: ENUM (nullable)
- experience_level: ENUM (nullable)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

**Fitness Goals**:
- strength
- hypertrophy
- endurance
- weight_loss
- general_fitness
- sport_specific

**Experience Levels**:
- beginner
- intermediate
- advanced
- expert

**Relationships**:
- belongsTo User
- hasMany WorkoutSession
- hasMany WorkoutPurchase
- hasMany TrainingPlanPurchase
- hasMany GymSubscription
- hasMany TrainerContract
- hasMany ProgressSnapshot
- hasMany PersonalRecord
- belongsToMany TrainingPlan (via trainee_active_plans)

## Business Rules

### User Rules
- Email must be unique across all users
- Email verification required for certain actions (configurable)
- Password must meet security requirements (min length, complexity)
- Authentication tokens expire after configurable period

### Profile Rules
- **One profile per user per type** (enforced by unique constraint)
- User can have both Trainer and Trainee profiles simultaneously
- Profile display_name not required to be unique
- Trainer slug must be globally unique (for URLs)
- Profile deletion does NOT delete user (soft delete recommended)

### Authorization Patterns

**Check Trainer Profile**:
```php
if (!auth()->user()->trainerProfile) {
    abort(403, 'Trainer profile required');
}

$trainer = auth()->user()->trainerProfile;
```

**Check Trainee Profile**:
```php
if (!auth()->user()->traineeProfile) {
    abort(403, 'Trainee profile required');
}

$trainee = auth()->user()->traineeProfile;
```

**Check Gym Ownership**:
```php
if ($gym->owner_id !== auth()->id()) {
    abort(403, 'Must be gym owner');
}
```

## Status Transitions

### Trainer Status
- **pending** → active (after verification)
- **active** → suspended (policy violation)
- **suspended** → active (after review)

### Content Access
- Trainers can create content only when status = 'active'
- Suspended trainers: content remains accessible but cannot create new
- Profile deletion: content ownership transferred or deleted based on policy

## Key Use Cases

### User Registration
1. Create User with email/password
2. Send verification email
3. User verifies email
4. User creates Trainer or Trainee profile (or both)

### Trainer Profile Creation
1. User must be authenticated
2. Check user doesn't already have trainer profile
3. Validate display_name, generate slug
4. Set initial status (pending or active based on config)
5. Create TrainerProfile record

### Trainee Profile Creation
1. User must be authenticated
2. Check user doesn't already have trainee profile
3. Validate fitness goals and experience level
4. Create TraineeProfile record

### Profile Switching (Frontend)
- Frontend determines which profile context to use
- Backend validates user has required profile for action
- Single auth token, multiple profile contexts

## Authentication

**Laravel Sanctum** (Token-based API auth):
- POST /api/v1/auth/register
- POST /api/v1/auth/login (returns token)
- POST /api/v1/auth/logout (revokes token)
- Middleware: `auth:sanctum`

**Token Format**:
```json
{
  "token": "1|abc123...",
  "expires_at": "2024-12-31T23:59:59Z"
}
```

## Policies

### TrainerProfilePolicy
- `view`: any authenticated user
- `update`: owner only
- `delete`: owner only

### TraineeProfilePolicy
- `view`: owner or contracted trainers
- `update`: owner only
- `delete`: owner only

## API Endpoints

See `/api/identity-endpoints.md` for full specifications.

**Summary**:
- POST /auth/register
- POST /auth/login
- POST /auth/logout
- GET /auth/user
- POST /profiles/trainer
- GET /profiles/trainer/{slug}
- PATCH /profiles/trainer
- POST /profiles/trainee
- GET /profiles/trainee
- PATCH /profiles/trainee
