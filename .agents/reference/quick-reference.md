# Quick Reference Guide

Fast lookup for common information.

## Tech Stack
- **Framework**: Laravel 12
- **Language**: PHP 8.3+
- **Database**: PostgreSQL 15+
- **Cache**: Redis 7+
- **Auth**: Laravel Sanctum (token-based)

## Commands

### Development
```bash
# Start dev server
php artisan serve

# Run tests
php artisan test

# Run specific test
php artisan test --filter test_method_name

# Code formatting
./vendor/bin/pint

# Migrations
php artisan migrate
php artisan migrate:fresh --seed

# Seeders
php artisan db:seed --class=EquipmentSeeder

# Tinker (REPL)
php artisan tinker
```

### Docker
```bash
make docker-up
make docker-down
```

## Key File Locations

### Configuration
- `.env` - Environment configuration
- `config/database.php` - Database config
- `config/sanctum.php` - Auth config

### Migrations
- `database/migrations/` - All 30 migrations

### Seeders
- `database/seeders/EquipmentSeeder.php` - Equipment catalog
- `database/seeders/SystemExerciseSeeder.php` - System exercises

### Models (Future)
- `app/Domain/Identity/Models/User.php`
- `app/Domain/Training/Models/Exercise.php`
- `app/Domain/Training/Models/Workout.php`

### Routes
- `routes/api.php` - API routes

### Tests
- `tests/Feature/Api/` - API tests
- `tests/Unit/` - Unit tests

## Environment Variables

### Required
```bash
APP_NAME=FitTrack
APP_ENV=local
APP_KEY=base64:...  # Generate with: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fittrack
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## Database Schema Summary

### Core Tables
- **users** - Base user accounts
- **trainer_profiles** - Trainer profiles (1:1 with users)
- **trainee_profiles** - Trainee profiles (1:1 with users)
- **gyms** - Gym entities (owned by users)

### Equipment & Content
- **equipment** - Preset catalog (~60 items)
- **exercises** - System and custom exercises
- **workouts** - Workout compositions
- **training_plans** - Multi-week programs

### Execution
- **workout_sessions** - Workout execution instances
- **exercise_logs** - Per-exercise performance
- **set_logs** - Per-set data

### Commerce
- **workout_purchases** - Workout purchases
- **gym_subscriptions** - Gym memberships
- **trainer_contracts** - Personal training

### Analytics
- **progress_snapshots** - Daily progress data
- **personal_records** - PR tracking

## API Endpoints Summary

Base: `/api/v1`

### Auth
- POST `/auth/register`
- POST `/auth/login`
- POST `/auth/logout`

### Profiles
- POST `/profiles/trainer`
- POST `/profiles/trainee`

### Gyms
- POST `/gyms`
- GET `/gyms`
- GET `/gyms/{id}/compatible-workouts`

### Content
- GET `/equipment`
- POST `/exercises`
- POST `/workouts`
- POST `/training-plans`

### Execution
- POST `/sessions`
- GET `/sessions`
- POST `/sessions/{id}/exercises/{exerciseLogId}/sets`

### Analytics
- GET `/progress/dashboard`
- GET `/progress/personal-records`

## Common Queries

### Find Exercise by Muscle Group
```php
Exercise::whereJsonContains('primary_muscle_groups', 'pectorals')->get();
```

### Get Trainer's Workouts
```php
$trainer->workouts()->published()->get();
```

### Check Workout-Gym Compatibility
```php
app(CheckWorkoutCompatibilityAction::class)->execute($workout, $gym);
```

### Get Trainee's Active Plans
```php
$trainee->activeTrainingPlans()->with('trainingPlan')->get();
```

## Enum Values

### Exercise Types
- `repetition`, `duration`, `distance`

### Difficulty Levels
- `beginner`, `intermediate`, `advanced`, `expert`

### Pricing Types
- `free`, `premium`

### Status Values
- **Content**: `draft`, `published`, `archived`
- **Session**: `started`, `in_progress`, `completed`, `abandoned`
- **Payment**: `pending`, `completed`, `failed`, `refunded`

### Equipment Categories
- `bodyweight`, `free_weights`, `machines`, `cable`, `cardio`, `plyometric`, `accessories`

## Relationships Cheat Sheet

### User
- hasOne TrainerProfile
- hasOne TraineeProfile
- hasMany Gym (as owner)

### Workout
- belongsTo TrainerProfile (creator)
- belongsToMany Exercise (via workout_exercises)

### WorkoutSession
- belongsTo TraineeProfile
- belongsTo Workout
- hasMany ExerciseLog

### Gym
- belongsTo User (owner)
- belongsToMany Equipment (via gym_equipment)

## Critical Business Rules

1. Equipment is PRESET - users cannot create
2. System exercises are IMMUTABLE
3. Workout-gym compatibility: ALL exercises need compatible equipment
4. One profile per type per user
5. Gyms owned by users, not profiles
6. Premium workouts require price > 0
7. Published workouts create versions on edit
8. Logs immutable after session completion

## Next Steps Reminder

1. Run migrations
2. Create & run Equipment Seeder ⭐
3. Create & run System Exercise Seeder
4. Create DDD folder structure
5. Create HasUuid trait
6. Create models with relationships
7. Create factories
8. Install Sanctum
9. Implement features with TDD

See `/implementation/current-status.md` for detailed roadmap.
