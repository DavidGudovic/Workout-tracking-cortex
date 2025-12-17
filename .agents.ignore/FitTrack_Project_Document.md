# FitTrack - Workout Tracking Platform
## Project Specification Document

**Version:** 1.1  
**Date:** December 2024  
**Tech Stack:** Laravel (Backend), PostgreSQL (Database), Redis (Cache)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Project Scope](#2-project-scope)
3. [Domain Analysis](#3-domain-analysis)
4. [User Roles & Capabilities](#4-user-roles--capabilities)
5. [Bounded Contexts](#5-bounded-contexts)
6. [Feature Specifications](#6-feature-specifications)
7. [Database Design](#7-database-design)
8. [API Structure](#8-api-structure)
9. [Caching Strategy](#9-caching-strategy)
10. [Implementation Phases](#10-implementation-phases)

---

## 1. Executive Summary

FitTrack is a multi-tenant workout tracking platform connecting three primary user types: **Gyms**, **Trainers**, and **Trainees**. The platform enables trainers to create and monetize workout content, gyms to manage their trainer workforce and subscriptions, and trainees to follow training programs while tracking their fitness progress over time.

### Core Value Propositions

- **For Gyms:** Digital presence, trainer management, subscription-based revenue
- **For Trainers:** Content monetization, client management, exercise library contributions
- **For Trainees:** Structured training plans, progress tracking, access to professional content

---

## 2. Project Scope

### In Scope

- User registration and authentication with role-based access
- Gym profile management and subscription tier creation
- Trainer profile management and gym employment relationships
- Exercise library (system-wide and custom trainer exercises)
- Workout composition and training plan creation
- Workout execution tracking with performance logging
- Progress analytics and dashboards
- Content marketplace (premium workouts, gym subscriptions, trainer hiring)
- Basic payment/purchase tracking (payment gateway integration deferred)

### Out of Scope (v1.0)

- Real-time video streaming for live training sessions
- Mobile native applications (API-first approach enables future development)
- Social features (following trainers, sharing progress publicly)
- Nutrition tracking
- Wearable device integrations
- Payment gateway integration (stubbed for future implementation)

### Assumptions

- Users can hold multiple roles (e.g., a trainer can also be a trainee)
- All monetary transactions will be tracked but actual payment processing is deferred
- Video content for exercises is hosted externally (YouTube, Vimeo links)

---

## 3. Domain Analysis

### 3.1 Ubiquitous Language

| Term | Definition |
|------|------------|
| **Equipment** | A standardized piece of gym equipment from the preset catalog (e.g., Barbell, Dumbbells, Cable Machine) |
| **Exercise** | A single physical movement with defined parameters (reps/duration, technique) |
| **System Exercise** | Pre-defined exercises available to all trainers |
| **Custom Exercise** | Trainer-created exercises, either private or contributed to the public pool |
| **Exercise Equipment** | The equipment options an exercise can be performed with (one or more) |
| **Gym Equipment** | Equipment a specific gym has available |
| **Workout** | A structured collection of exercises with sets, reps/duration targets, and rest periods |
| **Workout Compatibility** | Whether a workout can be performed at a gym (all exercises have at least one compatible equipment the gym has) |
| **Training Plan** | A scheduled program of workouts spanning days/weeks |
| **Workout Session** | An instance of a trainee performing a workout |
| **Exercise Log** | Recorded performance data for a single exercise within a session |
| **Subscription Tier** | A gym membership level with defined pricing and benefits |
| **Premium Workout** | A paid workout created by a trainer |
| **Personal Training Contract** | An agreement between a trainee and trainer for ongoing coaching |
| **Gym Owner** | A user who has created and manages one or more gyms |

### 3.2 Domain Model Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              IDENTITY CONTEXT                               │
│  ┌──────────┐         ┌───────────────┐         ┌─────────────────┐        │
│  │   User   │────────<│TrainerProfile │         │ TraineeProfile  │        │
│  │          │         └───────────────┘         └─────────────────┘        │
│  │          │────────<─────────────────────────────────────────────        │
│  └──────────┘                                                              │
│       │                                                                     │
│       │ owns                                                                │
│       ▼                                                                     │
│  ┌──────────┐                                                              │
│  │   Gym    │ (standalone entity, not a profile)                           │
│  └──────────┘                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
                                      │
        ┌─────────────────────────────┼─────────────────────────────┐
        │                             │                             │
        ▼                             ▼                             ▼
┌───────────────────┐    ┌─────────────────────────┐    ┌─────────────────────┐
│   GYM CONTEXT     │    │   TRAINING CONTEXT      │    │  COMMERCE CONTEXT   │
│                   │    │                         │    │                     │
│ • SubscriptionTier│    │ • Equipment (preset)    │    │ • WorkoutPurchase   │
│ • GymTrainer      │    │ • Exercise              │    │ • GymSubscription   │
│ • GymMembership   │    │ • ExerciseEquipment     │    │ • TrainerContract   │
│ • GymEquipment    │    │ • Workout               │    │                     │
│                   │    │ • WorkoutExercise       │    └─────────────────────┘
└───────────────────┘    │ • TrainingPlan          │
                         │ • TrainingPlanWorkout   │
                         └─────────────────────────┘
                                      │
                                      ▼
                         ┌─────────────────────────┐
                         │   EXECUTION CONTEXT     │
                         │                         │
                         │ • WorkoutSession        │
                         │ • ExerciseLog           │
                         │ • ProgressSnapshot      │
                         └─────────────────────────┘
```

---

## 4. User Roles & Capabilities

### 4.1 Trainer

**Description:** A fitness professional who creates content and provides coaching services.

**Capabilities:**
- Create and manage trainer profile (bio, certifications, specializations)
- Create custom exercises (private or public pool contribution)
- Compose workouts from system and custom exercises
- Set workout pricing (free or premium)
- Create training plans from workouts
- Accept/decline gym employment offers
- Manage personal training clients
- View content analytics (downloads, completion rates, revenue)

**Restrictions:**
- Premium content creation may require platform verification (future feature)

### 4.2 Trainee

**Description:** An individual seeking fitness guidance and tracking.

**Capabilities:**
- Create and manage trainee profile (fitness goals, current stats)
- Browse and follow free workouts
- Purchase premium workouts
- Subscribe to gym membership tiers
- Hire trainers for personal coaching
- Start workout sessions and log performance
- View personal progress dashboards
- Manage active training plans
- Check workout/gym equipment compatibility

**Restrictions:**
- Cannot create content for others

### 4.3 Gym Owner (via Gym Entity)

**Description:** Any user can create and manage one or more gyms. Gyms are standalone entities owned by users, not a user role.

**Capabilities:**
- Create and manage gym profiles (name, description, location, images, contact info)
- Define subscription tiers with pricing and benefits
- Manage gym equipment inventory (select from preset equipment list)
- Search and hire trainers (send employment offers)
- Manage employed trainers (assign schedules, terminate employment)
- View gym-wide analytics (member counts, revenue, trainer utilization)
- Feature specific workouts or trainers on their profile

**Note:** A user who owns a gym can also be a Trainer and/or Trainee simultaneously.

### 4.4 Multi-Role Users

Users may hold multiple roles simultaneously. A single user account can have:
- Trainer + Trainee profiles (trainer who also works out)
- Gym ownership + Trainer profile (gym owner who also trains clients)
- Gym ownership + Trainee profile (gym owner who trains)
- All combinations

Additionally, users can own multiple gyms (gym chains or multiple locations).

Role switching is handled via profile context, not separate accounts.

---

## 5. Bounded Contexts

### 5.1 Identity & Access Context

**Responsibility:** User authentication, authorization, profile management

**Aggregates:**
- **User Aggregate**
  - User (root)
  - TrainerProfile  
  - TraineeProfile

**Key Business Rules:**
- Email must be unique across all users
- Users must have at least one profile (trainer or trainee)
- Profile data is role-specific and isolated
- Authentication tokens expire after configurable period
- Users can own multiple gyms (separate from profiles)

### 5.2 Gym Management Context

**Responsibility:** Gym operations, equipment inventory, subscriptions, trainer employment

**Aggregates:**
- **Gym Aggregate**
  - Gym (root) - owned by User
  - SubscriptionTier
  - GymEquipment (links to preset Equipment)
  
- **Employment Aggregate**
  - GymTrainer (root)
  - EmploymentSchedule

**Key Business Rules:**
- A user can own multiple gyms
- Gym must have at least one subscription tier to accept members
- Trainer can only have one active employment per gym
- Employment termination requires notice period handling
- Subscription tiers cannot be deleted if active subscribers exist
- Gym equipment must reference preset equipment catalog
- Equipment availability affects workout compatibility

### 5.3 Training Content Context

**Responsibility:** Equipment catalog, exercise and workout content management

**Aggregates:**
- **Equipment Aggregate** (System-managed)
  - Equipment (root) - preset catalog, not user-editable
  
- **Exercise Aggregate**
  - Exercise (root)
  - ExerciseMedia
  - ExerciseEquipment (links exercise to compatible equipment)
  
- **Workout Aggregate**
  - Workout (root)
  - WorkoutExercise
  - WorkoutTag

- **Training Plan Aggregate**
  - TrainingPlan (root)
  - TrainingPlanWeek
  - TrainingPlanDay
  - TrainingPlanWorkout

**Key Business Rules:**
- Equipment catalog is preset and managed by system administrators
- Exercises must specify at least one compatible equipment option
- "Bodyweight/None" is a valid equipment option for exercises requiring no equipment
- System exercises are immutable by trainers
- Custom exercises can only be edited by creator until published to pool
- Public pool exercises become community property (creator credited)
- Workouts must have at least one exercise
- Training plans must have at least one workout
- Premium workouts require price > 0
- Workout modifications create new versions (purchasers keep access to purchased version)
- Workout compatibility with a gym = all exercises have at least one equipment option the gym has

### 5.4 Workout Execution Context

**Responsibility:** Session tracking and performance logging

**Aggregates:**
- **Workout Session Aggregate**
  - WorkoutSession (root)
  - ExerciseLog
  - SetLog

**Key Business Rules:**
- Session can only be started for accessible workouts (owned, purchased, or free)
- Session status flow: STARTED → IN_PROGRESS → COMPLETED/ABANDONED
- Exercise logs are immutable once session is completed
- Partial completion is allowed and tracked

### 5.5 Analytics Context

**Responsibility:** Progress tracking and statistics

**Aggregates:**
- **Progress Aggregate**
  - TraineeProgress (root)
  - ProgressSnapshot
  - PersonalRecord

**Key Business Rules:**
- Snapshots are calculated daily for active users
- Personal records are automatically detected and recorded
- Historical data is retained indefinitely
- Analytics are eventually consistent (background processing)

### 5.6 Commerce Context

**Responsibility:** Purchases, subscriptions, and contracts

**Aggregates:**
- **Purchase Aggregate**
  - WorkoutPurchase (root)
  
- **Subscription Aggregate**
  - GymSubscription (root)
  - SubscriptionPayment

- **Contract Aggregate**
  - TrainerContract (root)
  - ContractSession

**Key Business Rules:**
- Purchases are one-time and non-refundable (policy configurable)
- Subscriptions auto-renew unless cancelled
- Contracts have defined session counts or time periods
- Access is revoked when subscription/contract expires

---

## 6. Feature Specifications

### 6.1 Exercise Management

#### 6.1.1 Exercise Entity Structure

```
Exercise
├── id: UUID
├── type: ENUM (system, custom)
├── visibility: ENUM (private, public_pool) [only for custom]
├── creator_id: UUID (nullable for system)
├── name: string (max 100)
├── description: text
├── instructions: text (step-by-step technique)
├── exercise_type: ENUM (repetition, duration, distance)
├── difficulty: ENUM (beginner, intermediate, advanced, expert)
├── primary_muscle_groups: string[]
├── secondary_muscle_groups: string[]
├── media: ExerciseMedia[]
├── equipment: ExerciseEquipment[] (links to preset equipment)
├── created_at: timestamp
├── updated_at: timestamp
└── published_at: timestamp (nullable, for pool contribution)
```

#### 6.1.2 Equipment (Preset Catalog)

```
Equipment
├── id: UUID
├── name: string (unique, e.g., "Barbell", "Dumbbell")
├── category: ENUM (free_weights, machines, cardio, bodyweight, accessories, cable)
├── description: text
├── icon_url: string (nullable)
├── is_common: boolean (frequently found in gyms)
├── sort_order: integer
└── created_at: timestamp
```

#### 6.1.3 Exercise-Equipment Relationship

```
ExerciseEquipment
├── id: UUID
├── exercise_id: UUID
├── equipment_id: UUID
├── is_primary: boolean (main equipment vs alternatives)
└── notes: string (nullable, e.g., "Can substitute with resistance band")
```

An exercise can be performed with multiple equipment options. For example:
- Chest Press: Barbell (primary), Dumbbells (alternative), Smith Machine (alternative)
- Pull-ups: Pull-up Bar (primary), Assisted Pull-up Machine (alternative)
- Squats: Bodyweight/None (primary), Barbell (alternative), Dumbbell (alternative)

#### 6.1.4 Exercise Media

```
ExerciseMedia
├── id: UUID
├── exercise_id: UUID
├── type: ENUM (video_url, image_url, gif_url)
├── url: string
├── title: string
├── is_primary: boolean
└── sort_order: integer
```

#### 6.1.5 Exercise Features

- **System Exercise Library:** Pre-populated database of common exercises
- **Custom Exercise Creation:** Trainers create private exercises
- **Pool Contribution:** Trainers can publish custom exercises to public pool
- **Exercise Search:** Filter by muscle group, equipment, difficulty, type
- **Exercise Versioning:** Edits to public pool exercises create new versions

### 6.2 Workout Composition

#### 6.2.1 Workout Entity Structure

```
Workout
├── id: UUID
├── creator_id: UUID (trainer)
├── name: string (max 150)
├── description: text
├── cover_image_url: string (nullable)
├── difficulty: ENUM (beginner, intermediate, advanced, expert)
├── estimated_duration_minutes: integer
├── pricing_type: ENUM (free, premium)
├── price_cents: integer (nullable, required if premium)
├── currency: string (default: USD)
├── status: ENUM (draft, published, archived)
├── version: integer
├── tags: string[]
├── created_at: timestamp
├── updated_at: timestamp
└── published_at: timestamp (nullable)
```

#### 6.2.2 Workout Exercise (Junction)

```
WorkoutExercise
├── id: UUID
├── workout_id: UUID
├── exercise_id: UUID
├── sort_order: integer
├── sets: integer
├── target_reps: integer (nullable, for repetition type)
├── target_duration_seconds: integer (nullable, for duration type)
├── target_distance_meters: integer (nullable, for distance type)
├── rest_seconds: integer
├── notes: text (nullable, trainer tips)
├── superset_group: integer (nullable, for supersets)
└── is_optional: boolean (default: false)
```

#### 6.2.3 Workout Features

- **Drag-and-drop Composer:** Visual workout building interface
- **Superset Support:** Group exercises for back-to-back execution
- **Rest Period Configuration:** Per-exercise rest times
- **Difficulty Calculator:** Auto-suggest difficulty based on exercises
- **Duration Estimator:** Calculate estimated workout time
- **Preview Mode:** Test workout flow before publishing
- **Version Control:** Edit published workouts as new versions

### 6.3 Training Plans

#### 6.3.1 Training Plan Structure

```
TrainingPlan
├── id: UUID
├── creator_id: UUID (trainer)
├── name: string
├── description: text
├── goal: ENUM (strength, hypertrophy, endurance, weight_loss, general_fitness)
├── difficulty: ENUM
├── duration_weeks: integer
├── days_per_week: integer
├── pricing_type: ENUM (free, premium)
├── price_cents: integer (nullable)
├── status: ENUM (draft, published, archived)
└── created_at: timestamp

TrainingPlanWeek
├── id: UUID
├── training_plan_id: UUID
├── week_number: integer
├── name: string (nullable, e.g., "Deload Week")
└── notes: text (nullable)

TrainingPlanDay
├── id: UUID
├── training_plan_week_id: UUID
├── day_number: integer (1-7)
├── name: string (e.g., "Push Day", "Rest")
├── is_rest_day: boolean
└── notes: text (nullable)

TrainingPlanWorkout
├── id: UUID
├── training_plan_day_id: UUID
├── workout_id: UUID
├── sort_order: integer
└── is_optional: boolean
```

#### 6.3.2 Training Plan Features

- **Week-based Planning:** Organize workouts across multiple weeks
- **Progressive Overload Templates:** Define progression rules
- **Rest Day Scheduling:** Explicitly plan recovery
- **Plan Duplication:** Copy and modify existing plans
- **Active Plan Tracking:** Trainees see current week/day

### 6.4 Workout Execution

#### 6.4.1 Session Structure

```
WorkoutSession
├── id: UUID
├── trainee_id: UUID
├── workout_id: UUID
├── workout_version: integer
├── training_plan_id: UUID (nullable)
├── status: ENUM (started, in_progress, completed, abandoned)
├── started_at: timestamp
├── completed_at: timestamp (nullable)
├── total_duration_seconds: integer (nullable)
├── notes: text (nullable, trainee reflection)
├── rating: integer (nullable, 1-5)
└── created_at: timestamp

ExerciseLog
├── id: UUID
├── workout_session_id: UUID
├── workout_exercise_id: UUID
├── exercise_id: UUID
├── status: ENUM (pending, in_progress, completed, skipped)
├── started_at: timestamp (nullable)
├── completed_at: timestamp (nullable)
└── notes: text (nullable)

SetLog
├── id: UUID
├── exercise_log_id: UUID
├── set_number: integer
├── target_reps: integer (nullable)
├── actual_reps: integer (nullable)
├── target_duration_seconds: integer (nullable)
├── actual_duration_seconds: integer (nullable)
├── target_distance_meters: integer (nullable)
├── actual_distance_meters: integer (nullable)
├── weight_kg: decimal (nullable)
├── rpe: integer (nullable, 1-10 Rate of Perceived Exertion)
├── is_warmup: boolean (default: false)
├── is_failure: boolean (default: false)
└── completed_at: timestamp
```

#### 6.4.2 Execution Features

- **Guided Workout Mode:** Step-by-step exercise navigation
- **Rest Timer:** Countdown between sets with audio cue
- **Quick Logging:** Tap to log with smart defaults
- **Previous Performance Display:** Show last session's numbers
- **In-Session Notes:** Add notes per exercise
- **Session Pause/Resume:** Handle interruptions
- **Abandonment Tracking:** Log partial completions

### 6.5 Progress & Analytics

#### 6.5.1 Trainee Dashboard Metrics

**Workout Completion:**
- Total workouts completed (all time, this week, this month)
- Completion rate percentage (completed / started)
- Completion rate trend over time (weekly/monthly graphs)
- Current streak (consecutive days with workouts)
- Longest streak

**Volume Tracking:**
- Total weight lifted (weekly/monthly)
- Total reps performed
- Total workout duration
- Volume by muscle group

**Personal Records:**
- Heaviest weight per exercise
- Most reps at given weight
- Longest duration
- PR history timeline

**Training Plan Progress:**
- Current plan completion percentage
- Days remaining
- Workouts completed vs planned

#### 6.5.2 Progress Snapshot Structure

```
ProgressSnapshot
├── id: UUID
├── trainee_id: UUID
├── snapshot_date: date
├── total_workouts_completed: integer
├── total_workouts_started: integer
├── completion_rate: decimal
├── total_volume_kg: decimal
├── total_duration_minutes: integer
├── active_training_plans: integer
└── created_at: timestamp

PersonalRecord
├── id: UUID
├── trainee_id: UUID
├── exercise_id: UUID
├── record_type: ENUM (max_weight, max_reps, max_duration, max_volume)
├── value: decimal
├── weight_kg: decimal (nullable, for context)
├── achieved_at: timestamp
├── workout_session_id: UUID
└── previous_record_id: UUID (nullable)
```

### 6.6 Gym Features

#### 6.6.1 Gym Entity Structure

```
Gym
├── id: UUID
├── owner_id: UUID (user who owns this gym)
├── name: VARCHAR(150)
├── slug: VARCHAR(150) UNIQUE
├── description: TEXT
├── logo_url: VARCHAR(500)
├── cover_image_url: VARCHAR(500)
├── address_line1: VARCHAR(255)
├── address_line2: VARCHAR(255)
├── city: VARCHAR(100)
├── state: VARCHAR(100)
├── postal_code: VARCHAR(20)
├── country: VARCHAR(100)
├── phone: VARCHAR(50)
├── website_url: VARCHAR(500)
├── status: ENUM (pending, active, suspended, closed)
├── created_at: TIMESTAMP
└── updated_at: TIMESTAMP
```

#### 6.6.2 Gym Equipment

```
GymEquipment
├── id: UUID
├── gym_id: UUID
├── equipment_id: UUID (references preset Equipment)
├── quantity: INTEGER (optional, how many units)
├── notes: TEXT (nullable, e.g., "2nd floor", "members only")
└── created_at: TIMESTAMP
```

Gym owners select from the preset equipment catalog to indicate what their gym has available. This enables:
- **Workout Compatibility Check:** Can a trainee do workout X at gym Y?
- **Gym Search by Equipment:** Find gyms that have specific equipment
- **Smart Recommendations:** Suggest workouts compatible with user's gym

#### 6.6.3 Subscription Tiers

```
SubscriptionTier
├── id: UUID
├── gym_id: UUID
├── name: string
├── description: text
├── price_cents: integer
├── currency: string
├── billing_period: ENUM (monthly, quarterly, yearly)
├── benefits: jsonb (structured benefits list)
├── max_members: integer (nullable, for capacity limits)
├── includes_trainer_access: boolean
├── featured_workouts: UUID[] (workout IDs)
├── status: ENUM (active, inactive)
├── sort_order: integer
└── created_at: timestamp
```

#### 6.6.4 Gym-Trainer Employment

```
GymTrainer
├── id: UUID
├── gym_id: UUID
├── trainer_id: UUID
├── status: ENUM (pending, active, terminated)
├── role: ENUM (staff_trainer, head_trainer, contractor)
├── hourly_rate_cents: integer (nullable)
├── commission_percentage: decimal (nullable)
├── hired_at: timestamp
├── terminated_at: timestamp (nullable)
├── termination_reason: text (nullable)
└── created_at: timestamp
```

### 6.7 Commerce Features

#### 6.7.1 Workout Purchase

```
WorkoutPurchase
├── id: UUID
├── trainee_id: UUID
├── workout_id: UUID
├── workout_version: integer
├── price_cents: integer
├── currency: string
├── payment_status: ENUM (pending, completed, failed, refunded)
├── payment_reference: string (nullable, external payment ID)
└── purchased_at: timestamp
```

#### 6.7.2 Gym Subscription

```
GymSubscription
├── id: UUID
├── trainee_id: UUID
├── gym_id: UUID
├── subscription_tier_id: UUID
├── status: ENUM (active, cancelled, expired, suspended)
├── current_period_start: timestamp
├── current_period_end: timestamp
├── cancelled_at: timestamp (nullable)
├── cancellation_reason: text (nullable)
└── created_at: timestamp
```

#### 6.7.3 Trainer Contract

```
TrainerContract
├── id: UUID
├── trainee_id: UUID
├── trainer_id: UUID
├── gym_id: UUID (nullable, if through gym)
├── contract_type: ENUM (session_based, time_based)
├── total_sessions: integer (nullable, for session_based)
├── sessions_used: integer (default: 0)
├── valid_from: date
├── valid_until: date
├── price_cents: integer
├── status: ENUM (active, completed, cancelled, expired)
└── created_at: timestamp
```

---

## 7. Database Design

### 7.1 Schema Overview

```sql
-- ============================================
-- IDENTITY & ACCESS CONTEXT
-- ============================================

CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE trainer_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    display_name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    bio TEXT NULL,
    avatar_url VARCHAR(500) NULL,
    cover_image_url VARCHAR(500) NULL,
    specializations TEXT[] DEFAULT '{}',
    certifications JSONB DEFAULT '[]',
    years_experience INTEGER NULL,
    hourly_rate_cents INTEGER NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    is_available_for_hire BOOLEAN DEFAULT TRUE,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('pending', 'active', 'suspended')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_trainer_per_user UNIQUE (user_id)
);

CREATE TABLE trainee_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    display_name VARCHAR(100) NOT NULL,
    avatar_url VARCHAR(500) NULL,
    date_of_birth DATE NULL,
    gender VARCHAR(20) NULL,
    height_cm DECIMAL(5,2) NULL,
    weight_kg DECIMAL(5,2) NULL,
    fitness_goal VARCHAR(50) NULL CHECK (fitness_goal IN ('strength', 'hypertrophy', 'endurance', 'weight_loss', 'general_fitness', 'sport_specific')),
    experience_level VARCHAR(20) NULL CHECK (experience_level IN ('beginner', 'intermediate', 'advanced', 'expert')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_trainee_per_user UNIQUE (user_id)
);

-- ============================================
-- GYM MANAGEMENT CONTEXT
-- ============================================

-- Gyms are standalone entities owned by users (not a user profile/role)
CREATE TABLE gyms (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    owner_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    logo_url VARCHAR(500) NULL,
    cover_image_url VARCHAR(500) NULL,
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    phone VARCHAR(50) NULL,
    website_url VARCHAR(500) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('pending', 'active', 'suspended', 'closed')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Index for finding all gyms owned by a user
CREATE INDEX idx_gyms_owner ON gyms(owner_id);

CREATE TABLE subscription_tiers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    gym_id UUID NOT NULL REFERENCES gyms(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price_cents INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    billing_period VARCHAR(20) NOT NULL CHECK (billing_period IN ('monthly', 'quarterly', 'yearly')),
    benefits JSONB DEFAULT '[]',
    max_members INTEGER NULL,
    includes_trainer_access BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE gym_trainers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    gym_id UUID NOT NULL REFERENCES gyms(id) ON DELETE CASCADE,
    trainer_id UUID NOT NULL REFERENCES trainer_profiles(id) ON DELETE CASCADE,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'active', 'terminated')),
    role VARCHAR(30) DEFAULT 'staff_trainer' CHECK (role IN ('staff_trainer', 'head_trainer', 'contractor')),
    hourly_rate_cents INTEGER NULL,
    commission_percentage DECIMAL(5,2) NULL,
    hired_at TIMESTAMP NULL,
    terminated_at TIMESTAMP NULL,
    termination_reason TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_active_employment UNIQUE (gym_id, trainer_id)
);

-- ============================================
-- EQUIPMENT & TRAINING CONTENT CONTEXT
-- ============================================

-- Preset equipment catalog (system-managed, not user-editable)
CREATE TABLE equipment (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(30) NOT NULL CHECK (category IN ('free_weights', 'machines', 'cardio', 'bodyweight', 'accessories', 'cable', 'plyometric')),
    description TEXT NULL,
    icon_url VARCHAR(500) NULL,
    is_common BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- What equipment a gym has
CREATE TABLE gym_equipment (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    gym_id UUID NOT NULL REFERENCES gyms(id) ON DELETE CASCADE,
    equipment_id UUID NOT NULL REFERENCES equipment(id) ON DELETE CASCADE,
    quantity INTEGER DEFAULT 1,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_gym_equipment UNIQUE (gym_id, equipment_id)
);

CREATE TABLE exercises (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    creator_id UUID NULL REFERENCES trainer_profiles(id) ON DELETE SET NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'custom' CHECK (type IN ('system', 'custom')),
    visibility VARCHAR(20) DEFAULT 'private' CHECK (visibility IN ('private', 'public_pool')),
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    instructions TEXT NULL,
    exercise_type VARCHAR(20) NOT NULL CHECK (exercise_type IN ('repetition', 'duration', 'distance')),
    difficulty VARCHAR(20) NOT NULL CHECK (difficulty IN ('beginner', 'intermediate', 'advanced', 'expert')),
    primary_muscle_groups TEXT[] DEFAULT '{}',
    secondary_muscle_groups TEXT[] DEFAULT '{}',
    is_compound BOOLEAN DEFAULT FALSE,
    calories_per_minute DECIMAL(5,2) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    published_at TIMESTAMP NULL
);

-- What equipment can be used to perform an exercise
CREATE TABLE exercise_equipment (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    exercise_id UUID NOT NULL REFERENCES exercises(id) ON DELETE CASCADE,
    equipment_id UUID NOT NULL REFERENCES equipment(id) ON DELETE CASCADE,
    is_primary BOOLEAN DEFAULT FALSE,
    notes VARCHAR(255) NULL,
    CONSTRAINT unique_exercise_equipment UNIQUE (exercise_id, equipment_id)
);

CREATE TABLE exercise_media (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    exercise_id UUID NOT NULL REFERENCES exercises(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL CHECK (type IN ('video_url', 'image_url', 'gif_url')),
    url VARCHAR(1000) NOT NULL,
    title VARCHAR(200) NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE workouts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    creator_id UUID NOT NULL REFERENCES trainer_profiles(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    cover_image_url VARCHAR(500) NULL,
    difficulty VARCHAR(20) NOT NULL CHECK (difficulty IN ('beginner', 'intermediate', 'advanced', 'expert')),
    estimated_duration_minutes INTEGER NULL,
    pricing_type VARCHAR(20) NOT NULL DEFAULT 'free' CHECK (pricing_type IN ('free', 'premium')),
    price_cents INTEGER NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
    version INTEGER NOT NULL DEFAULT 1,
    tags TEXT[] DEFAULT '{}',
    total_exercises INTEGER DEFAULT 0,
    total_sets INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    published_at TIMESTAMP NULL,
    CONSTRAINT premium_requires_price CHECK (
        pricing_type != 'premium' OR price_cents IS NOT NULL
    )
);

CREATE TABLE workout_exercises (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    workout_id UUID NOT NULL REFERENCES workouts(id) ON DELETE CASCADE,
    exercise_id UUID NOT NULL REFERENCES exercises(id) ON DELETE RESTRICT,
    sort_order INTEGER NOT NULL,
    sets INTEGER NOT NULL DEFAULT 1,
    target_reps INTEGER NULL,
    target_duration_seconds INTEGER NULL,
    target_distance_meters INTEGER NULL,
    rest_seconds INTEGER DEFAULT 60,
    notes TEXT NULL,
    superset_group INTEGER NULL,
    is_optional BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT valid_target CHECK (
        target_reps IS NOT NULL OR 
        target_duration_seconds IS NOT NULL OR 
        target_distance_meters IS NOT NULL
    )
);

CREATE TABLE training_plans (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    creator_id UUID NOT NULL REFERENCES trainer_profiles(id) ON DELETE CASCADE,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    cover_image_url VARCHAR(500) NULL,
    goal VARCHAR(30) NULL CHECK (goal IN ('strength', 'hypertrophy', 'endurance', 'weight_loss', 'general_fitness', 'sport_specific')),
    difficulty VARCHAR(20) NOT NULL CHECK (difficulty IN ('beginner', 'intermediate', 'advanced', 'expert')),
    duration_weeks INTEGER NOT NULL,
    days_per_week INTEGER NOT NULL CHECK (days_per_week BETWEEN 1 AND 7),
    pricing_type VARCHAR(20) NOT NULL DEFAULT 'free' CHECK (pricing_type IN ('free', 'premium')),
    price_cents INTEGER NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) NOT NULL DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    published_at TIMESTAMP NULL
);

CREATE TABLE training_plan_weeks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    training_plan_id UUID NOT NULL REFERENCES training_plans(id) ON DELETE CASCADE,
    week_number INTEGER NOT NULL,
    name VARCHAR(100) NULL,
    notes TEXT NULL,
    CONSTRAINT unique_week_number UNIQUE (training_plan_id, week_number)
);

CREATE TABLE training_plan_days (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    training_plan_week_id UUID NOT NULL REFERENCES training_plan_weeks(id) ON DELETE CASCADE,
    day_number INTEGER NOT NULL CHECK (day_number BETWEEN 1 AND 7),
    name VARCHAR(100) NULL,
    is_rest_day BOOLEAN DEFAULT FALSE,
    notes TEXT NULL,
    CONSTRAINT unique_day_number UNIQUE (training_plan_week_id, day_number)
);

CREATE TABLE training_plan_workouts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    training_plan_day_id UUID NOT NULL REFERENCES training_plan_days(id) ON DELETE CASCADE,
    workout_id UUID NOT NULL REFERENCES workouts(id) ON DELETE RESTRICT,
    sort_order INTEGER DEFAULT 0,
    is_optional BOOLEAN DEFAULT FALSE
);

-- ============================================
-- WORKOUT EXECUTION CONTEXT
-- ============================================

CREATE TABLE workout_sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    workout_id UUID NOT NULL REFERENCES workouts(id) ON DELETE RESTRICT,
    workout_version INTEGER NOT NULL,
    training_plan_id UUID NULL REFERENCES training_plans(id) ON DELETE SET NULL,
    training_plan_week_number INTEGER NULL,
    training_plan_day_number INTEGER NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'started' CHECK (status IN ('started', 'in_progress', 'completed', 'abandoned')),
    started_at TIMESTAMP NOT NULL DEFAULT NOW(),
    completed_at TIMESTAMP NULL,
    total_duration_seconds INTEGER NULL,
    total_volume_kg DECIMAL(10,2) NULL,
    notes TEXT NULL,
    rating INTEGER NULL CHECK (rating BETWEEN 1 AND 5),
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE exercise_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    workout_session_id UUID NOT NULL REFERENCES workout_sessions(id) ON DELETE CASCADE,
    workout_exercise_id UUID NOT NULL REFERENCES workout_exercises(id) ON DELETE RESTRICT,
    exercise_id UUID NOT NULL REFERENCES exercises(id) ON DELETE RESTRICT,
    status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'completed', 'skipped')),
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE set_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    exercise_log_id UUID NOT NULL REFERENCES exercise_logs(id) ON DELETE CASCADE,
    set_number INTEGER NOT NULL,
    target_reps INTEGER NULL,
    actual_reps INTEGER NULL,
    target_duration_seconds INTEGER NULL,
    actual_duration_seconds INTEGER NULL,
    target_distance_meters INTEGER NULL,
    actual_distance_meters INTEGER NULL,
    weight_kg DECIMAL(6,2) NULL,
    rpe INTEGER NULL CHECK (rpe BETWEEN 1 AND 10),
    is_warmup BOOLEAN DEFAULT FALSE,
    is_failure BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    CONSTRAINT unique_set_number UNIQUE (exercise_log_id, set_number)
);

-- ============================================
-- ANALYTICS CONTEXT
-- ============================================

CREATE TABLE progress_snapshots (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    snapshot_date DATE NOT NULL,
    total_workouts_completed INTEGER DEFAULT 0,
    total_workouts_started INTEGER DEFAULT 0,
    completion_rate DECIMAL(5,2) DEFAULT 0,
    total_volume_kg DECIMAL(12,2) DEFAULT 0,
    total_duration_minutes INTEGER DEFAULT 0,
    total_reps INTEGER DEFAULT 0,
    active_training_plans INTEGER DEFAULT 0,
    current_streak_days INTEGER DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_daily_snapshot UNIQUE (trainee_id, snapshot_date)
);

CREATE TABLE personal_records (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    exercise_id UUID NOT NULL REFERENCES exercises(id) ON DELETE CASCADE,
    record_type VARCHAR(20) NOT NULL CHECK (record_type IN ('max_weight', 'max_reps', 'max_duration', 'max_volume', 'max_distance')),
    value DECIMAL(10,2) NOT NULL,
    weight_kg DECIMAL(6,2) NULL,
    reps INTEGER NULL,
    achieved_at TIMESTAMP NOT NULL,
    workout_session_id UUID REFERENCES workout_sessions(id) ON DELETE SET NULL,
    previous_record_id UUID NULL REFERENCES personal_records(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================
-- COMMERCE CONTEXT
-- ============================================

CREATE TABLE workout_purchases (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    workout_id UUID NOT NULL REFERENCES workouts(id) ON DELETE RESTRICT,
    workout_version INTEGER NOT NULL,
    price_cents INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('pending', 'completed', 'failed', 'refunded')),
    payment_reference VARCHAR(255) NULL,
    purchased_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_workout_purchase UNIQUE (trainee_id, workout_id)
);

CREATE TABLE training_plan_purchases (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    training_plan_id UUID NOT NULL REFERENCES training_plans(id) ON DELETE RESTRICT,
    price_cents INTEGER NOT NULL,
    currency VARCHAR(3) NOT NULL,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (payment_status IN ('pending', 'completed', 'failed', 'refunded')),
    payment_reference VARCHAR(255) NULL,
    purchased_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_plan_purchase UNIQUE (trainee_id, training_plan_id)
);

CREATE TABLE gym_subscriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    gym_id UUID NOT NULL REFERENCES gyms(id) ON DELETE RESTRICT,
    subscription_tier_id UUID NOT NULL REFERENCES subscription_tiers(id) ON DELETE RESTRICT,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'cancelled', 'expired', 'suspended')),
    current_period_start TIMESTAMP NOT NULL,
    current_period_end TIMESTAMP NOT NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    payment_reference VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE trainer_contracts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    trainer_id UUID NOT NULL REFERENCES trainer_profiles(id) ON DELETE RESTRICT,
    gym_id UUID NULL REFERENCES gyms(id) ON DELETE SET NULL,
    contract_type VARCHAR(20) NOT NULL CHECK (contract_type IN ('session_based', 'time_based')),
    total_sessions INTEGER NULL,
    sessions_used INTEGER DEFAULT 0,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    price_cents INTEGER NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'completed', 'cancelled', 'expired')),
    payment_reference VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ============================================
-- TRAINEE ACTIVE PLANS (Junction)
-- ============================================

CREATE TABLE trainee_active_plans (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    trainee_id UUID NOT NULL REFERENCES trainee_profiles(id) ON DELETE CASCADE,
    training_plan_id UUID NOT NULL REFERENCES training_plans(id) ON DELETE CASCADE,
    started_at TIMESTAMP NOT NULL DEFAULT NOW(),
    current_week INTEGER DEFAULT 1,
    current_day INTEGER DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'paused', 'completed', 'abandoned')),
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_active_plan UNIQUE (trainee_id, training_plan_id)
);

