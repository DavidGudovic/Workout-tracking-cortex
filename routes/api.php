<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ExerciseLogController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SetLogController;
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
    });

    // Public workout routes
    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::get('/workouts/{id}', [WorkoutController::class, 'show']);

    // Protected workout session routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/sessions', [WorkoutSessionController::class, 'index']);
        Route::post('/sessions', [WorkoutSessionController::class, 'store']);
        Route::get('/sessions/{id}', [WorkoutSessionController::class, 'show']);
        Route::post('/sessions/{id}/complete', [WorkoutSessionController::class, 'complete']);

        // Exercise logging routes
        Route::post('/sessions/{sessionId}/exercises', [ExerciseLogController::class, 'store']);
        Route::post('/sessions/{sessionId}/exercises/{exerciseLogId}/start', [ExerciseLogController::class, 'start']);
        Route::post('/sessions/{sessionId}/exercises/{exerciseLogId}/complete', [ExerciseLogController::class, 'complete']);
        Route::post('/sessions/{sessionId}/exercises/{exerciseLogId}/skip', [ExerciseLogController::class, 'skip']);

        // Set logging routes
        Route::post('/sessions/{sessionId}/exercises/{exerciseLogId}/sets', [SetLogController::class, 'store']);
        Route::patch('/sessions/{sessionId}/exercises/{exerciseLogId}/sets/{setLogId}', [SetLogController::class, 'update']);
        Route::delete('/sessions/{sessionId}/exercises/{exerciseLogId}/sets/{setLogId}', [SetLogController::class, 'destroy']);
    });
});
