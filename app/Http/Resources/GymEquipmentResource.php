<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GymEquipmentResource extends JsonResource
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
            'equipment_id' => $this->equipment_id,
            'quantity' => $this->quantity,
            'notes' => $this->notes,
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
