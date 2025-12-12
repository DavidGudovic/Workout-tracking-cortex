# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**FitTrack** is a multi-tenant workout tracking platform built with Laravel 12, PostgreSQL, and Redis. The application connects Gyms, Trainers, and Trainees, enabling trainers to create and monetize workout content, gyms to manage trainer workforce, and trainees to follow training programs while tracking fitness progress.

**IMPORTANT**: This repository contains ONLY the backend API. Frontend development is handled by a separate team. Focus exclusively on API design, business logic, and data layer implementation.

**Critical Context**: This project uses Domain-Driven Design (DDD), Test-Driven Development (TDD), and Vertical Slice Architecture. All implementation must follow these architectural patterns.

## Development Commands

### Setup
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Testing
```bash
# Run all tests
composer test
# or
php artisan test

# Run specific test file
php artisan test tests/Feature/SomeTest.php

# Run specific test method
php artisan test --filter test_method_name

# Run tests with coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code
./vendor/bin/pint

# Run linter
./vendor/bin/pint --test
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh database with seeders
php artisan migrate:fresh --seed

# Create migration
php artisan make:migration create_table_name

# Create seeder
php artisan make:seeder TableNameSeeder
```

### Development Server
```bash
# Start development server
php artisan serve

# Or use the full dev environment (with queue, logs, and Vite)
composer dev
```

### Docker
```bash
# Start Docker services (PostgreSQL, Redis)
make docker-up

# Stop Docker services
make docker-down
```

## Architecture Overview

### Backend-Only API Development

**Scope**: This repository delivers a RESTful JSON API. All endpoints return JSON responses. No views, blade templates, or frontend assets.

**API-First Approach**:
- Design endpoints with clear contracts
- Provide comprehensive JSON responses
- Include proper HTTP status codes
- Document all endpoints for frontend team
- Design resources/transformers for consistent response structure

### Domain-Driven Design Structure

The application is organized into **Bounded Contexts**, each representing a distinct business domain:

1. **Identity & Access Context** - User authentication, authorization, profiles (Trainer/Trainee)
2. **Gym Management Context** - Gym entities (owned by users), equipment inventory, subscriptions, trainer employment
3. **Training Content Context** - Equipment catalog (preset), exercises, workouts, training plans
4. **Workout Execution Context** - Session tracking, performance logging, set logs
5. **Analytics Context** - Progress tracking, personal records, statistics
6. **Commerce Context** - Purchases, subscriptions, trainer contracts

### Vertical Slice Architecture

Each feature is implemented as a **vertical slice** containing all layers:
- API Route
- Request Validation
- Application Service / Use Case
- Domain Logic
- Data Access
- Response Formatting (JSON Resources)

**Example slice structure**:
```
src/
└── Modules/
    └── TrainingContent/
        └── Features/
            └── CreateWorkout/
                ├── CreateWorkoutRequest.php
                ├── CreateWorkoutAction.php
                ├── CreateWorkoutResource.php
                └── CreateWorkoutTest.php
```

### Key Architectural Patterns

**Aggregates**: Each bounded context has aggregates (domain object clusters). The aggregate root is the entry point for all operations within that cluster. All business rules are enforced at the aggregate level.

**Ubiquitous Language**: Use domain terminology from FitTrack_Project_Document.md consistently in code:
- Equipment (preset catalog item)
- Exercise (physical movement with parameters)
- Workout (collection of exercises)
- Training Plan (scheduled program of workouts)
- Workout Session (instance of trainee performing workout)
- Subscription Tier (gym membership level)
- Gym Equipment (equipment a gym has available)
- Exercise Equipment (equipment options for an exercise)

**Multi-Role Users**: Users can hold multiple roles simultaneously (Trainer + Trainee + Gym Owner). Handle via profile context, not separate accounts.

**Equipment System**:
- Equipment catalog is **preset** (system-managed, not user-editable)
- Exercises link to equipment options (one or more)
- Gyms select equipment from preset catalog
- **Workout Compatibility** = all exercises have at least one equipment option the gym has

### Database Design

- **Database**: PostgreSQL (required for advanced features)
- **Cache**: Redis (for performance optimization)
- **Primary Keys**: UUID (gen_random_uuid())
- **Timestamps**: All tables use created_at/updated_at

**Critical relationships**:
- User → TrainerProfile (1:1, optional)
- User → TraineeProfile (1:1, optional)
- User → Gym (1:many, owner relationship)
- Gym → Equipment (many:many via gym_equipment)
- Exercise → Equipment (many:many via exercise_equipment)

## Test-Driven Development (TDD)

**CRITICAL**: Write tests BEFORE implementation. Follow Red-Green-Refactor cycle.

### Test Structure
```php
// Feature Test Example
public function test_trainer_can_create_workout()
{
    // Arrange
    $trainer = TrainerProfile::factory()->create();
    $exercises = Exercise::factory()->count(3)->create();

    // Act
    $response = $this->actingAs($trainer->user)
        ->postJson('/api/v1/workouts', [
            'name' => 'Full Body Workout',
            'exercises' => $exercises->pluck('id'),
        ]);

    // Assert
    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'creator',
            'exercises',
        ],
    ]);
    $this->assertDatabaseHas('workouts', [
        'name' => 'Full Body Workout',
        'creator_id' => $trainer->id,
    ]);
}
```

### Test Categories
- **Unit Tests**: Test domain logic, business rules, value objects
- **Feature Tests**: Test HTTP endpoints, JSON responses, authentication
- **Integration Tests**: Test database interactions, external services

## Implementation Guidelines

### 1. Bounded Context Isolation
- Each context should have minimal coupling to others
- Cross-context communication via domain events or service interfaces
- Keep domain models within their context boundaries

