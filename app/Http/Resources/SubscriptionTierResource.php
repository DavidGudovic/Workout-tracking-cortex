<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionTierResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price_cents / 100,
            'currency' => $this->currency,
            'billing_period' => $this->billing_period->value,
            'benefits' => $this->benefits,
            'max_members' => $this->max_members,
            'includes_trainer_access' => $this->includes_trainer_access,
            'status' => $this->status->value,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