-- ============================================
-- INDEXES
-- ============================================

-- Users & Profiles
CREATE INDEX idx_trainer_profiles_user ON trainer_profiles(user_id);
CREATE INDEX idx_trainee_profiles_user ON trainee_profiles(user_id);

-- Equipment
CREATE INDEX idx_equipment_category ON equipment(category);
CREATE INDEX idx_gym_equipment_gym ON gym_equipment(gym_id);
CREATE INDEX idx_gym_equipment_equipment ON gym_equipment(equipment_id);
CREATE INDEX idx_exercise_equipment_exercise ON exercise_equipment(exercise_id);
CREATE INDEX idx_exercise_equipment_equipment ON exercise_equipment(equipment_id);

-- Exercises
CREATE INDEX idx_exercises_creator ON exercises(creator_id);
CREATE INDEX idx_exercises_type ON exercises(type);
CREATE INDEX idx_exercises_visibility ON exercises(visibility);
CREATE INDEX idx_exercises_muscle_groups ON exercises USING GIN(primary_muscle_groups);

-- Workouts
CREATE INDEX idx_workouts_creator ON workouts(creator_id);
CREATE INDEX idx_workouts_status ON workouts(status);
CREATE INDEX idx_workouts_pricing ON workouts(pricing_type);
CREATE INDEX idx_workouts_tags ON workouts USING GIN(tags);
CREATE INDEX idx_workout_exercises_workout ON workout_exercises(workout_id);

