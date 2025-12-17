<?php

namespace App\Http\Controllers\Api;

use App\Domain\Execution\ExerciseLog;
use App\Domain\Execution\SetLog;
use App\Domain\Execution\WorkoutSession;
use App\Domain\Training\Workout;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteSessionRequest;
use App\Http\Requests\CreateExerciseLogRequest;
use App\Http\Requests\CreateSetLogRequest;
use App\Http\Requests\CreateWorkoutSessionRequest;
use App\Http\Requests\UpdateExerciseLogRequest;
use App\Http\Requests\UpdateSetLogRequest;
use App\Http\Resources\ExerciseLogResource;
use App\Http\Resources\SetLogResource;
use App\Http\Resources\WorkoutSessionResource;
use App\Shared\Enums\ExerciseLogStatus;
use App\Shared\Enums\SessionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkoutSessionController extends Controller
{
    /**
     * Display a listing of the trainee's workout sessions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $trainee = $request->user()->traineeProfile;

        $query = WorkoutSession::with(['workout', 'exerciseLogs.exercise'])
            ->where('trainee_id', $trainee->id)
            ->orderBy('started_at', 'desc');

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->betweenDates($request->start_date, $request->end_date);
        }

        // Filter by workout
        if ($request->has('workout_id')) {
            $query->where('workout_id', $request->workout_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query->get();

        return WorkoutSessionResource::collection($sessions);
    }

    /**
     * Start a new workout session.
     */
    public function store(CreateWorkoutSessionRequest $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $workout = Workout::findOrFail($request->workout_id);

        $session = WorkoutSession::create([
            'trainee_id' => $trainee->id,
            'workout_id' => $workout->id,
            'workout_version' => $workout->version,
            'training_plan_id' => $request->training_plan_id,
            'training_plan_week_number' => $request->training_plan_week_number,
            'training_plan_day_number' => $request->training_plan_day_number,
            'started_at' => now(),
            'status' => SessionStatus::STARTED,
        ]);

        return response()->json([
            'data' => new WorkoutSessionResource($session->load('workout')),
            'message' => 'Workout session started successfully',
        ], 201);
    }

    /**
     * Display the specified workout session.
     */
    public function show(string $id, Request $request): WorkoutSessionResource
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::with(['workout', 'exerciseLogs.exercise', 'exerciseLogs.setLogs'])
            ->findOrFail($id);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            abort(403, 'Unauthorized');
        }

        return new WorkoutSessionResource($session);
    }

    /**
     * Complete a workout session.
     */
    public function complete(string $id, CompleteSessionRequest $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::findOrFail($id);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if already completed
        if ($session->isCompleted()) {
            return response()->json(['message' => 'Session is already completed'], 422);
        }

        // Update notes and rating if provided
        if ($request->has('notes')) {
            $session->notes = $request->notes;
        }
        if ($request->has('rating')) {
            $session->rating = $request->rating;
        }

        // Complete the session (triggers calculateTotals)
        $session->complete();

        return response()->json([
            'data' => new WorkoutSessionResource($session->fresh()),
            'message' => 'Workout session completed successfully',
        ]);
    }

    /**
     * Abandon a workout session.
     */
    public function abandon(string $id, Request $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::findOrFail($id);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if already completed
        if ($session->isCompleted()) {
            return response()->json(['message' => 'Session is already completed'], 422);
        }

        $session->abandon();

        return response()->json([
            'data' => new WorkoutSessionResource($session->fresh()),
            'message' => 'Workout session abandoned',
        ]);
    }

    /**
     * Start an exercise in the session.
     */
    public function createExerciseLog(string $sessionId, CreateExerciseLogRequest $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::findOrFail($sessionId);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if session is completed
        if ($session->logsAreImmutable()) {
            return response()->json(['message' => 'Cannot modify completed session'], 422);
        }

        $workoutExercise = $session->workout->workoutExercises()
            ->findOrFail($request->workout_exercise_id);

        $exerciseLog = ExerciseLog::create([
            'workout_session_id' => $session->id,
            'workout_exercise_id' => $workoutExercise->id,
            'exercise_id' => $workoutExercise->exercise_id,
            'status' => ExerciseLogStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);

        return response()->json([
            'data' => new ExerciseLogResource($exerciseLog->load('exercise')),
            'message' => 'Exercise started successfully',
        ], 201);
    }

    /**
     * Complete an exercise in the session.
     */
    public function completeExercise(string $sessionId, string $exerciseLogId, UpdateExerciseLogRequest $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::findOrFail($sessionId);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if session is completed
        if ($session->logsAreImmutable()) {
            return response()->json(['message' => 'Cannot modify completed session'], 422);
        }

        $exerciseLog = ExerciseLog::where('workout_session_id', $sessionId)
            ->findOrFail($exerciseLogId);

        if ($request->has('notes')) {
            $exerciseLog->notes = $request->notes;
            $exerciseLog->save();
        }

        $exerciseLog->complete();

        return response()->json([
            'data' => new ExerciseLogResource($exerciseLog->fresh()),
            'message' => 'Exercise completed successfully',
        ]);
    }

    /**
     * Skip an exercise in the session.
     */
    public function skipExercise(string $sessionId, string $exerciseLogId, Request $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::findOrFail($sessionId);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if session is completed
        if ($session->logsAreImmutable()) {
            return response()->json(['message' => 'Cannot modify completed session'], 422);
        }

        $exerciseLog = ExerciseLog::where('workout_session_id', $sessionId)
            ->findOrFail($exerciseLogId);

        $exerciseLog->skip();

        return response()->json([
            'data' => new ExerciseLogResource($exerciseLog->fresh()),
            'message' => 'Exercise skipped',
        ]);
    }

    /**
     * Log a set for an exercise.
     */
    public function createSetLog(string $sessionId, string $exerciseLogId, CreateSetLogRequest $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::findOrFail($sessionId);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if session is completed
        if ($session->logsAreImmutable()) {
            return response()->json(['message' => 'Cannot modify completed session'], 422);
        }

        $exerciseLog = ExerciseLog::where('workout_session_id', $sessionId)
            ->findOrFail($exerciseLogId);

        $setLog = SetLog::create([
            'exercise_log_id' => $exerciseLog->id,
            'set_number' => $request->set_number,
            'target_reps' => $request->target_reps,
            'actual_reps' => $request->actual_reps,
            'target_duration_seconds' => $request->target_duration_seconds,
            'actual_duration_seconds' => $request->actual_duration_seconds,
            'target_distance_meters' => $request->target_distance_meters,
            'actual_distance_meters' => $request->actual_distance_meters,
            'weight_kg' => $request->weight_kg,
            'rpe' => $request->rpe,
            'is_warmup' => $request->is_warmup ?? false,
            'is_failure' => $request->is_failure ?? false,
            'completed_at' => now(),
        ]);

        return response()->json([
            'data' => new SetLogResource($setLog),
            'message' => 'Set logged successfully',
        ], 201);
    }

    /**
     * Update a set log.
     */
    public function updateSetLog(string $sessionId, string $exerciseLogId, string $setLogId, UpdateSetLogRequest $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;

        $session = WorkoutSession::findOrFail($sessionId);

        // Check authorization
        if ($session->trainee_id !== $trainee->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if session is completed
        if ($session->logsAreImmutable()) {
            return response()->json(['message' => 'Cannot modify completed session'], 422);
        }

        $setLog = SetLog::whereHas('exerciseLog', function ($query) use ($sessionId, $exerciseLogId) {
            $query->where('workout_session_id', $sessionId)
                ->where('id', $exerciseLogId);
        })->findOrFail($setLogId);

        $setLog->update($request->only([
            'actual_reps',
            'actual_duration_seconds',
            'actual_distance_meters',
            'weight_kg',
            'rpe',
            'is_warmup',
            'is_failure',
        ]));

        return response()->json([
            'data' => new SetLogResource($setLog->fresh()),
            'message' => 'Set updated successfully',
        ]);
    }
}
