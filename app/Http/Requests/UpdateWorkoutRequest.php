<?php

namespace App\Http\Requests;

use App\Shared\Enums\Difficulty;
use App\Shared\Enums\PricingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:200',
            'description' => 'nullable|string|max:2000',
            'cover_image_url' => 'nullable|string|url|max:500',
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'estimated_duration_minutes' => 'nullable|integer|min:1|max:480',
            'pricing_type' => ['nullable', Rule::enum(PricingType::class)],
            'price_cents' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ];
    }
}