-- Training Plans
CREATE INDEX idx_training_plans_creator ON training_plans(creator_id);
CREATE INDEX idx_training_plan_weeks_plan ON training_plan_weeks(training_plan_id);
CREATE INDEX idx_training_plan_days_week ON training_plan_days(training_plan_week_id);

-- Sessions & Logs
CREATE INDEX idx_workout_sessions_trainee ON workout_sessions(trainee_id);
CREATE INDEX idx_workout_sessions_workout ON workout_sessions(workout_id);
CREATE INDEX idx_workout_sessions_status ON workout_sessions(status);
CREATE INDEX idx_workout_sessions_date ON workout_sessions(started_at);
CREATE INDEX idx_exercise_logs_session ON exercise_logs(workout_session_id);
CREATE INDEX idx_set_logs_exercise_log ON set_logs(exercise_log_id);

-- Analytics
CREATE INDEX idx_progress_snapshots_trainee ON progress_snapshots(trainee_id);
CREATE INDEX idx_progress_snapshots_date ON progress_snapshots(snapshot_date);
CREATE INDEX idx_personal_records_trainee ON personal_records(trainee_id);
CREATE INDEX idx_personal_records_exercise ON personal_records(exercise_id);

-- Commerce
CREATE INDEX idx_workout_purchases_trainee ON workout_purchases(trainee_id);
CREATE INDEX idx_gym_subscriptions_trainee ON gym_subscriptions(trainee_id);
CREATE INDEX idx_gym_subscriptions_gym ON gym_subscriptions(gym_id);
CREATE INDEX idx_trainer_contracts_trainee ON trainer_contracts(trainee_id);
CREATE INDEX idx_trainer_contracts_trainer ON trainer_contracts(trainer_id);

