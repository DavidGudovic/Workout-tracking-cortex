<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingPlanWorkoutResource extends JsonResource
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
            'training_plan_day_id' => $this->training_plan_day_id,
            'workout_id' => $this->workout_id,
            'sort_order' => $this->sort_order,
            'is_optional' => $this->is_optional,
            'workout' => new WorkoutResource($this->whenLoaded('workout')),
        ];
    }
}
