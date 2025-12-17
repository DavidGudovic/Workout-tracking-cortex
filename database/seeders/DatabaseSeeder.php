<?php

namespace Database\Seeders;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\TrainerProfile;
use App\Domain\Identity\User;
use App\Domain\Training\Equipment;
use App\Domain\Training\Exercise;
use App\Domain\Training\Workout;
use App\Shared\Enums\Difficulty;
use App\Shared\Enums\PricingType;
use App\Shared\Enums\WorkoutStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed equipment catalog (required)
        $this->call(EquipmentSeeder::class);

        // 2. Seed system exercises (required)
        $this->call(SystemExerciseSeeder::class);

        // 3. Create test user (trainee)
        $user = User::create([
            'email' => 'trainee@example.com',
            'password' => Hash::make('password'),
        ]);

        // Create trainee profile for test user
        $trainee = TraineeProfile::create([
            'user_id' => $user->id,
            'display_name' => 'Test Trainee',
            'date_of_birth' => now()->subYears(25),
            'height_cm' => 175,
            'weight_kg' => 75,
        ]);

        // 4. Create trainer user with profile
        $trainerUser = User::create([
            'email' => 'trainer@example.com',
            'password' => Hash::make('password'),
        ]);

        $trainer = TrainerProfile::create([
            'user_id' => $trainerUser->id,
            'display_name' => 'Coach John',
            'bio' => 'Certified personal trainer with 10 years of experience',
            'specializations' => ['Strength Training', 'Bodybuilding'],
            'certifications' => ['NASM-CPT', 'CSCS'],
            'years_experience' => 10,
            'hourly_rate_cents' => 7500, // $75/hr
        ]);

        // 5. Get some equipment and exercises for workouts
        $barbell = Equipment::where('name', 'Barbell')->first();
        $dumbbells = Equipment::where('name', 'Dumbbells')->first();
        $bench = Equipment::where('name', 'Flat Bench')->first();

        $benchPress = Exercise::where('name', 'Barbell Bench Press')->first();
        $squat = Exercise::where('name', 'Barbell Back Squat')->first();
        $deadlift = Exercise::where('name', 'Barbell Deadlift')->first();
        $dumbbellRow = Exercise::where('name', 'Dumbbell Row')->first();

        // 6. Create Workout 1: Full Body Strength
        $workout1 = Workout::create([
            'creator_id' => $trainer->id,
            'name' => 'Full Body Strength',
            'description' => 'A comprehensive full-body workout focusing on compound movements',
            'difficulty' => Difficulty::INTERMEDIATE,
            'estimated_duration_minutes' => 60,
            'pricing_type' => PricingType::FREE,
            'status' => WorkoutStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        // Add exercises to workout 1
        if ($benchPress) {
            $workout1->workoutExercises()->create([
                'exercise_id' => $benchPress->id,
                'sort_order' => 1,
                'sets' => 4,
                'target_reps' => 8,
                'rest_seconds' => 120,
            ]);
        }

        if ($squat) {
            $workout1->workoutExercises()->create([
                'exercise_id' => $squat->id,
                'sort_order' => 2,
                'sets' => 4,
                'target_reps' => 8,
                'rest_seconds' => 180,
            ]);
        }

        if ($dumbbellRow) {
            $workout1->workoutExercises()->create([
                'exercise_id' => $dumbbellRow->id,
                'sort_order' => 3,
                'sets' => 3,
                'target_reps' => 10,
                'rest_seconds' => 90,
            ]);
        }

        $workout1->updateTotals();

        // 7. Create Workout 2: Push Day
        $workout2 = Workout::create([
            'creator_id' => $trainer->id,
            'name' => 'Push Day',
            'description' => 'Chest, shoulders, and triceps workout',
            'difficulty' => Difficulty::BEGINNER,
            'estimated_duration_minutes' => 45,
            'pricing_type' => PricingType::FREE,
            'status' => WorkoutStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        // Add exercises to workout 2
        if ($benchPress) {
            $workout2->workoutExercises()->create([
                'exercise_id' => $benchPress->id,
                'sort_order' => 1,
                'sets' => 3,
                'target_reps' => 10,
                'rest_seconds' => 90,
            ]);
        }

        if ($deadlift) {
            $workout2->workoutExercises()->create([
                'exercise_id' => $deadlift->id,
                'sort_order' => 2,
                'sets' => 3,
                'target_reps' => 6,
                'rest_seconds' => 180,
            ]);
        }

        $workout2->updateTotals();

        $this->command->info('✅ Created test user: trainee@example.com / password');
        $this->command->info('✅ Created trainer: trainer@example.com / password');
        $this->command->info('✅ Created 2 workouts');
    }
}
