# CLAUDE.md

Claude Code guidance for the FitTrack workout tracking platform.

## Project Context

**FitTrack** - Multi-tenant workout tracking platform. Laravel 12 + PostgreSQL + Redis backend API.
- **Backend API Only**: RESTful JSON API. No frontend views/templates/assets.
- **Architecture**: Domain-Driven Design (DDD), Test-Driven Development (TDD), Vertical Slice Architecture
- **Users**: Gyms, Trainers, Trainees (single user can hold multiple roles)

## 📚 Documentation Structure

**IMPORTANT**: Use the optimized `.agents/` folder for all documentation references.

### Primary Reference: `.agents/` Folder
- **Start here**: `.agents/INDEX.md` - Central navigation and quick lookups
- **Current status**: `.agents/implementation/current-status.md` - Progress and next steps
- **Domain models**: `.agents/domain/*.md` - Bounded contexts and entities
- **Business rules**: `.agents/business-rules/core-rules.md` - 26 critical rules
- **Quick reference**: `.agents/reference/quick-reference.md` - Commands, enums, patterns
- **API structure**: `.agents/api/api-overview.md` - Endpoints and responses
- **Full guide**: `.agents/AGENT_USAGE_GUIDE.md` - How to use these files efficiently

### Additional Documentation
- `FitTrack_Project_Document.md` - Complete specification (use `.agents/` for faster lookups)
- `START_HERE.md` - Quick start guide
- `IMPLEMENTATION_PROGRESS.md` - Detailed progress tracking

### **DO NOT READ** `.agents.ignore/` folder unless explicitly requested by user.

## Essential Commands

### Setup
```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed --class=EquipmentSeeder  # CRITICAL: Run first
```

### Testing (TDD Required)
```bash
php artisan test                              # All tests
php artisan test --filter test_method_name   # Specific test
./vendor/bin/pint                            # Format code
```

### Development
```bash
php artisan serve                            # Dev server
php artisan migrate:fresh --seed             # Fresh DB
make docker-up                               # Start PostgreSQL/Redis
```

## Critical Architecture Concepts

### 1. Bounded Contexts (DDD)
- **Identity**: Users, authentication, profiles
- **Gym Management**: Gyms (user-owned entities), equipment inventory, subscriptions
- **Training Content**: Equipment catalog (preset), exercises, workouts, plans
- **Execution**: Sessions, performance logs
- **Analytics**: Progress, personal records
- **Commerce**: Purchases, subscriptions, contracts

### 2. Multi-Role Users
- Single user can be: Trainer + Trainee + Gym Owner simultaneously
- Profiles: TrainerProfile, TraineeProfile (1:1 with User, optional)
- Gyms: Owned by users (NOT a profile), users can own multiple gyms

### 3. Equipment System (CRITICAL)
- Equipment catalog is **PRESET** (~60 items, system-managed)
- Users **CANNOT** create custom equipment
- Exercises link to equipment (many-to-many)
- Gyms select equipment from catalog (many-to-many)
- See `.agents/reference/equipment-catalog.md` for full list

### 4. Workout-Gym Compatibility (Core Feature)
**Rule**: For EVERY exercise in workout, gym must have AT LEAST ONE compatible equipment.

```php
// Algorithm: Set intersection check
function isWorkoutCompatibleWithGym(Workout $workout, Gym $gym): bool {
    $gymEquipmentIds = $gym->equipment->pluck('equipment_id');
    foreach ($workout->exercises as $workoutExercise) {
        $exerciseEquipmentIds = $workoutExercise->exercise->equipment->pluck('equipment_id');
        if ($gymEquipmentIds->intersect($exerciseEquipmentIds)->isEmpty()) {
            return false;
        }
    }
    return true;
}
```
Full algorithm: `.agents/implementation/compatibility-algorithm.md`

### 5. Database Design
- PostgreSQL required (UUID primary keys, GIN indexes, JSONB)
- UUIDs: All primary keys use `gen_random_uuid()`
- 30 migrations created, all ready to run
- See `.agents/database/migrations-overview.md`

## TDD Workflow (MANDATORY)

1. **RED**: Write failing test first
2. **GREEN**: Implement minimum code to pass
3. **REFACTOR**: Improve while keeping tests green

