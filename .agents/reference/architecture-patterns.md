# Architecture Patterns

## Domain-Driven Design (DDD)

### Bounded Contexts
The application is organized into distinct business domains:

1. **Identity & Access** - Users, authentication, profiles
2. **Gym Management** - Gym entities, equipment, employment
3. **Training Content** - Equipment catalog, exercises, workouts, plans
4. **Workout Execution** - Sessions, logs, performance tracking
5. **Analytics** - Progress snapshots, personal records
6. **Commerce** - Purchases, subscriptions, contracts

Each context has minimal coupling to others.

### Aggregates
- Cluster of domain objects treated as a unit
- Aggregate root is entry point for operations
- All business rules enforced at aggregate level

**Examples**:
- **Gym Aggregate**: Gym (root), SubscriptionTier, GymEquipment
- **Workout Aggregate**: Workout (root), WorkoutExercise
- **Session Aggregate**: WorkoutSession (root), ExerciseLog, SetLog

### Ubiquitous Language
Domain terminology used consistently throughout:
- Equipment, Exercise, Workout, Training Plan
- Workout Session, Exercise Log, Set Log
- Subscription Tier, Trainer Contract
- Compatibility, Equipment Options

See `/domain/ubiquitous-language.md` for full glossary.

## Vertical Slice Architecture

Each feature implemented as complete vertical slice:
1. API Route
2. Request Validation
3. Application Service / Action
4. Domain Logic
5. Data Access (Repository)
6. Response Resource (JSON)

**Example Structure**:
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

**Benefits**:
- Feature cohesion
- Easy to locate all code for a feature
- Reduces merge conflicts
- Enables feature toggles

## Test-Driven Development (TDD)

### Red-Green-Refactor Cycle
1. **RED**: Write failing test
2. **GREEN**: Implement minimum code to pass
3. **REFACTOR**: Improve code while keeping tests green

### Test Structure
```php
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
$response->assertJsonStructure([...]);
$this->assertDatabaseHas('workouts', [...]);
```

### Test Categories
- **Feature Tests**: HTTP endpoints, JSON responses, auth
- **Unit Tests**: Domain logic, business rules, value objects
- **Integration Tests**: Database interactions, external services

### Coverage Target
- Minimum 80% code coverage
- 100% coverage for critical business rules
- All business rules have tests

## API-First Design

### Backend Only
- Pure RESTful JSON API
- No views, blade templates, or frontend assets
- Frontend team consumes API separately

### Resource-Oriented
- Resources: users, gyms, workouts, sessions
- Standard HTTP verbs: GET, POST, PATCH, DELETE
- Consistent response format

### Versioning
- URL-based versioning: `/api/v1`
- Breaking changes increment version

## Caching Strategy

### Cache Layers
1. **Application Cache** (Redis)
   - Frequently accessed data
   - Query results
   - Computed values

2. **Database Query Cache** (PostgreSQL)
   - Query result caching
   - Materialized views (future)

### Cache Keys Pattern
```
{entity}:{id}
{entity}:{filter}:page:{n}
trainer:{id}:workouts
gym:{id}:compatible_workouts
```

### Invalidation
- Invalidate on write operations
- Use cache tags for group invalidation
- TTL varies by data volatility

See `/reference/caching-strategy.md` for details.

## Multi-Tenancy Approach

### User-Centric Multi-Role
- Single user account
- Multiple roles via profiles
- Gym ownership separate from profiles

**Not using**:
- Separate databases per tenant
- Schema-based multi-tenancy
- Row-level tenancy with tenant_id

**Benefits**:
- Simpler data model
- Cross-tenant interactions possible
- User can switch contexts

## Authentication & Authorization

### Authentication (Sanctum)
- Token-based API authentication
- Stateless (tokens in requests)
- Token expiration configurable

### Authorization (Policies)
- Laravel policies per model
- Policy methods: view, create, update, delete
- Gate checks in controllers

**Pattern**:
```php
// Controller
$this->authorize('update', $workout);

// Policy
public function update(User $user, Workout $workout)
{
    return $user->trainerProfile?->id === $workout->creator_id;
}
```

## Data Access Patterns

### Eloquent ORM
- Primary data access method
- Relationships defined in models
- Query scopes for reusable filters

### Repository Pattern (Optional)
- Abstraction layer over Eloquent
- Used for complex queries
- Testability (mock repositories)

### Action Classes
- Single-purpose classes for business logic
- Invokable or `execute()` method
- Dependency injection

**Example**:
```php
class CheckWorkoutCompatibilityAction
{
    public function execute(Workout $workout, Gym $gym): array
    {
        // Business logic here
    }
}
```

## Error Handling

### Exceptions
- Custom domain exceptions
- HTTP exception responses
- Validation exceptions

**Pattern**:
```php
throw ValidationException::withMessages([
    'field' => ['Error message'],
]);
```

### API Error Format
```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error detail"]
  }
}
```

## Event-Driven Architecture

### Domain Events
- Trigger on domain state changes
- Decouple cross-context logic
- Enable async processing

**Examples**:
- `WorkoutSessionCompleted` → Update analytics
- `PersonalRecordAchieved` → Notify trainee
- `GymEquipmentUpdated` → Invalidate compatibility cache

### Listeners
- Handle events
- Update read models
- Send notifications
- Background processing

## Queue Jobs

### Background Processing
- Progress snapshot generation (daily)
- PR detection after session completion
- Email notifications
- Cache warming

### Queue Configuration
- Redis as queue driver
- Multiple queues: default, high, low
- Retry failed jobs

## Folder Structure

```
app/
├── Domain/                   # DDD Bounded Contexts
│   ├── Identity/
│   ├── Gym/
│   ├── Training/
│   ├── Execution/
│   ├── Analytics/
│   └── Commerce/
├── Http/
│   ├── Controllers/Api/     # API Controllers
│   ├── Requests/            # Form Requests
│   ├── Resources/           # JSON Resources
│   └── Middleware/
├── Shared/                  # Shared Utilities
│   ├── Traits/
│   ├── Exceptions/
│   └── Enums/
├── Actions/                 # Application Actions
├── Policies/                # Authorization Policies
└── Observers/               # Model Observers

database/
├── migrations/
├── seeders/
└── factories/

tests/
├── Feature/                 # Feature Tests
│   └── Api/
└── Unit/                    # Unit Tests
```

## Deployment Architecture

### Services
- **Application**: Laravel API (PHP-FPM)
- **Database**: PostgreSQL
- **Cache**: Redis
- **Queue**: Redis + Laravel Horizon
- **Web Server**: Nginx

### Horizontal Scaling
- Stateless API servers
- Load balancer in front
- Shared PostgreSQL and Redis

### Future Considerations
- Read replicas (PostgreSQL)
- Cache cluster (Redis Sentinel)
- CDN for media (exercise videos)
- Search engine (Algolia/Elasticsearch)