-- Active Plans
CREATE INDEX idx_trainee_active_plans_trainee ON trainee_active_plans(trainee_id);
CREATE INDEX idx_trainee_active_plans_status ON trainee_active_plans(status);

-- Gyms
CREATE INDEX idx_subscription_tiers_gym ON subscription_tiers(gym_id);
CREATE INDEX idx_gym_trainers_gym ON gym_trainers(gym_id);
CREATE INDEX idx_gym_trainers_trainer ON gym_trainers(trainer_id);
```

### 7.2 Entity Relationship Diagram

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                                    USER DOMAIN                                           │
│                                                                                          │
│  ┌─────────┐         ┌─────────────────┐                                                 │
│  │  users  │────────<│trainer_profiles │                                                 │
│  │         │         └────────┬────────┘                                                 │
│  │         │                  │                                                          │
│  │         │         ┌────────┴────────┐                                                 │
│  │         │────────<│trainee_profiles │                                                 │
│  │         │         └────────┬────────┘                                                 │
│  │         │                  │                                                          │
│  │         │ owns    ┌────────┴────────┐                                                 │
│  │         │────────<│      gyms       │ (standalone entities, not profiles)             │
│  └─────────┘         └────────┬────────┘                                                 │
│                               │                                                          │
└───────────────────────────────┼──────────────────────────────────────────────────────────┘
                                │
        ┌───────────────────────┼───────────────────────┐
        │                       │                       │
        ▼                       ▼                       ▼
┌───────────────────┐  ┌────────────────────┐  ┌─────────────────────┐
│   GYM CONTEXT     │  │  EXECUTION DOMAIN  │  │   COMMERCE DOMAIN   │
│                   │  │                    │  │                     │
│ subscription_tiers│  │ workout_sessions   │  │ workout_purchases   │
│ gym_trainers      │  │ exercise_logs      │  │ plan_purchases      │
│ gym_equipment ◄───┼──┼──────────┐         │  │ gym_subscriptions   │
│       │           │  │          │         │  │ trainer_contracts   │
└───────┼───────────┘  │          │         │  └─────────────────────┘
        │              │          │         │
        ▼              │          │         │
┌───────────────────┐  │          │         │
│ EQUIPMENT DOMAIN  │  │          │         │
│                   │  │          │         │
│ equipment (preset)│  │          │         │
│       │           │  │          │         │
└───────┼───────────┘  │          │         │
        │              │          │         │
        ▼              │          │         │
┌───────────────────┐  │          │         │
│  TRAINING DOMAIN  │  │          │         │
│                   │  │          │         │
│ exercises ◄───────┼──┼──────────┘         │
│     │             │  │                    │
│     ▼             │  │   set_logs         │
│ exercise_equipment│  │                    │
│ exercise_media    │  └────────────────────┘
│                   │
│ workouts          │
│     │             │
│     ▼             │
│ workout_exercises │
│                   │
│ training_plans    │
│     │             │
│     ▼             │
│ plan_weeks        │
│     │             │
│     ▼             │
│ plan_days         │
│     │             │
│     ▼             │
│ plan_workouts     │
└───────────────────┘

EQUIPMENT RELATIONSHIPS:
========================
equipment (preset catalog)
    │
    ├──< exercise_equipment >── exercises
    │     (what equipment an exercise can use)
    │
    └──< gym_equipment >── gyms
          (what equipment a gym has)

COMPATIBILITY CHECK:
===================
Workout compatible with Gym IF:
  FOR EVERY exercise in workout:
    gym_equipment ∩ exercise_equipment ≠ ∅
```

