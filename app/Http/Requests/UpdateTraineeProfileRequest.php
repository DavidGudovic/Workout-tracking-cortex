<?php

namespace App\Http\Requests;

use App\Shared\Enums\ExperienceLevel;
use App\Shared\Enums\FitnessGoal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTraineeProfileRequest extends FormRequest
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
            'display_name' => 'sometimes|string|max:100',
            'avatar_url' => 'nullable|string|url|max:500',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|string|in:male,female,other,prefer_not_to_say',
            'height_cm' => 'nullable|numeric|min:50|max:300',
            'weight_kg' => 'nullable|numeric|min:20|max:500',
            'fitness_goal' => ['nullable', Rule::enum(FitnessGoal::class)],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
        ];
    }
}
