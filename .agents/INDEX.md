# FitTrack Backend - Agent Reference Index

Quick navigation to all optimized reference files for AI agent consumption.

## 📖 Getting Started

**New to the project?** Start here:
1. Read `/reference/quick-reference.md` - Overview and common commands
2. Read `/implementation/current-status.md` - What's done, what's next
3. Read `/domain/ubiquitous-language.md` - Domain terminology

## 📂 Directory Structure

### `/domain/` - Domain Models & Contexts (4 files)
Core business domain definitions and bounded contexts.

- **`ubiquitous-language.md`** - Domain terminology and definitions
- **`identity-context.md`** - Users, authentication, profiles
- **`gym-context.md`** - Gym entities, equipment inventory, subscriptions
- **`training-content-context.md`** - Equipment, exercises, workouts, training plans

### `/database/` - Database Design (1 file)
Database schema and migration details.

- **`migrations-overview.md`** - All 30 migrations, features, running guide

### `/api/` - API Specifications (1 file)
RESTful API endpoint definitions.

- **`api-overview.md`** - API structure, endpoints, response formats

### `/business-rules/` - Business Logic (1 file)
Critical business rules that must be enforced.

- **`core-rules.md`** - 26 core business rules with enforcement patterns

### `/implementation/` - Implementation Guides (2 files)
Current progress and critical algorithms.

- **`current-status.md`** - Progress tracking, next steps, checklists
- **`compatibility-algorithm.md`** - Workout-gym compatibility (THE core feature)

### `/reference/` - Quick References (4 files)
Fast lookup for common information.

- **`quick-reference.md`** - Commands, queries, enums, relationships
- **`equipment-catalog.md`** - Full equipment preset catalog (~60 items)
- **`muscle-groups.md`** - Muscle group reference for exercises
- **`architecture-patterns.md`** - DDD, TDD, Vertical Slice patterns

## 🎯 Common Tasks

### Setting Up Development
1. Read: `/implementation/current-status.md`
2. Read: `/database/migrations-overview.md`
3. Run migrations: `php artisan migrate`

### Creating Equipment Seeder
1. Read: `/reference/equipment-catalog.md`
2. Read: `/implementation/current-status.md` (Step 2)
3. Create seeder with ~60 items from catalog

### Understanding Domain Model
1. Read: `/domain/ubiquitous-language.md`
2. Read specific context: `/domain/identity-context.md`, `/domain/gym-context.md`, etc.
3. Read: `/reference/architecture-patterns.md`

### Implementing Compatibility Feature
1. Read: `/implementation/compatibility-algorithm.md`
2. Read: `/domain/gym-context.md` (Compatibility section)
3. Read: `/business-rules/core-rules.md` (Rule 16)

### Building API Endpoints
1. Read: `/api/api-overview.md`
2. Read: `/reference/architecture-patterns.md` (Vertical Slice)
3. Read: `/business-rules/core-rules.md` (relevant rules)

### Understanding Business Rules
1. Read: `/business-rules/core-rules.md`
2. Read specific domain context for details
3. Check: `/reference/quick-reference.md` (Critical Business Rules section)

## 🔍 Quick Lookups

### "What equipment items should I seed?"
→ `/reference/equipment-catalog.md`

### "What muscle groups are valid?"
→ `/reference/muscle-groups.md`

### "What's the current status?"
→ `/implementation/current-status.md`

### "How does compatibility work?"
→ `/implementation/compatibility-algorithm.md`

### "What are the core business rules?"
→ `/business-rules/core-rules.md`

### "What's the API structure?"
→ `/api/api-overview.md`

### "What commands do I need?"
→ `/reference/quick-reference.md`

### "What's the domain terminology?"
→ `/domain/ubiquitous-language.md`

## 📊 File Sizes

All files are optimized for fast agent consumption:
- Each file: 2-8 KB
- Total: ~40 KB (vs 100+ KB for original docs)
- Focused single-topic files

## 🎓 Learning Path

**Day 1: Understanding**
1. `/reference/quick-reference.md`
2. `/domain/ubiquitous-language.md`
3. `/implementation/current-status.md`

**Day 2: Setup**
1. `/database/migrations-overview.md`
2. `/reference/equipment-catalog.md`
3. Create and run seeders

**Day 3: Architecture**
1. `/reference/architecture-patterns.md`
2. `/business-rules/core-rules.md`
3. `/domain/` - Read all context files

**Day 4: Implementation**
1. `/implementation/compatibility-algorithm.md`
2. `/api/api-overview.md`
3. Start building features

## 🚨 Critical Files

**MUST READ** before starting implementation:
- ✅ `/implementation/current-status.md` - Know where we are
- ✅ `/business-rules/core-rules.md` - Rules must be enforced
- ✅ `/implementation/compatibility-algorithm.md` - THE core feature
- ✅ `/reference/equipment-catalog.md` - Foundation data

## 📝 Notes

- All files focus on **backend API only** (no frontend)
- Equipment is **preset** - users cannot create custom equipment
- Workout-gym compatibility is **the core unique feature**
- Multi-role users supported (Trainer + Trainee + Gym Owner)
- TDD required for all features
- UUIDs used throughout

## 🔗 Original Documents

Large original docs still available:
- `FitTrack_Project_Document.md` - Complete specification
- `START_HERE.md` - Quick start guide
- `IMPLEMENTATION_PROGRESS.md` - Detailed progress
- `CLAUDE.md` - Architecture guidelines

## 📦 Total Files Created

**14 optimized reference files** across 6 directories:
- 4 domain context files
- 1 database file
- 1 API file
- 1 business rules file
- 2 implementation files
- 4 reference files
- 1 README + this INDEX

---

**Happy coding! 🚀**