---

## 8. API Structure

### 8.1 RESTful Endpoint Organization

```
/api/v1
│
├── /auth
│   ├── POST   /register              # User registration
│   ├── POST   /login                 # Authentication
│   ├── POST   /logout                # Invalidate token
│   ├── POST   /refresh               # Refresh token
│   ├── POST   /forgot-password       # Password reset request
│   └── POST   /reset-password        # Password reset execution
│
├── /users
│   ├── GET    /me                    # Current user profile
│   ├── PUT    /me                    # Update user
│   ├── GET    /me/roles              # List user roles/profiles
│   └── GET    /me/gyms               # List gyms owned by user
│
├── /equipment                        # Preset equipment catalog
│   ├── GET    /                      # List all equipment
│   └── GET    /categories            # List equipment by category
│
├── /gyms
│   ├── GET    /                      # List gyms (public)
│   ├── POST   /                      # Create gym (any user can create)
│   ├── GET    /{slug}                # Get gym details
│   ├── PUT    /{id}                  # Update gym (owner only)
│   ├── DELETE /{id}                  # Delete gym (owner only)
│   ├── GET    /{id}/trainers         # List gym trainers
│   ├── POST   /{id}/trainers/invite  # Invite trainer
│   ├── DELETE /{id}/trainers/{tid}   # Remove trainer
│   ├── GET    /{id}/equipment        # List gym equipment
│   ├── POST   /{id}/equipment        # Add equipment to gym
│   ├── DELETE /{id}/equipment/{eid}  # Remove equipment from gym
│   ├── PUT    /{id}/equipment/{eid}  # Update gym equipment (quantity, notes)
│   ├── GET    /{id}/tiers            # List subscription tiers
│   ├── POST   /{id}/tiers            # Create tier
│   ├── PUT    /{id}/tiers/{tid}      # Update tier
│   ├── DELETE /{id}/tiers/{tid}      # Delete tier
│   ├── GET    /{id}/members          # List gym members
│   └── GET    /{id}/compatible-workouts # List workouts doable at this gym
│
├── /trainers
│   ├── GET    /                      # List trainers (public)
│   ├── POST   /                      # Create trainer profile
│   ├── GET    /{slug}                # Get trainer details
│   ├── PUT    /{id}                  # Update trainer
│   ├── GET    /{id}/workouts         # List trainer workouts
│   ├── GET    /{id}/plans            # List trainer plans
│   ├── GET    /{id}/exercises        # List trainer exercises
│   └── GET    /{id}/clients          # List trainer clients
│
├── /trainees
│   ├── POST   /                      # Create trainee profile
│   ├── GET    /me                    # Get own trainee profile
│   ├── PUT    /me                    # Update trainee profile
│   ├── GET    /me/dashboard          # Get dashboard data
│   ├── GET    /me/workouts           # List accessible workouts
│   ├── GET    /me/plans              # List active plans
│   ├── GET    /me/purchases          # List purchases
│   ├── GET    /me/subscriptions      # List subscriptions
│   ├── GET    /me/sessions           # List workout sessions
│   └── GET    /me/records            # List personal records
│
├── /exercises
│   ├── GET    /                      # List exercises (system + pool)
│   ├── POST   /                      # Create custom exercise
│   ├── GET    /{id}                  # Get exercise details
│   ├── PUT    /{id}                  # Update exercise
│   ├── DELETE /{id}                  # Delete exercise
│   ├── POST   /{id}/publish          # Publish to public pool
│   ├── GET    /{id}/equipment        # List equipment for exercise
│   ├── POST   /{id}/equipment        # Add equipment to exercise
│   ├── DELETE /{id}/equipment/{eid}  # Remove equipment from exercise
│   └── GET    /muscle-groups         # List muscle group options
│
├── /workouts
│   ├── GET    /                      # List workouts (public)
│   ├── POST   /                      # Create workout
│   ├── GET    /{id}                  # Get workout details
│   ├── PUT    /{id}                  # Update workout
│   ├── DELETE /{id}                  # Delete workout
│   ├── POST   /{id}/publish          # Publish workout
│   ├── POST   /{id}/duplicate        # Duplicate workout
│   ├── GET    /{id}/exercises        # List workout exercises
│   ├── POST   /{id}/exercises        # Add exercise to workout
│   ├── PUT    /{id}/exercises/{eid}  # Update workout exercise
│   ├── DELETE /{id}/exercises/{eid}  # Remove exercise
│   ├── POST   /{id}/exercises/reorder # Reorder exercises
│   ├── GET    /{id}/required-equipment # List all equipment needed
│   └── GET    /{id}/compatible-gyms  # List gyms where workout can be done
│
├── /training-plans
│   ├── GET    /                      # List plans (public)
│   ├── POST   /                      # Create plan
│   ├── GET    /{id}                  # Get plan details
│   ├── PUT    /{id}                  # Update plan
│   ├── DELETE /{id}                  # Delete plan
│   ├── POST   /{id}/publish          # Publish plan
│   ├── GET    /{id}/structure        # Get full plan structure
│   ├── PUT    /{id}/structure        # Update plan structure
│   └── GET    /{id}/required-equipment # List all equipment needed
│
├── /sessions
│   ├── POST   /                      # Start workout session
│   ├── GET    /{id}                  # Get session details
│   ├── PUT    /{id}                  # Update session (complete/abandon)
│   ├── GET    /{id}/logs             # Get exercise logs
│   ├── POST   /{id}/logs             # Log exercise
│   ├── PUT    /{id}/logs/{lid}       # Update exercise log
│   ├── POST   /{id}/logs/{lid}/sets  # Log set
│   └── PUT    /{id}/logs/{lid}/sets/{sid} # Update set log
│
├── /purchases
│   ├── POST   /workouts/{id}         # Purchase workout
│   ├── POST   /plans/{id}            # Purchase training plan
│   └── GET    /history               # Purchase history
│
├── /subscriptions
│   ├── POST   /gym/{tier_id}         # Subscribe to gym tier
│   ├── DELETE /{id}                  # Cancel subscription
│   └── PUT    /{id}                  # Update subscription
│
├── /contracts
│   ├── POST   /trainer/{trainer_id}  # Hire trainer
│   ├── GET    /{id}                  # Get contract details
│   ├── PUT    /{id}                  # Update contract
│   └── POST   /{id}/sessions         # Log contract session
│
└── /analytics
    ├── GET    /trainee/progress      # Progress over time
    ├── GET    /trainee/volume        # Volume analytics
    ├── GET    /trainee/records       # PR timeline
    ├── GET    /trainer/content       # Content performance
    └── GET    /gym/overview          # Gym analytics
```

