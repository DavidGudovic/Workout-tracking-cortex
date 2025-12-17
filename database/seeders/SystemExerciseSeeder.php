<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemExerciseSeeder extends Seeder
{
    /**
     * Seed system exercises with equipment relationships.
     *
     * Creates ~100 fundamental exercises covering all movement patterns:
     * - Push, Pull, Squat, Hinge movements
     * - Core work
     * - Cardio exercises
     *
     * All exercises are marked as 'system' type and cannot be modified by users.
     */
    public function run(): void
    {
        // First, get equipment IDs for linking
        $equipment = DB::table('equipment')->pluck('id', 'name')->toArray();

        // Define exercises with their details
        $exercises = $this->getExercises();

        foreach ($exercises as $exercise) {
            // Insert exercise
            $exerciseId = DB::table('exercises')->insertGetId([
                'id' => DB::raw('gen_random_uuid()'),
                'creator_id' => null, // System exercises have no creator
                'type' => 'system',
                'visibility' => 'public_pool',
                'name' => $exercise['name'],
                'description' => $exercise['description'],
                'instructions' => $exercise['instructions'] ?? null,
                'exercise_type' => $exercise['exercise_type'],
                'difficulty' => $exercise['difficulty'],
                'primary_muscle_groups' => json_encode($exercise['primary_muscle_groups']),
                'secondary_muscle_groups' => json_encode($exercise['secondary_muscle_groups'] ?? []),
                'is_compound' => $exercise['is_compound'],
                'calories_per_minute' => $exercise['calories_per_minute'] ?? null,
                'created_at' => now(),
                'published_at' => now(),
            ], 'id');

            // Link equipment
            foreach ($exercise['equipment'] as $equipmentName => $isPrimary) {
                if (isset($equipment[$equipmentName])) {
                    DB::table('exercise_equipment')->insert([
                        'exercise_id' => $exerciseId,
                        'equipment_id' => $equipment[$equipmentName],
                        'is_primary' => $isPrimary,
                        'notes' => null,
                    ]);
                }
            }
        }

        $this->command->info('System Exercise seeder completed: ' . count($exercises) . ' exercises seeded');
    }

    /**
     * Get array of exercise definitions
     */
    private function getExercises(): array
    {
        return [
            // ============================================
            // CHEST EXERCISES
            // ============================================
            [
                'name' => 'Barbell Bench Press',
                'description' => 'Classic compound chest exercise performed on a flat bench',
                'instructions' => 'Lie flat, grip bar slightly wider than shoulder width, lower to chest, press up',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['pectorals', 'triceps', 'front_delts'],
                'secondary_muscle_groups' => ['serratus'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true, 'Flat Bench' => false],
            ],
            [
                'name' => 'Dumbbell Bench Press',
                'description' => 'Chest press with dumbbells allowing greater range of motion',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['pectorals', 'triceps', 'front_delts'],
                'is_compound' => true,
                'equipment' => ['Dumbbells' => true, 'Flat Bench' => false],
            ],
            [
                'name' => 'Incline Barbell Bench Press',
                'description' => 'Bench press on incline to target upper chest',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['upper_chest', 'front_delts', 'triceps'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true, 'Incline Bench' => false, 'Adjustable Bench' => false],
            ],
            [
                'name' => 'Push-ups',
                'description' => 'Classic bodyweight chest exercise',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['pectorals', 'triceps', 'front_delts'],
                'is_compound' => true,
                'equipment' => ['None (Bodyweight)' => true],
            ],
            [
                'name' => 'Dumbbell Flyes',
                'description' => 'Isolation exercise for chest stretch and contraction',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['pectorals'],
                'secondary_muscle_groups' => ['front_delts'],
                'is_compound' => false,
                'equipment' => ['Dumbbells' => true, 'Flat Bench' => false],
            ],
            [
                'name' => 'Cable Chest Flyes',
                'description' => 'Cable variation providing constant tension',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['pectorals'],
                'is_compound' => false,
                'equipment' => ['Cable Crossover' => true, 'Cable Machine' => false],
            ],
            [
                'name' => 'Chest Press Machine',
                'description' => 'Machine chest press for controlled movement',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['pectorals', 'triceps'],
                'is_compound' => true,
                'equipment' => ['Chest Press Machine' => true],
            ],

            // ============================================
            // BACK EXERCISES
            // ============================================
            [
                'name' => 'Pull-ups',
                'description' => 'Bodyweight back exercise using overhead bar',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['lats', 'biceps'],
                'secondary_muscle_groups' => ['traps', 'rhomboids', 'forearms'],
                'is_compound' => true,
                'equipment' => ['Pull-up Bar' => true],
            ],
            [
                'name' => 'Lat Pulldown',
                'description' => 'Cable-based lat development exercise',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['lats', 'biceps'],
                'secondary_muscle_groups' => ['traps', 'rhomboids'],
                'is_compound' => true,
                'equipment' => ['Lat Pulldown Machine' => true],
            ],
            [
                'name' => 'Barbell Row',
                'description' => 'Bent-over row with barbell for back thickness',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['lats', 'rhomboids', 'traps'],
                'secondary_muscle_groups' => ['biceps', 'erector_spinae', 'forearms'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true],
            ],
            [
                'name' => 'Dumbbell Row',
                'description' => 'Single-arm dumbbell row for unilateral back development',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['lats', 'rhomboids'],
                'secondary_muscle_groups' => ['biceps', 'traps'],
                'is_compound' => true,
                'equipment' => ['Dumbbells' => true, 'Flat Bench' => false],
            ],
            [
                'name' => 'Seated Cable Row',
                'description' => 'Seated horizontal row using cable machine',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['lats', 'rhomboids', 'traps'],
                'secondary_muscle_groups' => ['biceps', 'erector_spinae'],
                'is_compound' => true,
                'equipment' => ['Seated Row Machine' => true, 'Cable Machine' => false],
            ],
            [
                'name' => 'Conventional Deadlift',
                'description' => 'King of compound exercises - full posterior chain',
                'exercise_type' => 'repetition',
                'difficulty' => 'advanced',
                'primary_muscle_groups' => ['hamstrings', 'glutes', 'erector_spinae'],
                'secondary_muscle_groups' => ['traps', 'lats', 'forearms', 'quadriceps'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true],
            ],
            [
                'name' => 'T-Bar Row',
                'description' => 'Landmine row variation for back thickness',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['lats', 'rhomboids', 'traps'],
                'secondary_muscle_groups' => ['biceps', 'erector_spinae'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true],
            ],

            // ============================================
            // SHOULDER EXERCISES
            // ============================================
            [
                'name' => 'Overhead Barbell Press',
                'description' => 'Standing or seated overhead press with barbell',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['deltoids', 'triceps'],
                'secondary_muscle_groups' => ['traps', 'serratus'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true],
            ],
            [
                'name' => 'Dumbbell Shoulder Press',
                'description' => 'Seated or standing overhead press with dumbbells',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['deltoids', 'triceps'],
                'secondary_muscle_groups' => ['traps'],
                'is_compound' => true,
                'equipment' => ['Dumbbells' => true],
            ],
            [
                'name' => 'Lateral Raises',
                'description' => 'Isolation exercise for side deltoids',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['side_delts'],
                'is_compound' => false,
                'equipment' => ['Dumbbells' => true],
            ],
            [
                'name' => 'Front Raises',
                'description' => 'Isolation for anterior deltoids',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['front_delts'],
                'is_compound' => false,
                'equipment' => ['Dumbbells' => true, 'Barbell' => false],
            ],
            [
                'name' => 'Rear Delt Flyes',
                'description' => 'Isolation for posterior deltoids',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['rear_delts'],
                'secondary_muscle_groups' => ['rhomboids'],
                'is_compound' => false,
                'equipment' => ['Dumbbells' => true],
            ],
            [
                'name' => 'Face Pulls',
                'description' => 'Cable exercise for rear delts and upper back',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['rear_delts', 'traps'],
                'secondary_muscle_groups' => ['rhomboids'],
                'is_compound' => false,
                'equipment' => ['Cable Machine' => true],
            ],

            // ============================================
            // ARM EXERCISES
            // ============================================
            [
                'name' => 'Barbell Bicep Curl',
                'description' => 'Classic bicep curl with straight bar',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['biceps'],
                'secondary_muscle_groups' => ['forearms'],
                'is_compound' => false,
                'equipment' => ['Barbell' => true, 'EZ Curl Bar' => false],
            ],
            [
                'name' => 'Dumbbell Bicep Curl',
                'description' => 'Bicep curl with dumbbells allowing supination',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['biceps'],
                'secondary_muscle_groups' => ['forearms'],
                'is_compound' => false,
                'equipment' => ['Dumbbells' => true],
            ],
            [
                'name' => 'Hammer Curls',
                'description' => 'Neutral grip curl targeting brachialis',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['biceps', 'forearms'],
                'is_compound' => false,
                'equipment' => ['Dumbbells' => true],
            ],
            [
                'name' => 'Tricep Dips',
                'description' => 'Bodyweight tricep exercise on parallel bars',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['triceps'],
                'secondary_muscle_groups' => ['pectorals', 'front_delts'],
                'is_compound' => true,
                'equipment' => ['Dip Station' => true],
            ],
            [
                'name' => 'Overhead Tricep Extension',
                'description' => 'Tricep isolation with dumbbell overhead',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['triceps'],
                'is_compound' => false,
                'equipment' => ['Dumbbells' => true, 'EZ Curl Bar' => false],
            ],
            [
                'name' => 'Tricep Pushdowns',
                'description' => 'Cable tricep extension',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['triceps'],
                'is_compound' => false,
                'equipment' => ['Cable Machine' => true],
            ],
            [
                'name' => 'Close-Grip Bench Press',
                'description' => 'Bench press variation emphasizing triceps',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['triceps', 'pectorals'],
                'secondary_muscle_groups' => ['front_delts'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true, 'Flat Bench' => false],
            ],

            // ============================================
            // LEG EXERCISES
            // ============================================
            [
                'name' => 'Barbell Back Squat',
                'description' => 'King of leg exercises - full leg development',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['quadriceps', 'glutes', 'hamstrings'],
                'secondary_muscle_groups' => ['erector_spinae', 'abs'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true, 'Squat Rack' => false],
            ],
            [
                'name' => 'Front Squat',
                'description' => 'Squat with bar in front rack position',
                'exercise_type' => 'repetition',
                'difficulty' => 'advanced',
                'primary_muscle_groups' => ['quadriceps', 'glutes'],
                'secondary_muscle_groups' => ['hamstrings', 'abs', 'erector_spinae'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true, 'Squat Rack' => false],
            ],
            [
                'name' => 'Leg Press',
                'description' => 'Machine-based quad and glute development',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['quadriceps', 'glutes', 'hamstrings'],
                'is_compound' => true,
                'equipment' => ['Leg Press Machine' => true],
            ],
            [
                'name' => 'Romanian Deadlift',
                'description' => 'Hip hinge movement for hamstrings and glutes',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['hamstrings', 'glutes', 'erector_spinae'],
                'secondary_muscle_groups' => ['traps', 'forearms'],
                'is_compound' => true,
                'equipment' => ['Barbell' => true, 'Dumbbells' => false],
            ],
            [
                'name' => 'Leg Curl',
                'description' => 'Isolation exercise for hamstrings',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['hamstrings'],
                'is_compound' => false,
                'equipment' => ['Leg Curl Machine' => true],
            ],
            [
                'name' => 'Leg Extension',
                'description' => 'Isolation exercise for quadriceps',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['quadriceps'],
                'is_compound' => false,
                'equipment' => ['Leg Extension Machine' => true],
            ],
            [
                'name' => 'Walking Lunges',
                'description' => 'Dynamic lunge movement for legs and glutes',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['quadriceps', 'glutes'],
                'secondary_muscle_groups' => ['hamstrings', 'calves'],
                'is_compound' => true,
                'equipment' => ['None (Bodyweight)' => true, 'Dumbbells' => false],
            ],
            [
                'name' => 'Bulgarian Split Squat',
                'description' => 'Single-leg squat variation with rear foot elevated',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['quadriceps', 'glutes'],
                'secondary_muscle_groups' => ['hamstrings'],
                'is_compound' => true,
                'equipment' => ['None (Bodyweight)' => true, 'Dumbbells' => false, 'Flat Bench' => false],
            ],
            [
                'name' => 'Calf Raises',
                'description' => 'Standing calf raises for gastrocnemius',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['calves'],
                'is_compound' => false,
                'equipment' => ['Calf Raise Machine' => true, 'None (Bodyweight)' => false],
            ],
            [
                'name' => 'Hip Thrusts',
                'description' => 'Glute-focused hip extension movement',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['glutes', 'hamstrings'],
                'is_compound' => false,
                'equipment' => ['Barbell' => true, 'Flat Bench' => false],
            ],

            // ============================================
            // CORE EXERCISES
            // ============================================
            [
                'name' => 'Crunches',
                'description' => 'Basic abdominal exercise',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['abs'],
                'is_compound' => false,
                'equipment' => ['None (Bodyweight)' => true],
            ],
            [
                'name' => 'Plank',
                'description' => 'Isometric core stability exercise',
                'exercise_type' => 'duration',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['abs', 'obliques'],
                'secondary_muscle_groups' => ['erector_spinae'],
                'is_compound' => false,
                'equipment' => ['None (Bodyweight)' => true],
            ],
            [
                'name' => 'Russian Twists',
                'description' => 'Rotational core exercise for obliques',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['obliques', 'abs'],
                'is_compound' => false,
                'equipment' => ['None (Bodyweight)' => true, 'Medicine Ball' => false],
            ],
            [
                'name' => 'Hanging Leg Raises',
                'description' => 'Advanced ab exercise hanging from bar',
                'exercise_type' => 'repetition',
                'difficulty' => 'advanced',
                'primary_muscle_groups' => ['abs', 'hip_flexors'],
                'is_compound' => false,
                'equipment' => ['Pull-up Bar' => true],
            ],
            [
                'name' => 'Cable Woodchops',
                'description' => 'Rotational core exercise with cable',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['obliques'],
                'secondary_muscle_groups' => ['abs', 'deltoids'],
                'is_compound' => false,
                'equipment' => ['Cable Machine' => true],
            ],
            [
                'name' => 'Ab Wheel Rollouts',
                'description' => 'Advanced core exercise with ab wheel',
                'exercise_type' => 'repetition',
                'difficulty' => 'advanced',
                'primary_muscle_groups' => ['abs'],
                'secondary_muscle_groups' => ['serratus', 'lats'],
                'is_compound' => false,
                'equipment' => ['Ab Wheel' => true],
            ],

            // ============================================
            // CARDIO EXERCISES
            // ============================================
            [
                'name' => 'Treadmill Running',
                'description' => 'Cardiovascular exercise on treadmill',
                'exercise_type' => 'duration',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['quadriceps', 'hamstrings', 'calves'],
                'is_compound' => true,
                'calories_per_minute' => 10.0,
                'equipment' => ['Treadmill' => true],
            ],
            [
                'name' => 'Stationary Bike',
                'description' => 'Low-impact cardio on bike',
                'exercise_type' => 'duration',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['quadriceps', 'hamstrings'],
                'is_compound' => false,
                'calories_per_minute' => 8.0,
                'equipment' => ['Stationary Bike' => true, 'Spin Bike' => false],
            ],
            [
                'name' => 'Rowing Machine',
                'description' => 'Full-body cardio exercise',
                'exercise_type' => 'duration',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['lats', 'quadriceps', 'hamstrings'],
                'secondary_muscle_groups' => ['biceps', 'glutes', 'traps'],
                'is_compound' => true,
                'calories_per_minute' => 11.0,
                'equipment' => ['Rowing Machine' => true],
            ],
            [
                'name' => 'Elliptical',
                'description' => 'Low-impact full-body cardio',
                'exercise_type' => 'duration',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['quadriceps', 'hamstrings'],
                'is_compound' => true,
                'calories_per_minute' => 9.0,
                'equipment' => ['Elliptical' => true],
            ],
            [
                'name' => 'StairMaster',
                'description' => 'Stair climbing cardio machine',
                'exercise_type' => 'duration',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['quadriceps', 'glutes', 'calves'],
                'is_compound' => true,
                'calories_per_minute' => 12.0,
                'equipment' => ['StairMaster' => true],
            ],
            [
                'name' => 'Jump Rope',
                'description' => 'High-intensity cardio exercise',
                'exercise_type' => 'duration',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['calves', 'quadriceps'],
                'secondary_muscle_groups' => ['forearms', 'deltoids'],
                'is_compound' => true,
                'calories_per_minute' => 13.0,
                'equipment' => ['None (Bodyweight)' => true],
            ],
            [
                'name' => 'Burpees',
                'description' => 'Full-body explosive exercise',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['full_body'],
                'is_compound' => true,
                'calories_per_minute' => 12.0,
                'equipment' => ['None (Bodyweight)' => true],
            ],

            // ============================================
            // PLYOMETRIC & FUNCTIONAL
            // ============================================
            [
                'name' => 'Box Jumps',
                'description' => 'Explosive jumping exercise onto box',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['quadriceps', 'glutes', 'calves'],
                'is_compound' => true,
                'equipment' => ['Plyo Box' => true],
            ],
            [
                'name' => 'Battle Ropes',
                'description' => 'High-intensity rope exercise',
                'exercise_type' => 'duration',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['deltoids', 'forearms'],
                'secondary_muscle_groups' => ['abs', 'lats'],
                'is_compound' => true,
                'calories_per_minute' => 10.0,
                'equipment' => ['Battle Ropes' => true],
            ],
            [
                'name' => 'Sled Push',
                'description' => 'Pushing weighted sled for power and conditioning',
                'exercise_type' => 'distance',
                'difficulty' => 'advanced',
                'primary_muscle_groups' => ['quadriceps', 'glutes', 'calves'],
                'secondary_muscle_groups' => ['hamstrings', 'abs'],
                'is_compound' => true,
                'equipment' => ['Speed Sled' => true],
            ],
            [
                'name' => 'Kettlebell Swings',
                'description' => 'Explosive hip hinge movement with kettlebell',
                'exercise_type' => 'repetition',
                'difficulty' => 'intermediate',
                'primary_muscle_groups' => ['glutes', 'hamstrings'],
                'secondary_muscle_groups' => ['erector_spinae', 'deltoids'],
                'is_compound' => true,
                'equipment' => ['Kettlebell' => true],
            ],
            [
                'name' => 'TRX Rows',
                'description' => 'Suspension trainer rowing exercise',
                'exercise_type' => 'repetition',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['lats', 'rhomboids'],
                'secondary_muscle_groups' => ['biceps', 'abs'],
                'is_compound' => true,
                'equipment' => ['TRX Suspension Trainer' => true],
            ],
            [
                'name' => 'Farmers Walk',
                'description' => 'Loaded carry for grip and core strength',
                'exercise_type' => 'distance',
                'difficulty' => 'beginner',
                'primary_muscle_groups' => ['forearms', 'traps'],
                'secondary_muscle_groups' => ['abs', 'glutes'],
                'is_compound' => true,
                'equipment' => ['Dumbbells' => true, 'Kettlebell' => false],
            ],
        ];
    }
}
