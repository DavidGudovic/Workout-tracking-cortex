<?php

namespace App\Http\Controllers\Api;

use App\Domain\Execution\WorkoutSession;
use App\Domain\Training\Workout;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkoutSessionResource;
use App\Shared\Enums\SessionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutSessionController extends Controller
{
    /**
     * Start a new workout session.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'workout_id' => 'required|exists:workouts,id',
        ]);

        // Verify user has trainee profile
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile to start a workout session',
            ], 403);
        }

        // Create the session
        $session = WorkoutSession::create([
            'trainee_id' => $trainee->id,
            'workout_id' => $request->workout_id,
            'started_at' => now(),
            'status' => SessionStatus::STARTED,
        ]);

        // Load relationships
        $session->load('workout', 'exerciseLogs');

        return response()->json([
            'data' => new WorkoutSessionResource($session),
            'message' => 'Workout session started successfully',
        ], 201);
    }

    /**
     * Display the specified workout session.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile',
            ], 403);
        }

        $session = WorkoutSession::with(['workout', 'exerciseLogs.exercise', 'exerciseLogs.setLogs'])
            ->where('trainee_id', $trainee->id)
            ->findOrFail($id);

        return response()->json([
            'data' => new WorkoutSessionResource($session),
        ]);
    }

    /**
     * Complete a workout session.
     */
    public function complete(Request $request, string $id): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile',
            ], 403);
        }

        $session = WorkoutSession::where('trainee_id', $trainee->id)
            ->findOrFail($id);

        if ($session->status === SessionStatus::COMPLETED) {
            return response()->json([
                'message' => 'Session is already completed',
            ], 400);
        }

        $session->complete();
        $session->load('workout', 'exerciseLogs');

        return response()->json([
            'data' => new WorkoutSessionResource($session),
            'message' => 'Workout session completed successfully',
        ]);
    }

    /**
     * Get all sessions for the authenticated trainee.
     */
    public function index(Request $request): JsonResponse
    {
        $trainee = $request->user()->traineeProfile;
        if (!$trainee) {
            return response()->json([
                'message' => 'You must have a trainee profile',
            ], 403);
        }

        $sessions = WorkoutSession::with(['workout'])
            ->where('trainee_id', $trainee->id)
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json([
            'data' => WorkoutSessionResource::collection($sessions),
        ]);
    }
}