### 8.2 Laravel Route Organization

```
routes/
├── api.php              # API route registration
├── api/
│   ├── auth.php         # Authentication routes
│   ├── gyms.php         # Gym management routes
│   ├── trainers.php     # Trainer routes
│   ├── trainees.php     # Trainee routes
│   ├── exercises.php    # Exercise routes
│   ├── workouts.php     # Workout routes
│   ├── plans.php        # Training plan routes
│   ├── sessions.php     # Workout session routes
│   ├── commerce.php     # Purchase/subscription routes
│   └── analytics.php    # Analytics routes
```

---

## 9. Caching Strategy

### 9.1 Redis Cache Implementation

```php
// Cache key patterns
$cacheKeys = [
    // User & Profile caches
    'user:{id}' => 'User model',
    'user:{id}:profiles' => 'User profiles/roles',
    'user:{id}:gyms' => 'Gyms owned by user',
    
    // Equipment caches (preset, rarely changes)
    'equipment:all' => 'All preset equipment',
    'equipment:category:{cat}' => 'Equipment by category',
    
    // Exercise caches
    'exercises:system' => 'All system exercises',
    'exercises:pool' => 'Public pool exercises',
    'exercises:trainer:{id}' => 'Trainer custom exercises',
    'exercise:{id}' => 'Single exercise with media and equipment',
    
    // Workout caches
    'workouts:public' => 'Public workout listing',
    'workouts:trainer:{id}' => 'Trainer workouts',
    'workout:{id}' => 'Workout with exercises',
    'workout:{id}:equipment' => 'Required equipment for workout',
    
    // Training plan caches
    'plans:public' => 'Public plans listing',
    'plan:{id}:structure' => 'Full plan structure',
    'plan:{id}:equipment' => 'Required equipment for plan',
    
    // Trainee-specific caches
    'trainee:{id}:dashboard' => 'Dashboard metrics',
    'trainee:{id}:accessible_workouts' => 'Workouts user can access',
    'trainee:{id}:active_plans' => 'Active training plans',
    'trainee:{id}:records' => 'Personal records',
    
    // Gym caches
    'gym:{id}' => 'Gym details',
    'gym:{id}:tiers' => 'Subscription tiers',
    'gym:{id}:trainers' => 'Gym trainers',
    'gym:{id}:equipment' => 'Gym equipment list',
    'gym:{id}:compatible_workouts' => 'Workouts compatible with gym equipment',
    
    // Listing caches with pagination
    'gyms:list:page:{n}' => 'Paginated gym list',
    'trainers:list:page:{n}' => 'Paginated trainer list',
];
```

