<?php

namespace App\Http\Controllers\Api;

use App\Domain\Execution\ExerciseLog;
use App\Domain\Execution\WorkoutSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExerciseLogResource;
use App\Shared\Enums\ExerciseLogStatus;
use App\Shared\Enums\SessionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseLogController extends Controller
{
    /**
     * Create an exercise log for a session.
     */
    public function store(Request $request, string $sessionId): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile',
            ], 403);
        }

        $session = WorkoutSession::where('trainee_id', $trainee->id)
            ->findOrFail($sessionId);

        if ($session->status === SessionStatus::COMPLETED) {
            return response()->json([
                'message' => 'Cannot log exercises for a completed session',
            ], 400);
        }

        $request->validate([
            'exercise_id' => 'required|exists:exercises,id',
            'sort_order' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $exerciseLog = ExerciseLog::create([
            'workout_session_id' => $session->id,
            'exercise_id' => $request->exercise_id,
            'sort_order' => $request->sort_order,
            'status' => ExerciseLogStatus::PENDING,
            'notes' => $request->notes,
        ]);

        $exerciseLog->load('exercise');

        return response()->json([
            'data' => new ExerciseLogResource($exerciseLog),
            'message' => 'Exercise log created successfully',
        ], 201);
    }

    /**
     * Start an exercise log.
     */
    public function start(Request $request, string $sessionId, string $exerciseLogId): JsonResponse
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

        $exerciseLog->start();
        $exerciseLog->load('exercise');

        return response()->json([
            'data' => new ExerciseLogResource($exerciseLog),
            'message' => 'Exercise started',
        ]);
    }

    /**
     * Complete an exercise log.
     */
    public function complete(Request $request, string $sessionId, string $exerciseLogId): JsonResponse
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

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $exerciseLog->complete($request->notes);
        $exerciseLog->load('exercise', 'setLogs');

        return response()->json([
            'data' => new ExerciseLogResource($exerciseLog),
            'message' => 'Exercise completed',
        ]);
    }

    /**
     * Skip an exercise log.
     */
    public function skip(Request $request, string $sessionId, string $exerciseLogId): JsonResponse
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

        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $exerciseLog->skip($request->notes);
        $exerciseLog->load('exercise');

        return response()->json([
            'data' => new ExerciseLogResource($exerciseLog),
            'message' => 'Exercise skipped',
        ]);
    }
}
