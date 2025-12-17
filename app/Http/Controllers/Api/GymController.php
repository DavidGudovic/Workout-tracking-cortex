<?php

namespace App\Http\Controllers\Api;

use App\Domain\Gym\Gym;
use App\Domain\Gym\GymEquipment;
use App\Domain\Gym\GymTrainer;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddEquipmentRequest;
use App\Http\Requests\CreateGymRequest;
use App\Http\Requests\HireTrainerRequest;
use App\Http\Requests\TerminateTrainerRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Requests\UpdateGymRequest;
use App\Http\Requests\UpdateGymTrainerRequest;
use App\Http\Resources\GymEquipmentResource;
use App\Http\Resources\GymResource;
use App\Http\Resources\GymTrainerResource;
use App\Shared\Enums\GymStatus;
use App\Shared\Enums\GymTrainerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GymController extends Controller
{
    /**
     * Display a listing of active gyms (public).
     */
    public function index(): AnonymousResourceCollection
    {
        $gyms = Gym::with(['owner', 'gymEquipment.equipment'])
            ->where('status', GymStatus::ACTIVE)
            ->orderBy('created_at', 'desc')
            ->get();

        return GymResource::collection($gyms);
    }

    /**
     * Display a listing of the authenticated user's gyms.
     */
    public function myGyms(): AnonymousResourceCollection
    {
        $user = request()->user();

        $gyms = Gym::with(['owner', 'gymEquipment.equipment', 'trainerAssociations.trainer'])
            ->where('owner_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return GymResource::collection($gyms);
    }

    /**
     * Store a newly created gym.
     */
    public function store(CreateGymRequest $request): JsonResponse
    {
        $user = $request->user();

        $gym = Gym::create([
            'owner_id' => $user->id,
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'logo_url' => $request->logo_url,
            'cover_image_url' => $request->cover_image_url,
            'address_line1' => $request->address_line1,
            'address_line2' => $request->address_line2,
            'city' => $request->city,
            'state' => $request->state,
            'postal_code' => $request->postal_code,
            'country' => $request->country,
            'phone' => $request->phone,
            'website_url' => $request->website_url,
        ]);

        $gym->load('owner');

        return response()->json([
            'data' => new GymResource($gym),
            'message' => 'Gym created successfully',
        ], 201);
    }

    /**
     * Display the specified gym (by slug).
     */
    public function show(string $slug): JsonResponse
    {
        $gym = Gym::with(['owner', 'gymEquipment.equipment', 'subscriptionTiers'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => new GymResource($gym),
        ]);
    }

    /**
     * Update the specified gym.
     */
    public function update(UpdateGymRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        $gym = Gym::findOrFail($id);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gym->update($request->only([
            'name',
            'slug',
            'description',
            'logo_url',
            'cover_image_url',
            'address_line1',
            'address_line2',
            'city',
            'state',
            'postal_code',
            'country',
            'phone',
            'website_url',
        ]));

        return response()->json([
            'data' => new GymResource($gym),
            'message' => 'Gym updated successfully',
        ]);
    }

    /**
     * Remove the specified gym.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = request()->user();
        $gym = Gym::findOrFail($id);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gym->delete();

        return response()->json(null, 204);
    }

    /*
    |--------------------------------------------------------------------------
    | Equipment Management
    |--------------------------------------------------------------------------
    */

    /**
     * Add equipment to the gym.
     */
    public function addEquipment(AddEquipmentRequest $request, string $id): JsonResponse
    {
        $user = $request->user();
        $gym = Gym::findOrFail($id);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if equipment already exists for this gym
        $exists = GymEquipment::where('gym_id', $gym->id)
            ->where('equipment_id', $request->equipment_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This equipment is already added to the gym.',
            ], 422);
        }

        $gymEquipment = GymEquipment::create([
            'gym_id' => $gym->id,
            'equipment_id' => $request->equipment_id,
            'quantity' => $request->quantity ?? 1,
            'notes' => $request->notes,
        ]);

        $gymEquipment->load('equipment');

        return response()->json([
            'data' => new GymEquipmentResource($gymEquipment),
            'message' => 'Equipment added to gym successfully',
        ], 201);
    }

    /**
     * Update equipment in the gym.
     */
    public function updateEquipment(UpdateEquipmentRequest $request, string $gymId, string $equipmentId): JsonResponse
    {
        $user = $request->user();
        $gym = Gym::findOrFail($gymId);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gymEquipment = GymEquipment::where('gym_id', $gymId)
            ->where('equipment_id', $equipmentId)
            ->firstOrFail();

        $gymEquipment->update($request->only(['quantity', 'notes']));

        $gymEquipment->load('equipment');

        return response()->json([
            'data' => new GymEquipmentResource($gymEquipment),
            'message' => 'Equipment updated successfully',
        ]);
    }

    /**
     * Remove equipment from the gym.
     */
    public function removeEquipment(string $gymId, string $equipmentId): JsonResponse
    {
        $user = request()->user();
        $gym = Gym::findOrFail($gymId);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gymEquipment = GymEquipment::where('gym_id', $gymId)
            ->where('equipment_id', $equipmentId)
            ->firstOrFail();

        $gymEquipment->delete();

        return response()->json(null, 204);
    }

    /*
    |--------------------------------------------------------------------------
    | Trainer Management
    |--------------------------------------------------------------------------
    */

    /**
     * List all trainers for the gym.
     */
    public function listTrainers(string $gymId): AnonymousResourceCollection
    {
        $gym = Gym::findOrFail($gymId);

        $trainers = GymTrainer::with('trainer')
            ->where('gym_id', $gymId)
            ->orderBy('created_at', 'desc')
            ->get();

        return GymTrainerResource::collection($trainers);
    }

    /**
     * Hire a trainer for the gym.
     */
    public function hireTrainer(HireTrainerRequest $request, string $gymId): JsonResponse
    {
        $user = $request->user();
        $gym = Gym::findOrFail($gymId);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if trainer is already hired
        $exists = GymTrainer::where('gym_id', $gym->id)
            ->where('trainer_id', $request->trainer_id)
            ->where('status', GymTrainerStatus::ACTIVE)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This trainer is already hired at this gym.',
            ], 422);
        }

        $gymTrainer = GymTrainer::create([
            'gym_id' => $gym->id,
            'trainer_id' => $request->trainer_id,
            'status' => GymTrainerStatus::ACTIVE,
            'role' => $request->role,
            'hourly_rate_cents' => $request->hourly_rate_cents,
            'commission_percentage' => $request->commission_percentage,
            'hired_at' => now(),
        ]);

        $gymTrainer->load('trainer');

        return response()->json([
            'data' => new GymTrainerResource($gymTrainer),
            'message' => 'Trainer hired successfully',
        ], 201);
    }

    /**
     * Update trainer details.
     */
    public function updateTrainer(UpdateGymTrainerRequest $request, string $gymId, string $trainerId): JsonResponse
    {
        $user = $request->user();
        $gym = Gym::findOrFail($gymId);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gymTrainer = GymTrainer::where('gym_id', $gymId)
            ->where('trainer_id', $trainerId)
            ->firstOrFail();

        $gymTrainer->update($request->only([
            'role',
            'hourly_rate_cents',
            'commission_percentage',
        ]));

        $gymTrainer->load('trainer');

        return response()->json([
            'data' => new GymTrainerResource($gymTrainer),
            'message' => 'Trainer details updated successfully',
        ]);
    }

    /**
     * Terminate a trainer.
     */
    public function terminateTrainer(TerminateTrainerRequest $request, string $gymId, string $trainerId): JsonResponse
    {
        $user = $request->user();
        $gym = Gym::findOrFail($gymId);

        // Check authorization
        if ($gym->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $gymTrainer = GymTrainer::where('gym_id', $gymId)
            ->where('trainer_id', $trainerId)
            ->firstOrFail();

        $gymTrainer->update([
            'status' => GymTrainerStatus::TERMINATED,
            'terminated_at' => now(),
            'termination_reason' => $request->termination_reason,
        ]);

        $gymTrainer->load('trainer');

        return response()->json([
            'data' => new GymTrainerResource($gymTrainer),
            'message' => 'Trainer terminated successfully',
        ]);
    }
}
