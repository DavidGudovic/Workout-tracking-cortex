<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingPlanWeekResource extends JsonResource
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
            'training_plan_id' => $this->training_plan_id,
            'week_number' => $this->week_number,
            'name' => $this->name,
            'notes' => $this->notes,
            'days' => TrainingPlanDayResource::collection($this->whenLoaded('days')),
        ];
    }
}
