<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SetLogResource extends JsonResource
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
            'set_number' => $this->set_number,
            'target_reps' => $this->target_reps,
            'actual_reps' => $this->actual_reps,
            'target_duration_seconds' => $this->target_duration_seconds,
            'actual_duration_seconds' => $this->actual_duration_seconds,
            'weight_kg' => $this->weight_kg,
            'rpe' => $this->rpe,
            'is_warmup' => $this->is_warmup,
            'is_failure' => $this->is_failure,
        ];
    }
}
