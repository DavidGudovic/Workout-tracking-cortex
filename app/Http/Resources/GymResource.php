<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GymResource extends JsonResource
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
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'cover_image_url' => $this->cover_image_url,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
            ],
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'status' => $this->status->value,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'equipment' => GymEquipmentResource::collection($this->whenLoaded('gymEquipment')),
            'trainers' => GymTrainerResource::collection($this->whenLoaded('trainerAssociations')),
            'subscription_tiers' => SubscriptionTierResource::collection($this->whenLoaded('subscriptionTiers')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
