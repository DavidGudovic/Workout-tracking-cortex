<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExerciseLogController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SetLogController;
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
    });

    // Public workout routes
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);

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
