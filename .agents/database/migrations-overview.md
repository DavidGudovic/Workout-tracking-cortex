# Database Migrations Overview

All 30 migrations created and ready to run.

## Migration Batches (Dependency Order)

### Batch 1: Foundation Tables (2)
1. `2024_12_12_000001_create_users_table.php`
2. `2024_12_12_000002_create_equipment_table.php` ⭐ CRITICAL

### Batch 2: Profiles & Gyms (3)
3. `2024_12_12_000003_create_trainer_profiles_table.php`
4. `2024_12_12_000004_create_trainee_profiles_table.php`
5. `2024_12_12_000005_create_gyms_table.php`

### Batch 3: Gym Context (3)
6. `2024_12_12_000006_create_subscription_tiers_table.php`
7. `2024_12_12_000007_create_gym_trainers_table.php`
8. `2024_12_12_000008_create_gym_equipment_table.php` ⭐ CRITICAL

### Batch 4: Training Content (9)
9. `2024_12_12_000009_create_exercises_table.php`
10. `2024_12_12_000010_create_exercise_equipment_table.php` ⭐ CRITICAL
11. `2024_12_12_000011_create_exercise_media_table.php`
12. `2024_12_12_000012_create_workouts_table.php`
13. `2024_12_12_000013_create_workout_exercises_table.php`
14. `2024_12_12_000014_create_training_plans_table.php`
15. `2024_12_12_000015_create_training_plan_weeks_table.php`
16. `2024_12_12_000016_create_training_plan_days_table.php`
17. `2024_12_12_000017_create_training_plan_workouts_table.php`

### Batch 5: Execution Context (3)
18. `2024_12_12_000018_create_workout_sessions_table.php`
19. `2024_12_12_000019_create_exercise_logs_table.php`
20. `2024_12_12_000020_create_set_logs_table.php`

### Batch 6: Analytics Context (2)
21. `2024_12_12_000021_create_progress_snapshots_table.php`
22. `2024_12_12_000022_create_personal_records_table.php`

### Batch 7: Commerce Context (4)
23. `2024_12_12_000023_create_workout_purchases_table.php`
24. `2024_12_12_000024_create_training_plan_purchases_table.php`
25. `2024_12_12_000025_create_gym_subscriptions_table.php`
26. `2024_12_12_000026_create_trainer_contracts_table.php`

### Batch 8: Junction & Laravel Tables (4)
27. `2024_12_12_000027_create_trainee_active_plans_table.php`
28. `2024_12_12_000028_create_personal_access_tokens_table.php` (Sanctum)
29. `2024_12_12_000029_create_cache_table.php`
30. `2024_12_12_000030_create_jobs_table.php`

## Key Features Implemented

### UUID Primary Keys
All tables use UUIDs with PostgreSQL default:
```php
$table->uuid('id')->primary();
DB::statement('ALTER TABLE table_name ALTER COLUMN id SET DEFAULT gen_random_uuid()');
```

### Foreign Key Constraints
Proper cascading and restrict behaviors:
- `ON DELETE CASCADE`: Delete dependent records (profiles, equipment)
- `ON DELETE RESTRICT`: Prevent deletion if referenced (workouts, exercises)
- `ON DELETE SET NULL`: Null out reference (optional relationships)

### CHECK Constraints
Database-level enum validation:
```sql
CHECK (status IN ('pending', 'active', 'suspended'))
CHECK (pricing_type IN ('free', 'premium'))
CHECK (days_per_week BETWEEN 1 AND 7)
```

### Unique Constraints
Business rule enforcement:
- `(user_id)` on trainer_profiles, trainee_profiles (one profile per user)
- `(gym_id, equipment_id)` on gym_equipment (no duplicates)
- `(exercise_id, equipment_id)` on exercise_equipment
- `(trainee_id, workout_id)` on workout_purchases (one purchase per workout)

### Indexes
Optimized for common queries:
- Foreign keys
- Status fields
- Date fields
- Creator IDs
- Lookup fields (slug, email)

### GIN Indexes (PostgreSQL)
For array searches:
```php
$table->index('primary_muscle_groups', 'idx_exercises_muscle_groups', 'gin');
$table->index('tags', 'idx_workouts_tags', 'gin');
```

Enables fast queries:
```php
Exercise::whereJsonContains('primary_muscle_groups', 'pectorals')->get();
```

## Running Migrations

### Setup Database
```bash
# PostgreSQL required
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fittrack
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Run Migrations
```bash
php artisan migrate
```

Expected output: 30 migrations run successfully.

### Verify
```bash
# Check tables created
php artisan migrate:status

# PostgreSQL direct check
psql -d fittrack -c "\dt"
```

## PostgreSQL Requirements

### Extensions
Migrations use pgcrypto for UUIDs:
```sql
CREATE EXTENSION IF NOT EXISTS "pgcrypto";
```

Laravel handles this automatically in first migration.

### Features Used
- `gen_random_uuid()` - UUID generation
- `GIN` indexes - Array/JSONB indexing
- `JSONB` columns - Structured JSON data
- `TEXT[]` arrays - String arrays for tags, muscle groups

## Critical Tables for Seeding

### Equipment (table #2)
**MUST be seeded first** - foundation of entire system.

All exercises and gyms depend on this table.

### Users (table #1)
Needed for creating trainer/trainee profiles and gyms.

### Profiles (tables #3, #4)
Needed for creating content (exercises, workouts, plans).

## Rollback Strategy

### Rollback Last Batch
```bash
php artisan migrate:rollback
```

### Rollback All
```bash
php artisan migrate:reset
```

### Fresh Migration
```bash
php artisan migrate:fresh
# WARNING: Drops all tables and re-runs migrations
```

### Fresh with Seeders
```bash
php artisan migrate:fresh --seed
```

## Next Steps

After migrations run successfully:
1. Create Equipment Seeder
2. Run Equipment Seeder
3. Create System Exercise Seeder
4. Run System Exercise Seeder
5. Verify data with SQL queries

See `/implementation/current-status.md` for full next steps.
