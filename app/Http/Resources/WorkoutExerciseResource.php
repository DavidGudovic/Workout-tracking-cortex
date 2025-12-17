<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutExerciseResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'sets' => $this->sets,
            'target_reps' => $this->target_reps,
            'target_duration_seconds' => $this->target_duration_seconds,
            'target_distance_meters' => $this->target_distance_meters,
            'rest_seconds' => $this->rest_seconds,
            'notes' => $this->notes,
            'exercise' => [
                'id' => $this->exercise->id,
                'name' => $this->exercise->name,
                'description' => $this->exercise->description,
                'primary_muscle_groups' => $this->exercise->primary_muscle_groups,
                'difficulty' => $this->exercise->difficulty->value,
                'performance_type' => $this->exercise->performance_type->value,
            ],
        ];
    }
}