### 9.2 Cache Invalidation Rules

| Event | Invalidate Keys |
|-------|-----------------|
| Exercise created/updated | `exercises:trainer:{id}`, `exercises:pool`, `exercise:{id}`, `workout:{wid}:equipment` (affected workouts) |
| Exercise equipment changed | `exercise:{id}`, `workout:{wid}:equipment`, `gym:{gid}:compatible_workouts` |
| Workout created/updated | `workouts:trainer:{id}`, `workouts:public`, `workout:{id}`, `workout:{id}:equipment` |
| Workout purchased | `trainee:{id}:accessible_workouts` |
| Session completed | `trainee:{id}:dashboard`, `trainee:{id}:records` |
| Subscription changed | `trainee:{id}:accessible_workouts`, `gym:{id}:members` |
| Trainer hired/removed | `gym:{id}:trainers`, `trainer:{id}` |
| Gym equipment changed | `gym:{id}:equipment`, `gym:{id}:compatible_workouts` |
| Gym created/updated | `user:{id}:gyms`, `gym:{id}`, `gyms:list:*` |

### 9.3 Cache TTL Guidelines

| Cache Type | TTL | Rationale |
|------------|-----|-----------|
| Preset equipment | 24 hours | System-managed, rarely changes |
| System exercises | 24 hours | Rarely changes |
| Public pool exercises | 1 hour | Moderate change rate |
| Workout listings | 15 minutes | Frequent updates |
| Workout equipment | 1 hour | Changes when workout edited |
| Dashboard metrics | 5 minutes | Near real-time feel |
| Personal records | 1 hour | Changes on PR events |
| Gym details | 1 hour | Moderate change rate |
| Gym equipment | 1 hour | Changes when inventory updated |
| Compatible workouts | 30 minutes | Computed, can be expensive |

---

## 10. Implementation Phases

### Phase 1: Foundation (Weeks 1-2)

**Objective:** Core infrastructure and authentication

- [ ] Project scaffolding (Laravel, PostgreSQL, Redis setup)
- [ ] Database migrations for all tables
- [ ] Equipment preset seeder (populate equipment catalog)
- [ ] User authentication (register, login, logout, password reset)
- [ ] Role/Profile system implementation (Trainer, Trainee)
- [ ] Base API response structure and error handling
- [ ] Request validation foundation
- [ ] Unit test setup

**Deliverables:**
- Working authentication flow
- User can create account and add profiles (trainer/trainee)
- Equipment catalog populated and accessible via API
- API documentation setup (OpenAPI/Swagger)

### Phase 2: Training Content (Weeks 3-4)

**Objective:** Exercise and workout management

- [ ] Exercise CRUD operations
- [ ] Exercise-Equipment relationship management
- [ ] System exercise seeder (with equipment assignments)
- [ ] Custom exercise creation and pool publishing
- [ ] Exercise media management
- [ ] Workout CRUD operations
- [ ] Workout exercise composition
- [ ] Workout publishing flow
- [ ] Exercise/workout search and filtering
- [ ] Workout required equipment aggregation

**Deliverables:**
- Trainers can create and manage exercises with equipment options
- Trainers can compose and publish workouts
- Trainees can browse available workouts
- API returns required equipment for workouts

### Phase 3: Training Plans (Week 5)

**Objective:** Multi-week training plan creation

- [ ] Training plan CRUD operations
- [ ] Week/day/workout structure management
- [ ] Plan publishing flow
- [ ] Trainee plan enrollment
- [ ] Active plan tracking

**Deliverables:**
- Trainers can create structured training plans
- Trainees can enroll in and track plan progress

### Phase 4: Workout Execution (Weeks 6-7)

**Objective:** Session tracking and performance logging

- [ ] Workout session initiation
- [ ] Exercise log creation
- [ ] Set log recording (reps, weight, duration, distance)
- [ ] Session completion/abandonment
- [ ] Previous performance retrieval
- [ ] Basic validation (can user access this workout?)

**Deliverables:**
- Trainees can start workouts and log their performance
- Historical session data is preserved

### Phase 5: Gym Management (Week 8)

**Objective:** Gym entities, equipment inventory, and trainer employment

- [ ] Gym CRUD (any user can create/own gyms)
- [ ] Gym equipment management (add/remove from preset catalog)
- [ ] Subscription tier management
- [ ] Trainer invitation/hiring flow
- [ ] Employment status management
- [ ] Gym public profile pages
- [ ] Workout-Gym compatibility checking
- [ ] Compatible workouts/gyms listing endpoints

**Deliverables:**
- Users can create and manage gyms
- Gym owners can configure equipment inventory
- Gyms can hire and manage trainers
- Trainees can check if workouts are compatible with their gym

### Phase 6: Commerce (Week 9)

**Objective:** Purchase and subscription tracking

- [ ] Workout purchase flow (stub payment)
- [ ] Training plan purchase flow
- [ ] Gym subscription management
- [ ] Trainer contract creation
- [ ] Access control based on purchases
- [ ] Purchase history

**Deliverables:**
- Trainees can "purchase" content (payment integration deferred)
- Access is properly gated based on ownership

### Phase 7: Analytics & Dashboard (Weeks 10-11)

**Objective:** Progress tracking and insights

- [ ] Progress snapshot generation (scheduled job)
- [ ] Personal record detection and tracking
- [ ] Trainee dashboard metrics API
- [ ] Completion rate calculations
- [ ] Volume tracking over time
- [ ] Streak calculation
- [ ] Trainer content analytics
- [ ] Gym member analytics

