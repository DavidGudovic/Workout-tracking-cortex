<?php

namespace App\Http\Controllers\Api;

use App\Domain\Execution\ExerciseLog;
use App\Domain\Execution\SetLog;
use App\Domain\Execution\WorkoutSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\SetLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetLogController extends Controller
{
    /**
     * Create a set log for an exercise.
     */
    public function store(Request $request, string $sessionId, string $exerciseLogId): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile',
            ], 403);
        }

        $session = WorkoutSession::where('trainee_id', $trainee->id)
            ->findOrFail($sessionId);

        $exerciseLog = ExerciseLog::where('workout_session_id', $session->id)
            ->findOrFail($exerciseLogId);

        $validated = $request->validate([
            'set_number' => 'required|integer|min:1',
            'target_reps' => 'nullable|integer|min:0',
            'actual_reps' => 'nullable|integer|min:0',
            'target_duration_seconds' => 'nullable|integer|min:0',
            'actual_duration_seconds' => 'nullable|integer|min:0',
            'target_distance_meters' => 'nullable|integer|min:0',
            'actual_distance_meters' => 'nullable|integer|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'rpe' => 'nullable|integer|min:1|max:10',
            'is_warmup' => 'boolean',
            'is_failure' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $setLog = SetLog::create([
            'exercise_log_id' => $exerciseLog->id,
            'set_number' => $validated['set_number'],
            'target_reps' => $validated['target_reps'] ?? null,
            'actual_reps' => $validated['actual_reps'] ?? null,
            'target_duration_seconds' => $validated['target_duration_seconds'] ?? null,
            'actual_duration_seconds' => $validated['actual_duration_seconds'] ?? null,
            'target_distance_meters' => $validated['target_distance_meters'] ?? null,
            'actual_distance_meters' => $validated['actual_distance_meters'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'rpe' => $validated['rpe'] ?? null,
            'is_warmup' => $validated['is_warmup'] ?? false,
            'is_failure' => $validated['is_failure'] ?? false,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'data' => new SetLogResource($setLog),
            'message' => 'Set logged successfully',
        ], 201);
    }

    /**
     * Update a set log.
     */
    public function update(Request $request, string $sessionId, string $exerciseLogId, string $setLogId): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile',
            ], 403);
        }

        $session = WorkoutSession::where('trainee_id', $trainee->id)
            ->findOrFail($sessionId);

        $exerciseLog = ExerciseLog::where('workout_session_id', $session->id)
            ->findOrFail($exerciseLogId);

        $setLog = SetLog::where('exercise_log_id', $exerciseLog->id)
            ->findOrFail($setLogId);

        // Check if session is completed (logs are immutable after completion)
        if ($session->logsAreImmutable()) {
            return response()->json([
                'message' => 'Cannot update set logs for a completed session',
            ], 400);
        }

        $validated = $request->validate([
            'actual_reps' => 'nullable|integer|min:0',
            'actual_duration_seconds' => 'nullable|integer|min:0',
            'actual_distance_meters' => 'nullable|integer|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'rpe' => 'nullable|integer|min:1|max:10',
            'is_warmup' => 'boolean',
            'is_failure' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $setLog->update($validated);

        return response()->json([
            'data' => new SetLogResource($setLog),
            'message' => 'Set updated successfully',
        ]);
    }

    /**
     * Delete a set log.
     */
    public function destroy(Request $request, string $sessionId, string $exerciseLogId, string $setLogId): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile',
            ], 403);
        }

        $session = WorkoutSession::where('trainee_id', $trainee->id)
            ->findOrFail($sessionId);

        // Check if session is completed (logs are immutable after completion)
        if ($session->logsAreImmutable()) {
            return response()->json([
                'message' => 'Cannot delete set logs for a completed session',
            ], 400);
        }

        $exerciseLog = ExerciseLog::where('workout_session_id', $session->id)
            ->findOrFail($exerciseLogId);

        $setLog = SetLog::where('exercise_log_id', $exerciseLog->id)
            ->findOrFail($setLogId);

        $setLog->delete();

        return response()->json([
            'message' => 'Set deleted successfully',
        ], 204);
    }
}
