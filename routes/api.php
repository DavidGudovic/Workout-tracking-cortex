<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExerciseLogController;
use App\Http\Controllers\Api\GymController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SetLogController;
use App\Http\Controllers\Api\TrainerTrainingPlanController;
use App\Http\Controllers\Api\TrainerWorkoutController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\WorkoutSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/health-check', function () {
    return response()->json(['status' => 'ok']);
});

// API v1 routes
Route::prefix('v1')->group(function () {

    // Public auth routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Profile management routes
        Route::post('/profiles/trainer', [ProfileController::class, 'createTrainerProfile']);
        Route::get('/profiles/trainer', [ProfileController::class, 'getTrainerProfile']);
        Route::patch('/profiles/trainer', [ProfileController::class, 'updateTrainerProfile']);
        Route::delete('/profiles/trainer', [ProfileController::class, 'deleteTrainerProfile']);

        Route::post('/profiles/trainee', [ProfileController::class, 'createTraineeProfile']);
        Route::get('/profiles/trainee', [ProfileController::class, 'getTraineeProfile']);
        Route::patch('/profiles/trainee', [ProfileController::class, 'updateTraineeProfile']);
        Route::delete('/profiles/trainee', [ProfileController::class, 'deleteTraineeProfile']);

        // Trainer workout management routes (requires trainer profile)
        Route::middleware('trainer')->prefix('trainer/workouts')->group(function () {
            Route::get('/', [TrainerWorkoutController::class, 'index']);
            Route::post('/', [TrainerWorkoutController::class, 'store']);
            Route::get('/{id}', [TrainerWorkoutController::class, 'show']);
            Route::patch('/{id}', [TrainerWorkoutController::class, 'update']);
            Route::delete('/{id}', [TrainerWorkoutController::class, 'destroy']);

            // Workout status transitions
            Route::post('/{id}/publish', [TrainerWorkoutController::class, 'publish']);
            Route::post('/{id}/archive', [TrainerWorkoutController::class, 'archive']);
            Route::post('/{id}/draft', [TrainerWorkoutController::class, 'draft']);

            // Workout exercise management
            Route::post('/{workoutId}/exercises', [TrainerWorkoutController::class, 'addExercise']);
            Route::patch('/{workoutId}/exercises/{exerciseId}', [TrainerWorkoutController::class, 'updateExercise']);
            Route::delete('/{workoutId}/exercises/{exerciseId}', [TrainerWorkoutController::class, 'removeExercise']);
        });

        // Trainer training plan management routes (requires trainer profile)
        Route::middleware('trainer')->prefix('trainer/training-plans')->group(function () {
            Route::get('/', [TrainerTrainingPlanController::class, 'index']);
            Route::post('/', [TrainerTrainingPlanController::class, 'store']);
            Route::get('/{id}', [TrainerTrainingPlanController::class, 'show']);
            Route::patch('/{id}', [TrainerTrainingPlanController::class, 'update']);
            Route::delete('/{id}', [TrainerTrainingPlanController::class, 'destroy']);

            // Training plan status transitions
            Route::post('/{id}/publish', [TrainerTrainingPlanController::class, 'publish']);
            Route::post('/{id}/archive', [TrainerTrainingPlanController::class, 'archive']);
            Route::post('/{id}/draft', [TrainerTrainingPlanController::class, 'draft']);

            // Training plan structure generation
            Route::post('/{id}/generate-structure', [TrainerTrainingPlanController::class, 'generateStructure']);

            // Week and day management
            Route::patch('/{planId}/weeks/{weekId}', [TrainerTrainingPlanController::class, 'updateWeek']);
            Route::patch('/{planId}/days/{dayId}', [TrainerTrainingPlanController::class, 'updateDay']);

            // Workout assignment to days
            Route::post('/{planId}/days/{dayId}/workouts', [TrainerTrainingPlanController::class, 'assignWorkout']);
            Route::delete('/{planId}/days/{dayId}/workouts/{workoutId}', [TrainerTrainingPlanController::class, 'removeWorkout']);
        });
    });

    // Public workout routes
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);

    // Public gym routes
    Route::get('/gyms', [GymController::class, 'index']);

    // Protected gym management routes (requires authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // User's gyms (must come before slug route)
        Route::get('/gyms/my-gyms', [GymController::class, 'myGyms']);
        Route::post('/gyms', [GymController::class, 'store']);
        Route::patch('/gyms/{id}', [GymController::class, 'update']);
        Route::delete('/gyms/{id}', [GymController::class, 'destroy']);

        // Equipment management
        Route::post('/gyms/{id}/equipment', [GymController::class, 'addEquipment']);
        Route::patch('/gyms/{gymId}/equipment/{equipmentId}', [GymController::class, 'updateEquipment']);
        Route::delete('/gyms/{gymId}/equipment/{equipmentId}', [GymController::class, 'removeEquipment']);

        // Trainer management
        Route::get('/gyms/{gymId}/trainers', [GymController::class, 'listTrainers']);
        Route::post('/gyms/{gymId}/trainers', [GymController::class, 'hireTrainer']);
        Route::patch('/gyms/{gymId}/trainers/{trainerId}', [GymController::class, 'updateTrainer']);
        Route::post('/gyms/{gymId}/trainers/{trainerId}/terminate', [GymController::class, 'terminateTrainer']);
    });

    // Gym detail route (must come after my-gyms)
    Route::get('/gyms/{slug}', [GymController::class, 'show']);

    // Protected workout session routes (requires trainee profile)
    Route::middleware(['auth:sanctum', 'trainee'])->group(function () {
        Route::get('/sessions', [WorkoutSessionController::class, 'index']);
        Route::post('/sessions', [WorkoutSessionController::class, 'store']);
        Route::get('/sessions/{id}', [WorkoutSessionController::class, 'show']);
        Route::post('/sessions/{id}/complete', [WorkoutSessionController::class, 'complete']);
        Route::post('/sessions/{id}/abandon', [WorkoutSessionController::class, 'abandon']);

        // Exercise logging routes
        Route::post('/sessions/{sessionId}/exercises', [WorkoutSessionController::class, 'createExerciseLog']);
        Route::patch('/sessions/{sessionId}/exercises/{exerciseLogId}/complete', [WorkoutSessionController::class, 'completeExercise']);
        Route::patch('/sessions/{sessionId}/exercises/{exerciseLogId}/skip', [WorkoutSessionController::class, 'skipExercise']);

        // Set logging routes
        Route::post('/sessions/{sessionId}/exercises/{exerciseLogId}/sets', [WorkoutSessionController::class, 'createSetLog']);
        Route::patch('/sessions/{sessionId}/exercises/{exerciseLogId}/sets/{setLogId}', [WorkoutSessionController::class, 'updateSetLog']);
    });
});
