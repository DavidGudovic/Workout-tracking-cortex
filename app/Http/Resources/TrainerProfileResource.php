<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrainerProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'display_name' => $this->display_name,
            'slug' => $this->slug,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'specializations' => $this->specializations,
            'certifications' => $this->certifications,
            'years_experience' => $this->years_experience,
            'hourly_rate' => $this->hourly_rate_dollars,
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
