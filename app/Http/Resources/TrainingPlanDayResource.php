<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingPlanDayResource extends JsonResource
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
            'training_plan_week_id' => $this->training_plan_week_id,
            'day_number' => $this->day_number,
            'name' => $this->name,
            'is_rest_day' => $this->is_rest_day,
            'notes' => $this->notes,
            'workouts' => TrainingPlanWorkoutResource::collection($this->whenLoaded('workouts')),
        ];
    }
}
