# Ubiquitous Language - FitTrack Domain

This document defines the core domain terminology used throughout the FitTrack platform.

## Core Entities

### Equipment System

**Equipment** - A standardized piece of gym equipment from the preset catalog
- Examples: Barbell, Dumbbells, Cable Machine, Treadmill
- **CRITICAL**: System-managed, users CANNOT create custom equipment
- ~60 items in preset catalog

**Exercise Equipment** - The equipment options an exercise can be performed with
- One or more equipment options per exercise
- Example: Chest Press can use Barbell, Dumbbells, or Smith Machine

**Gym Equipment** - Equipment a specific gym has available
- Gyms select from preset equipment catalog
- Used for workout compatibility checking

### Training Content

**Exercise** - A single physical movement with defined parameters
- Has reps/duration target and technique instructions
- Types: repetition, duration, distance
- Categories: system (preset), custom (trainer-created)

**System Exercise** - Pre-defined exercises available to all trainers
- Immutable by trainers
- Foundation exercise library

**Custom Exercise** - Trainer-created exercises
- Can be private or contributed to public pool
- Public pool exercises become community property (creator credited)

**Workout** - A structured collection of exercises
- Includes sets, reps/duration targets, rest periods
- Can be free or premium (paid)
- Has difficulty level and estimated duration

**Training Plan** - A scheduled program of workouts spanning days/weeks
- Week-based structure
- Multiple workouts per week
- Defines progression over time

### Execution

**Workout Session** - An instance of a trainee performing a workout
- Tracks start/end time, completion status
- Records actual performance vs targets
- Can be completed, abandoned, or in-progress

**Exercise Log** - Recorded performance data for a single exercise within a session
- Links to workout exercise and actual exercise
- Tracks completion status

**Set Log** - Performance data for a single set within an exercise
- Records actual reps/duration/distance vs target
- Includes weight, RPE (Rate of Perceived Exertion)
- Tracks warmup sets and sets to failure

### Compatibility

**Workout Compatibility** - Whether a workout can be performed at a gym
- **Algorithm**: For EVERY exercise in workout, gym must have AT LEAST ONE compatible equipment
- Uses set intersection: gym equipment ∩ exercise equipment ≠ ∅
- **CRITICAL FEATURE** - enables workout recommendations and gym search

### Gym Management

**Gym** - A standalone entity owned by a user
- **NOT a user profile or role**
- Users can own multiple gyms
- Has equipment inventory, subscription tiers, employed trainers

**Subscription Tier** - A gym membership level
- Defines pricing, benefits, access levels
- Monthly, quarterly, or yearly billing

**Gym Owner** - Any user who has created a gym
- Not a separate role/profile
- Same user can also be Trainer and/or Trainee

### Commerce

**Premium Workout** - A paid workout created by a trainer
- Requires price > 0
- One-time purchase grants permanent access

**Personal Training Contract** - Agreement between trainee and trainer
- Session-based or time-based
- Tracks sessions used
- Can be gym-affiliated or independent

**Workout Purchase** - Transaction granting trainee access to premium workout
- Includes workout version (edits create new versions)
- Purchasers keep access to purchased version

## User Roles

**User** - Base account entity
- Can have multiple simultaneous roles
- Owns gyms (separate from profiles)

**Trainer** - Fitness professional (via TrainerProfile)
- Creates content (exercises, workouts, plans)
- Can be hired by gyms
- Can work with personal training clients

**Trainee** - Individual seeking fitness guidance (via TraineeProfile)
- Follows workouts and training plans
- Logs workout sessions
- Tracks progress and PRs

**Multi-Role Users** - Single user can be:
- Trainer + Trainee (trainer who also works out)
- Gym Owner + Trainer (gym owner who trains clients)
- Gym Owner + Trainee (gym owner who trains)
- All three simultaneously

## Exercise Types

**Repetition Exercise** - Measured by number of reps
- Example: Bench Press, Squats, Push-ups

**Duration Exercise** - Measured by time
- Example: Plank, Wall Sit, Tempo Work

**Distance Exercise** - Measured by distance covered
- Example: Running, Rowing, Cycling

## Visibility Levels

**System** - System-managed, available to all
- Applied to: Equipment, System Exercises

**Private** - Visible only to creator
- Applied to: Custom Exercises (before publishing)

**Public Pool** - Community-contributed, available to all
- Applied to: Published Custom Exercises
- Creator credited but content becomes community property

## Status Flows

**Workout Session Status**
- STARTED → IN_PROGRESS → COMPLETED
- STARTED → IN_PROGRESS → ABANDONED

**Exercise Log Status**
- PENDING → IN_PROGRESS → COMPLETED
- PENDING → SKIPPED

**Content Status** (Workouts, Plans)
- DRAFT → PUBLISHED → ARCHIVED

**Payment Status**
- PENDING → COMPLETED
- PENDING → FAILED
- COMPLETED → REFUNDED

## Critical Concepts

### Equipment is Preset
- ~60 standardized items
- Users select from catalog
- Cannot create custom equipment

### Workout-Gym Compatibility
- **THE** core unique feature
- All exercises must have compatible equipment at gym
- Enables smart recommendations

### Multi-Role Design
- Single user, multiple profiles
- Gyms owned by users, not profiles
- Authorization checks profile context

### Immutability Rules
- System exercises: immutable by trainers
- Public pool exercises: become community property
- Exercise logs: immutable once session completed
- Workout versions: edits create new versions
