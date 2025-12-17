<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'difficulty' => $this->difficulty->value,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
            'total_exercises' => $this->total_exercises,
            'total_sets' => $this->total_sets,
            'pricing_type' => $this->pricing_type->value,
            'status' => $this->status->value,
            'creator' => [
                'id' => $this->creator->id,
                'display_name' => $this->creator->display_name,
            ],
            'exercises' => WorkoutExerciseResource::collection($this->whenLoaded('workoutExercises')),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
