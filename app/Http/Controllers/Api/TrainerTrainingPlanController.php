<?php

namespace App\Http\Controllers\Api;

use App\Domain\Training\TrainingPlan;
use App\Domain\Training\TrainingPlanDay;
use App\Domain\Training\TrainingPlanWeek;
use App\Domain\Training\TrainingPlanWorkout;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignWorkoutRequest;
use App\Http\Requests\CreateTrainingPlanRequest;
use App\Http\Requests\UpdateDayRequest;
use App\Http\Requests\UpdateTrainingPlanRequest;
use App\Http\Requests\UpdateWeekRequest;
use App\Http\Resources\TrainingPlanDayResource;
use App\Http\Resources\TrainingPlanResource;
use App\Http\Resources\TrainingPlanWeekResource;
use App\Http\Resources\TrainingPlanWorkoutResource;
use App\Shared\Enums\WorkoutStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TrainerTrainingPlanController extends Controller
{
    /**
     * Display a listing of the trainer's training plans.
     */
    public function index(): AnonymousResourceCollection
    {
        $trainer = request()->user()->trainerProfile;

        $plans = TrainingPlan::with(['creator', 'weeks.days'])
            ->where('creator_id', $trainer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return TrainingPlanResource::collection($plans);
    }

    /**
     * Store a newly created training plan.
     */
    public function store(CreateTrainingPlanRequest $request): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $plan = TrainingPlan::create([
            'creator_id' => $trainer->id,
            'name' => $request->name,
            'description' => $request->description,
            'cover_image_url' => $request->cover_image_url,
            'goal' => $request->goal,
            'difficulty' => $request->difficulty ?? 'beginner',
            'duration_weeks' => $request->duration_weeks,
            'days_per_week' => $request->days_per_week,
            'pricing_type' => $request->pricing_type ?? 'free',
            'price_cents' => $request->price_cents,
        ]);

        $plan->load('creator');

        return response()->json([
            'data' => new TrainingPlanResource($plan),
            'message' => 'Training plan created successfully',
        ], 201);
    }

    /**
     * Display the specified training plan.
     */
    public function show(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $plan = TrainingPlan::with(['creator', 'weeks.days.workouts.workout'])->findOrFail($id);

        // Check authorization: only creator can view draft plans
        if ($plan->status === WorkoutStatus::DRAFT && $plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => new TrainingPlanResource($plan),
        ]);
    }

    /**
     * Update the specified training plan.
     */
    public function update(UpdateTrainingPlanRequest $request, string $id): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($id);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plan->update($request->only([
            'name',
            'description',
            'cover_image_url',
            'goal',
            'difficulty',
            'duration_weeks',
            'days_per_week',
            'pricing_type',
            'price_cents',
        ]));

        return response()->json([
            'data' => new TrainingPlanResource($plan),
            'message' => 'Training plan updated successfully',
        ]);
    }

    /**
     * Remove the specified training plan.
     */
    public function destroy(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($id);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plan->delete();

        return response()->json(null, 204);
    }

    /**
     * Publish a draft training plan.
     */
    public function publish(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($id);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plan->publish();

        return response()->json([
            'data' => new TrainingPlanResource($plan),
            'message' => 'Training plan published successfully',
        ]);
    }

    /**
     * Archive a training plan.
     */
    public function archive(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($id);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plan->archive();

        return response()->json([
            'data' => new TrainingPlanResource($plan),
            'message' => 'Training plan archived successfully',
        ]);
    }

    /**
     * Revert training plan to draft status.
     */
    public function draft(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($id);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $plan->revertToDraft();

        return response()->json([
            'data' => new TrainingPlanResource($plan),
            'message' => 'Training plan reverted to draft successfully',
        ]);
    }

    /**
     * Generate the structure (weeks and days) for the training plan.
     */
    public function generateStructure(string $id): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($id);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if structure already exists
        if ($plan->weeks()->count() > 0) {
            return response()->json([
                'message' => 'Plan structure already exists. Delete existing weeks first to regenerate.',
            ], 422);
        }

        $plan->generateStructure();
        $plan->load('weeks.days');

        return response()->json([
            'data' => new TrainingPlanResource($plan),
            'message' => 'Training plan structure generated successfully',
        ]);
    }

    /**
     * Update a week in the training plan.
     */
    public function updateWeek(UpdateWeekRequest $request, string $planId, string $weekId): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($planId);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $week = TrainingPlanWeek::where('training_plan_id', $planId)
            ->findOrFail($weekId);

        $week->update($request->only(['name', 'notes']));

        return response()->json([
            'data' => new TrainingPlanWeekResource($week),
            'message' => 'Week updated successfully',
        ]);
    }

    /**
     * Update a day in the training plan.
     */
    public function updateDay(UpdateDayRequest $request, string $planId, string $dayId): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($planId);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $day = TrainingPlanDay::whereHas('week', function ($query) use ($planId) {
            $query->where('training_plan_id', $planId);
        })->findOrFail($dayId);

        $day->update($request->only(['name', 'notes', 'is_rest_day']));

        return response()->json([
            'data' => new TrainingPlanDayResource($day),
            'message' => 'Day updated successfully',
        ]);
    }

    /**
     * Assign a workout to a day.
     */
    public function assignWorkout(AssignWorkoutRequest $request, string $planId, string $dayId): JsonResponse
    {
        $trainer = $request->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($planId);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $day = TrainingPlanDay::whereHas('week', function ($query) use ($planId) {
            $query->where('training_plan_id', $planId);
        })->findOrFail($dayId);

        // Check if day is a rest day
        if ($day->is_rest_day) {
            return response()->json([
                'message' => 'Cannot assign workouts to rest days.',
            ], 422);
        }

        $planWorkout = TrainingPlanWorkout::create([
            'training_plan_day_id' => $day->id,
            'workout_id' => $request->workout_id,
            'sort_order' => $request->sort_order ?? 0,
            'is_optional' => $request->is_optional ?? false,
        ]);

        $planWorkout->load('workout');

        return response()->json([
            'data' => new TrainingPlanWorkoutResource($planWorkout),
            'message' => 'Workout assigned to day successfully',
        ], 201);
    }

    /**
     * Remove a workout from a day.
     */
    public function removeWorkout(string $planId, string $dayId, string $workoutId): JsonResponse
    {
        $trainer = request()->user()->trainerProfile;

        $plan = TrainingPlan::findOrFail($planId);

        // Check authorization
        if ($plan->creator_id !== $trainer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $planWorkout = TrainingPlanWorkout::where('training_plan_day_id', $dayId)
            ->findOrFail($workoutId);

        $planWorkout->delete();

        return response()->json(null, 204);
    }
}