**Deliverables:**
- Trainees see comprehensive progress dashboards
- Trainers see content performance metrics
- Gyms see membership analytics

### Phase 8: Polish & Optimization (Week 12)

**Objective:** Performance, testing, and refinement

- [ ] Redis caching implementation
- [ ] Query optimization
- [ ] Rate limiting
- [ ] Comprehensive API tests
- [ ] Documentation completion
- [ ] Error handling refinement
- [ ] Logging and monitoring setup

**Deliverables:**
- Production-ready API
- Complete test coverage
- Full API documentation

---

## Appendix A: Muscle Groups Reference

```php
$muscleGroups = [
    'chest' => 'Chest',
    'back_upper' => 'Upper Back',
    'back_lower' => 'Lower Back',
    'back_lats' => 'Latissimus Dorsi',
    'shoulders_front' => 'Front Deltoids',
    'shoulders_side' => 'Side Deltoids',
    'shoulders_rear' => 'Rear Deltoids',
    'biceps' => 'Biceps',
    'triceps' => 'Triceps',
    'forearms' => 'Forearms',
    'abs' => 'Abdominals',
    'obliques' => 'Obliques',
    'quadriceps' => 'Quadriceps',
    'hamstrings' => 'Hamstrings',
    'glutes' => 'Glutes',
    'calves' => 'Calves',
    'hip_flexors' => 'Hip Flexors',
    'adductors' => 'Adductors',
    'abductors' => 'Abductors',
    'traps' => 'Trapezius',
    'neck' => 'Neck',
    'full_body' => 'Full Body',
];
```

## Appendix B: Equipment Preset Catalog

The `equipment` table is pre-seeded with standardized equipment entries. This prevents naming inconsistencies and enables reliable gym-workout compatibility matching.

```php
$equipmentSeed = [
    // Bodyweight (always available)
    ['name' => 'None (Bodyweight)', 'category' => 'bodyweight', 'is_common' => true],
    
    // Free Weights
    ['name' => 'Barbell', 'category' => 'free_weights', 'is_common' => true],
    ['name' => 'Dumbbells', 'category' => 'free_weights', 'is_common' => true],
    ['name' => 'Kettlebell', 'category' => 'free_weights', 'is_common' => true],
    ['name' => 'EZ Curl Bar', 'category' => 'free_weights', 'is_common' => true],
    ['name' => 'Trap Bar / Hex Bar', 'category' => 'free_weights', 'is_common' => false],
    ['name' => 'Weight Plates', 'category' => 'free_weights', 'is_common' => true],
    ['name' => 'Medicine Ball', 'category' => 'free_weights', 'is_common' => true],
    
    // Machines
    ['name' => 'Smith Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Leg Press Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Leg Extension Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Leg Curl Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Hack Squat Machine', 'category' => 'machines', 'is_common' => false],
    ['name' => 'Chest Press Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Shoulder Press Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Lat Pulldown Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Seated Row Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Pec Deck / Fly Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Calf Raise Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Assisted Pull-up Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Abdominal Crunch Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Hip Abductor Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Hip Adductor Machine', 'category' => 'machines', 'is_common' => true],
    ['name' => 'Glute Kickback Machine', 'category' => 'machines', 'is_common' => false],
    ['name' => 'Preacher Curl Bench', 'category' => 'machines', 'is_common' => true],
    
    // Cable
    ['name' => 'Cable Machine (Single)', 'category' => 'cable', 'is_common' => true],
    ['name' => 'Cable Crossover Machine', 'category' => 'cable', 'is_common' => true],
    ['name' => 'Cable Attachments (Various)', 'category' => 'cable', 'is_common' => true],
    
    // Cardio
    ['name' => 'Treadmill', 'category' => 'cardio', 'is_common' => true],
    ['name' => 'Stationary Bike', 'category' => 'cardio', 'is_common' => true],
    ['name' => 'Elliptical', 'category' => 'cardio', 'is_common' => true],
    ['name' => 'Rowing Machine', 'category' => 'cardio', 'is_common' => true],
    ['name' => 'Stair Climber', 'category' => 'cardio', 'is_common' => true],
    ['name' => 'Assault Bike / Air Bike', 'category' => 'cardio', 'is_common' => false],
    ['name' => 'Ski Erg', 'category' => 'cardio', 'is_common' => false],
    
    // Plyometric
    ['name' => 'Plyo Box / Platform', 'category' => 'plyometric', 'is_common' => true],
    ['name' => 'Agility Ladder', 'category' => 'plyometric', 'is_common' => false],
    ['name' => 'Hurdles', 'category' => 'plyometric', 'is_common' => false],
    
    // Accessories
    ['name' => 'Pull-up Bar', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Dip Bars / Station', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Flat Bench', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Adjustable Bench', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Incline Bench', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Decline Bench', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Squat Rack / Power Rack', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Resistance Bands', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Stability Ball', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Foam Roller', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'TRX / Suspension Trainer', 'category' => 'accessories', 'is_common' => false],
    ['name' => 'Battle Ropes', 'category' => 'accessories', 'is_common' => false],
    ['name' => 'Ab Wheel', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Landmine Attachment', 'category' => 'accessories', 'is_common' => false],
    ['name' => 'GHD (Glute Ham Developer)', 'category' => 'accessories', 'is_common' => false],
    ['name' => 'Roman Chair / Hyperextension', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Jump Rope', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Yoga Mat', 'category' => 'accessories', 'is_common' => true],
    ['name' => 'Ankle Weights', 'category' => 'accessories', 'is_common' => true],
];
```

### Equipment Categories

| Category | Description |
|----------|-------------|
| `bodyweight` | No equipment needed |
| `free_weights` | Barbells, dumbbells, kettlebells, etc. |
| `machines` | Resistance machines with fixed movement paths |
| `cable` | Cable-based resistance equipment |
| `cardio` | Cardiovascular training equipment |
| `plyometric` | Equipment for explosive/jump training |
| `accessories` | Benches, racks, bands, and other support equipment |

### Gym-Workout Compatibility Logic

```php
/**
 * Check if a workout can be performed at a gym based on equipment availability.
 * 
 * A workout is compatible if, for EVERY exercise in the workout,
 * the gym has AT LEAST ONE of the equipment options that exercise supports.
 */
function isWorkoutCompatibleWithGym(Workout $workout, Gym $gym): bool
{
    $gymEquipmentIds = $gym->equipment->pluck('equipment_id')->toArray();
    
    foreach ($workout->exercises as $workoutExercise) {
        $exerciseEquipmentIds = $workoutExercise->exercise
            ->equipment->pluck('equipment_id')->toArray();
        
        // Check if gym has at least one compatible equipment
        if (empty(array_intersect($gymEquipmentIds, $exerciseEquipmentIds))) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get missing equipment for a workout at a gym.
 * Returns exercises and their missing equipment options.
 */
function getMissingEquipment(Workout $workout, Gym $gym): array
{
    $gymEquipmentIds = $gym->equipment->pluck('equipment_id')->toArray();
    $missing = [];
    
    foreach ($workout->exercises as $workoutExercise) {
        $exercise = $workoutExercise->exercise;
        $exerciseEquipmentIds = $exercise->equipment->pluck('equipment_id')->toArray();
        
        if (empty(array_intersect($gymEquipmentIds, $exerciseEquipmentIds))) {
            $missing[] = [
                'exercise' => $exercise,
                'required_equipment' => $exercise->equipment,
            ];
        }
    }
    
    return $missing;
}
```

## Appendix C: System Exercise Seed Categories

Initial system exercises should cover:

1. **Push movements:** Bench press, overhead press, push-ups, dips
2. **Pull movements:** Rows, pull-ups, lat pulldown, face pulls
3. **Squat patterns:** Back squat, front squat, goblet squat, leg press
4. **Hinge patterns:** Deadlift, Romanian deadlift, hip thrust
5. **Carry movements:** Farmer's walk, suitcase carry
6. **Core:** Plank, dead bug, Russian twist, leg raises
7. **Cardio:** Running, cycling, rowing, jump rope
8. **Mobility:** Various stretches and mobility drills

Recommended initial seed: 100-150 system exercises covering fundamental movements.

---

## Document Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Dec 2024 | Initial specification |
| 1.1 | Dec 2024 | Gyms refactored to owned entities (not user roles). Added preset equipment catalog. Added exercise-equipment and gym-equipment relationships. Added workout-gym compatibility checking. |

---

*This document serves as the authoritative specification for FitTrack development. All implementation decisions should reference and align with this document. Updates require version increment and changelog entry.*
