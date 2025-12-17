<?php

namespace App\Http\Controllers\Api;

use App\Domain\Training\Workout;
use App\Domain\Training\WorkoutExercise;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddWorkoutExerciseRequest;
use App\Http\Requests\CreateWorkoutRequest;
use App\Http\Requests\UpdateWorkoutExerciseRequest;
use App\Http\Requests\UpdateWorkoutRequest;
use App\Http\Resources\WorkoutExerciseResource;
use App\Http\Resources\WorkoutResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerWorkoutController extends Controller
{
    /**
     * Display a listing of the trainer's workouts.
     */
    public function index(): AnonymousResourceCollection
    {
        $trainer = request()->user()->trainerProfile;

        $workouts = Workout::with(['creator', 'workoutExercises.exercise'])
            ->where('creator_id', $trainer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return WorkoutResource::collection($workouts);
    }

    /**
     * Store a newly created workout.
     */
    public function store(CreateWorkoutRequest $request): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $workout = Workout::create([
            'creator_id' => $trainer->id,
            'name' => $request->name,
            'description' => $request->description,
            'cover_image_url' => $request->cover_image_url,
            'difficulty' => $request->difficulty ?? 'beginner',
            'estimated_duration_minutes' => $request->estimated_duration_minutes,
            'pricing_type' => $request->pricing_type ?? 'free',
            'price_cents' => $request->price_cents,
            'tags' => $request->tags,
        ]);

        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Workout created successfully',
        ], 201);
    }

    /**
     * Display the specified workout.
     */
    public function show(string $id): WorkoutResource
    {
        $trainer = request()->user()->trainerProfile;

        $workout = Workout::with(['creator', 'workoutExercises.exercise'])
            ->where('creator_id', $trainer->id)
            ->findOrFail($id);

        return new WorkoutResource($workout);
    }

    /**
     * Update the specified workout.
     */
    public function update(UpdateWorkoutRequest $request, string $id): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $workout = Workout::findOrFail($id);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workout->update($request->only([
            'name',
            'description',
            'cover_image_url',
            'difficulty',
            'estimated_duration_minutes',
            'pricing_type',
            'price_cents',
            'tags',
        ]));

        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Workout updated successfully',
        ]);
    }

    /**
     * Remove the specified workout.
     */
    public function destroy(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $workout = Workout::findOrFail($id);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workout->delete();

        return response()->json(null, 204);
    }

    /**
     * Publish a draft workout.
     */
    public function publish(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $workout = Workout::findOrFail($id);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workout->publish();

        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Workout published successfully',
        ]);
    }

    /**
     * Archive a workout.
     */
    public function archive(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $workout = Workout::findOrFail($id);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workout->archive();

        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Workout archived successfully',
        ]);
    }

    /**
     * Revert workout to draft status.
     */
    public function draft(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $workout = Workout::findOrFail($id);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workout->revertToDraft();

        return response()->json([
            'data' => new WorkoutResource($workout),
            'message' => 'Workout reverted to draft successfully',
        ]);
    }

    /**
     * Add an exercise to the workout.
     */
    public function addExercise(AddWorkoutExerciseRequest $request, string $workoutId): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $workout = Workout::findOrFail($workoutId);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workoutExercise = WorkoutExercise::create([
            'workout_id' => $workout->id,
            'exercise_id' => $request->exercise_id,
            'sort_order' => $request->sort_order,
            'sets' => $request->sets,
            'target_reps' => $request->target_reps,
            'target_duration_seconds' => $request->target_duration_seconds,
            'target_distance_meters' => $request->target_distance_meters,
            'rest_seconds' => $request->rest_seconds ?? 60,
            'notes' => $request->notes,
            'superset_group' => $request->superset_group,
            'is_optional' => $request->is_optional ?? false,
        ]);

        $workoutExercise->load('exercise');

        return response()->json([
            'data' => new WorkoutExerciseResource($workoutExercise),
            'message' => 'Exercise added to workout successfully',
        ], 201);
    }

    /**
     * Update an exercise in the workout.
     */
    public function updateExercise(UpdateWorkoutExerciseRequest $request, string $workoutId, string $exerciseId): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $workout = Workout::findOrFail($workoutId);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
            ->findOrFail($exerciseId);

        $workoutExercise->update($request->only([
            'sort_order',
            'sets',
            'target_reps',
            'target_duration_seconds',
            'target_distance_meters',
            'rest_seconds',
            'notes',
            'superset_group',
            'is_optional',
        ]));

        $workoutExercise->load('exercise');

        return response()->json([
            'data' => new WorkoutExerciseResource($workoutExercise),
            'message' => 'Exercise updated successfully',
        ]);
    }

    /**
     * Remove an exercise from the workout.
     */
    public function removeExercise(string $workoutId, string $exerciseId): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $workout = Workout::findOrFail($workoutId);

        // Check authorization
        if ($workout->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workoutExercise = WorkoutExercise::where('workout_id', $workoutId)
            ->findOrFail($exerciseId);

        $workoutExercise->delete();

        return response()->json(null, 204);
    }
}
