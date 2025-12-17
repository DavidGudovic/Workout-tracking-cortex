<?php

namespace App\Http\Controllers\Api;

use App\Domain\Training\Workout;
use App\Http\Controllers\Controller;
use App\Http\Resources\WorkoutResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkoutController extends Controller
{
    /**
     * Display a listing of published workouts.
     */
    public function index(): AnonymousResourceCollection
    {
        $workouts = Workout::with(['creator', 'workoutExercises.exercise'])
            ->published()
            ->orderBy('created_at', 'desc')
            ->get();

        return WorkoutResource::collection($workouts);
    }

    /**
     * Display the specified workout.
     */
    public function show(string $id): WorkoutResource
    {
        $workout = Workout::with(['creator', 'workoutExercises.exercise'])
            ->published()
            ->findOrFail($id);

        return new WorkoutResource($workout);
    }
}
