<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GymTrainerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gym_id' => $this->gym_id,
            'trainer_id' => $this->trainer_id,
            'status' => $this->status->value,
            'role' => $this->role->value,
            'hourly_rate' => $this->hourly_rate_cents ? $this->hourly_rate_cents / 100 : null,
            'commission_percentage' => $this->commission_percentage,
            'trainer' => new TrainerProfileResource($this->whenLoaded('trainer')),
            'hired_at' => $this->hired_at?->toISOString(),
            'terminated_at' => $this->terminated_at?->toISOString(),
            'termination_reason' => $this->termination_reason,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
