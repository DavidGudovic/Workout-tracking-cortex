<?php

namespace App\Http\Controllers\Api;

use App\Domain\Identity\TraineeProfile;
use App\Domain\Identity\TrainerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTraineeProfileRequest;
use App\Http\Requests\CreateTrainerProfileRequest;
use App\Http\Requests\UpdateTraineeProfileRequest;
use App\Http\Requests\UpdateTrainerProfileRequest;
use App\Http\Resources\TraineeProfileResource;
use App\Http\Resources\TrainerProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Create a trainer profile for the authenticated user.
     */
    public function createTrainerProfile(CreateTrainerProfileRequest $request): JsonResponse
    {
        // Check if user already has a trainer profile
        if ($request->user()->trainerProfile) {
            return response()->json([
                'message' => 'You already have a trainer profile',
            ], 400);
        }

        $trainerProfile = TrainerProfile::create([
            'user_id' => $request->user()->id,
            'display_name' => $request->display_name,
            'bio' => $request->bio,
            'specializations' => $request->specializations ?? [],
            'certifications' => $request->certifications ?? [],
            'years_experience' => $request->years_experience ?? 0,
            'hourly_rate_cents' => $request->hourly_rate_cents ?? 0,
        ]);

        return response()->json([
            'data' => new TrainerProfileResource($trainerProfile),
            'message' => 'Trainer profile created successfully',
        ], 201);
    }

    /**
     * Create a trainee profile for the authenticated user.
     */
    public function createTraineeProfile(CreateTraineeProfileRequest $request): JsonResponse
    {
        // Check if user already has a trainee profile
        if ($request->user()->traineeProfile) {
            return response()->json([
                'message' => 'You already have a trainee profile',
            ], 400);
        }

        $traineeProfile = TraineeProfile::create([
            'user_id' => $request->user()->id,
            'display_name' => $request->display_name,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'height_cm' => $request->height_cm,
            'weight_kg' => $request->weight_kg,
            'fitness_goal' => $request->fitness_goal,
            'experience_level' => $request->experience_level,
        ]);

        return response()->json([
            'data' => new TraineeProfileResource($traineeProfile),
            'message' => 'Trainee profile created successfully',
        ], 201);
    }

    /**
     * Get the authenticated user's trainer profile.
     */
    public function getTrainerProfile(Request $request): JsonResponse
    {
        $trainerProfile = $request->user()->trainerProfile;

        if (!$trainerProfile) {
            return response()->json([
                'message' => 'You do not have a trainer profile',
            ], 404);
        }

        return response()->json([
            'data' => new TrainerProfileResource($trainerProfile),
        ]);
    }

    /**
     * Get the authenticated user's trainee profile.
     */
    public function getTraineeProfile(Request $request): JsonResponse
    {
        $traineeProfile = $request->user()->traineeProfile;

        if (!$traineeProfile) {
            return response()->json([
                'message' => 'You do not have a trainee profile',
            ], 404);
        }

        return response()->json([
            'data' => new TraineeProfileResource($traineeProfile),
        ]);
    }

    /**
     * Update the authenticated user's trainer profile.
     */
    public function updateTrainerProfile(UpdateTrainerProfileRequest $request): JsonResponse
    {
        $trainerProfile = $request->user()->trainerProfile;

        if (!$trainerProfile) {
            return response()->json([
                'message' => 'You do not have a trainer profile',
            ], 404);
        }

        $trainerProfile->update($request->validated());

        return response()->json([
            'data' => new TrainerProfileResource($trainerProfile),
            'message' => 'Trainer profile updated successfully',
        ]);
    }

    /**
     * Update the authenticated user's trainee profile.
     */
    public function updateTraineeProfile(UpdateTraineeProfileRequest $request): JsonResponse
    {
        $traineeProfile = $request->user()->traineeProfile;

        if (!$traineeProfile) {
            return response()->json([
                'message' => 'You do not have a trainee profile',
            ], 404);
        }

        $traineeProfile->update($request->validated());

        return response()->json([
            'data' => new TraineeProfileResource($traineeProfile),
            'message' => 'Trainee profile updated successfully',
        ]);
    }

    /**
     * Delete the authenticated user's trainer profile.
     */
    public function deleteTrainerProfile(Request $request): JsonResponse
    {
        $trainerProfile = $request->user()->trainerProfile;

        if (!$trainerProfile) {
            return response()->json([
                'message' => 'You do not have a trainer profile',
            ], 404);
        }

        $trainerProfile->delete();

        return response()->json([
            'message' => 'Trainer profile deleted successfully',
        ], 204);
    }

    /**
     * Delete the authenticated user's trainee profile.
     */
    public function deleteTraineeProfile(Request $request): JsonResponse
    {
        $traineeProfile = $request->user()->traineeProfile;

        if (!$traineeProfile) {
            return response()->json([
                'message' => 'You do not have a trainee profile',
            ], 404);
        }

        $traineeProfile->delete();

        return response()->json([
            'message' => 'Trainee profile deleted successfully',
        ], 204);
    }
}
