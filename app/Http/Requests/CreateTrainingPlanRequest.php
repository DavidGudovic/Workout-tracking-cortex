<?php

namespace App\Http\Requests;

use App\Shared\Enums\Difficulty;
use App\Shared\Enums\FitnessGoal;
use App\Shared\Enums\PricingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTrainingPlanRequest extends FormRequest
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
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'cover_image_url' => 'nullable|string|url|max:500',
            'goal' => ['nullable', Rule::enum(FitnessGoal::class)],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'duration_weeks' => 'required|integer|min:1|max:52',
            'days_per_week' => 'required|integer|min:1|max:7',
            'pricing_type' => ['nullable', Rule::enum(PricingType::class)],
            'price_cents' => [
                'nullable',
                'integer',
                'min:0',
                Rule::requiredIf(fn () => $this->pricing_type === PricingType::PREMIUM->value),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'price_cents.required' => 'Premium training plans must have a price.',
            'days_per_week.min' => 'Training plans must have at least 1 day per week.',
            'days_per_week.max' => 'Training plans cannot have more than 7 days per week.',
            'duration_weeks.min' => 'Training plans must be at least 1 week long.',
            'duration_weeks.max' => 'Training plans cannot be longer than 52 weeks (1 year).',
        ];
    }
}
