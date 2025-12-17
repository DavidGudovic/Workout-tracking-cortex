<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSessionResource extends JsonResource
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
            'workout' => [
                'id' => $this->workout->id,
                'name' => $this->workout->name,
            ],
            'status' => $this->status->value,
            'started_at' => $this->started_at->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'total_duration_minutes' => $this->total_duration_minutes,
            'total_volume_kg' => $this->total_volume_kg,
            'exercise_logs' => ExerciseLogResource::collection($this->whenLoaded('exerciseLogs')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