### 2. Business Rules Enforcement
- All business rules documented in FitTrack_Project_Document.md must be enforced
- Validate at aggregate boundaries
- Example: "Workout must have at least one exercise", "Premium workouts require price > 0"

### 3. Immutability Rules
- System exercises are immutable by trainers
- Public pool exercises become community property (creator credited)
- Exercise logs are immutable once session is completed
- Workout modifications create new versions (purchasers keep access to purchased version)

### 4. Preset Equipment Catalog
- Equipment table is seeded with standardized items (see Appendix B in project doc)
- DO NOT allow users to create custom equipment
- Gyms select from preset catalog
- Exercises link to preset equipment options

### 5. Workout-Gym Compatibility
```php
// Check if workout can be performed at a gym
// For EVERY exercise, gym must have AT LEAST ONE compatible equipment
function isWorkoutCompatibleWithGym(Workout $workout, Gym $gym): bool
{
    $gymEquipmentIds = $gym->equipment->pluck('equipment_id');

    foreach ($workout->exercises as $workoutExercise) {
        $exerciseEquipmentIds = $workoutExercise->exercise
            ->equipment->pluck('equipment_id');

        if ($gymEquipmentIds->intersect($exerciseEquipmentIds)->isEmpty()) {
            return false;
        }
    }

    return true;
}
```

### 6. Caching Strategy
- Use Redis for frequently accessed data
- Cache keys follow pattern: `{entity}:{id}`, `{entity}:{filter}:page:{n}`
- Invalidate on write operations
- See Section 9 of project document for detailed cache strategy

## API Structure

All endpoints under `/api/v1` prefix. All responses are JSON.

### Key endpoint groups:

- `/auth` - Authentication (register, login, logout, password reset)
- `/equipment` - Preset equipment catalog (read-only for users)
- `/gyms` - Gym management (any user can create/own gyms)
- `/trainers` - Trainer profiles and content
- `/trainees` - Trainee profiles and activities
- `/exercises` - Exercise management (system + custom)
- `/workouts` - Workout composition
- `/training-plans` - Multi-week training programs
- `/sessions` - Workout execution and logging
- `/purchases` - Content purchases (payment integration deferred)
- `/subscriptions` - Gym memberships
- `/analytics` - Progress tracking and insights

### API Response Standards

**Success Responses**:
```json
{
  "data": { /* resource data */ },
  "meta": { /* pagination, counts, etc */ },
  "links": { /* HATEOAS links if applicable */ }
}
```

**Error Responses**:
```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

**HTTP Status Codes**:
- 200: OK (successful GET, PUT, PATCH)
- 201: Created (successful POST)
- 204: No Content (successful DELETE)
- 400: Bad Request (validation errors)
- 401: Unauthorized (authentication required)
- 403: Forbidden (insufficient permissions)
- 404: Not Found (resource doesn't exist)
- 422: Unprocessable Entity (validation failed)
- 500: Internal Server Error

## Common Patterns

### Creating a New Feature (Vertical Slice)

1. **Write failing test** (TDD) - API endpoint test
2. **Create Request class** for validation
3. **Create Action/Service class** for business logic
4. **Create Resource class** for JSON transformation
5. **Register route** in appropriate routes file
6. **Implement minimum code** to pass test
7. **Refactor** while keeping tests green

### JSON Resources

Use Laravel API Resources for consistent JSON responses:
```php
class WorkoutResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'difficulty' => $this->difficulty,
            'creator' => new TrainerResource($this->whenLoaded('creator')),
            'exercises' => ExerciseResource::collection($this->whenLoaded('exercises')),
        ];
    }
}
```

### Domain Events

Use Laravel events for cross-context communication:
```php
// After workout session completed
event(new WorkoutSessionCompleted($session));

// Listeners update analytics, check for PRs, etc.
```

### Cache Invalidation

```php
// After updating workout
Cache::forget("workout:{$workout->id}");
Cache::forget("workouts:trainer:{$workout->creator_id}");
Cache::forget("trainee:{$traineeId}:accessible_workouts");
```

## Project Phases

Implementation follows 12-week phase plan (see Section 10 of FitTrack_Project_Document.md):

1. **Phase 1** (Weeks 1-2): Foundation - Auth, profiles, equipment catalog
2. **Phase 2** (Weeks 3-4): Training content - Exercises, workouts
3. **Phase 3** (Week 5): Training plans
4. **Phase 4** (Weeks 6-7): Workout execution and logging
5. **Phase 5** (Week 8): Gym management and compatibility
6. **Phase 6** (Week 9): Commerce (stub payments)
7. **Phase 7** (Weeks 10-11): Analytics and dashboards
8. **Phase 8** (Week 12): Polish and optimization

## Important Notes

- **Backend API Only**: No frontend views, assets, or templates. Pure JSON API.
- **Payment Integration**: Deferred to post-v1.0. Track purchases but stub payment processing
- **User Roles**: Users can have multiple simultaneous roles (Trainer + Trainee + Gym Owner)
- **Gym Ownership**: Gyms are standalone entities owned by users, NOT a user role/profile
- **Exercise Visibility**: System exercises (immutable), Custom (private or public_pool)
- **Equipment**: Preset catalog only - users cannot create custom equipment
- **Compatibility Logic**: Critical for workout recommendations and gym search features

## Reference Documentation

**Primary Source**: `FitTrack_Project_Document.md` contains the complete specification including:
- Ubiquitous language definitions
- All bounded contexts and aggregates
- Complete database schema with indexes
- Business rules for each context
- API endpoint specifications
- Caching strategy details
- Equipment catalog seed data
- Muscle groups reference

When implementing any feature, ALWAYS reference the project document first to ensure alignment with business requirements and domain model.