```php
// Example: Feature test structure
public function test_trainer_can_create_workout() {
    // Arrange
    $trainer = TrainerProfile::factory()->create();
    
    // Act
    $response = $this->actingAs($trainer->user)
        ->postJson('/api/v1/workouts', ['name' => 'Test']);
    
    // Assert
    $response->assertStatus(201);
    $this->assertDatabaseHas('workouts', ['name' => 'Test']);
}
```

## Vertical Slice Pattern

Each feature = complete vertical slice:
1. API Route
2. Request Validation (FormRequest)
3. Action/Service Class (business logic)
4. Resource Class (JSON response)
5. Test (written first)

## API Standards

**Base**: `/api/v1`
**Auth**: Laravel Sanctum (token-based)
**Format**: JSON only

**Success Response**:
```json
{"data": {...}, "meta": {...}, "links": {...}}
```

**Error Response**:
```json
{"message": "...", "errors": {"field": ["..."]}}
```

**Status Codes**: 200 (OK), 201 (Created), 204 (No Content), 400 (Bad Request), 401 (Unauthorized), 403 (Forbidden), 404 (Not Found), 422 (Validation Failed), 500 (Error)

## Business Rules (CRITICAL)

**Must enforce**:
1. Equipment is preset - users cannot create
2. System exercises are immutable
3. Workout-gym compatibility required
4. Premium workouts require price > 0
5. One profile per type per user
6. Gyms owned by users (not profiles)
7. Logs immutable after session completion
8. Published workouts create versions on edit

**Full rules**: `.agents/business-rules/core-rules.md` (26 total)

## Key Terminology (Ubiquitous Language)

- **Equipment**: Preset catalog item (Barbell, Dumbbells, etc.)
- **Exercise**: Physical movement with parameters
- **Workout**: Collection of exercises with sets/reps
- **Training Plan**: Multi-week workout schedule
- **Workout Session**: Instance of trainee performing workout
- **Exercise Log**: Performance data for exercise in session
- **Set Log**: Per-set performance data
- **Workout Compatibility**: All exercises have compatible equipment at gym

Full glossary: `.agents/domain/ubiquitous-language.md`

## Implementation Pattern

### Creating New Feature
1. Check `.agents/implementation/current-status.md` for progress
2. Read relevant `.agents/domain/*.md` context
3. Review `.agents/business-rules/core-rules.md` for constraints
4. **Write test first** (TDD)
5. Create Request, Action, Resource classes
6. Register route
7. Implement minimum code
8. Refactor, commit

### Common Patterns
- **Actions**: Single-purpose classes (`CheckWorkoutCompatibilityAction`)
- **Resources**: Laravel API Resources for JSON transformation
- **Events**: Domain events for cross-context communication
- **Caching**: Redis, keys like `{entity}:{id}`, invalidate on write
- **Traits**: `HasUuid` (required all models), `Cacheable`

## Project Phases (Current: Phase 1 Complete)

✅ **Phase 1**: Database migrations (30 created)
⏳ **Phase 2**: Seeders (Equipment ⭐ NEXT, System Exercises)
📋 **Phase 3**: Models & structure (DDD folders, traits, 26 models)
📋 **Phase 4**: Authentication (Sanctum)
📋 **Phase 5-8**: Features (Gym, Content, Execution, Commerce, Analytics)

See `.agents/implementation/current-status.md` for detailed roadmap.

## Quick Lookups

**Need equipment list?** → `.agents/reference/equipment-catalog.md`
**Need muscle groups?** → `.agents/reference/muscle-groups.md`
**Need commands?** → `.agents/reference/quick-reference.md`
**Need current status?** → `.agents/implementation/current-status.md`
**Need navigation?** → `.agents/INDEX.md`

## Important Reminders

- ✅ Backend API only (no frontend code)
- ✅ TDD required (write tests first)
- ✅ Use `.agents/` folder for documentation
- ✅ Equipment is preset (users can't create)
- ✅ Compatibility is core feature
- ✅ Multi-role users supported
- ✅ UUIDs throughout
- ❌ Do NOT read `.agents.ignore/` unless user requests
