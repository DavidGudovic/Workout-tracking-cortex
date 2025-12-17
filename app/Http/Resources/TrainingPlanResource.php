<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainingPlanResource extends JsonResource
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
            'cover_image_url' => $this->cover_image_url,
            'goal' => $this->goal?->value,
            'difficulty' => $this->difficulty->value,
            'duration_weeks' => $this->duration_weeks,
            'days_per_week' => $this->days_per_week,
            'total_days' => $this->total_days,
            'pricing_type' => $this->pricing_type->value,
            'price' => $this->price_dollars,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'is_complete' => $this->isComplete(),
            'completion_percentage' => $this->getCompletionPercentage(),
            'total_workouts' => $this->getTotalWorkouts(),
            'creator' => new TrainerProfileResource($this->whenLoaded('creator')),
            'weeks' => TrainingPlanWeekResource::collection($this->whenLoaded('weeks')),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
