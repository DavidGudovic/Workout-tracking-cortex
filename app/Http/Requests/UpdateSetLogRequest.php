<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSetLogRequest extends FormRequest
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
            'actual_reps' => 'sometimes|integer|min:1|max:1000',
            'actual_duration_seconds' => 'sometimes|integer|min:1|max:86400',
            'actual_distance_meters' => 'sometimes|integer|min:1|max:100000',
            'weight_kg' => 'sometimes|numeric|min:0|max:5000',
            'rpe' => 'sometimes|integer|min:1|max:10',
            'is_warmup' => 'sometimes|boolean',
            'is_failure' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'rpe.min' => 'RPE must be between 1 and 10.',
            'rpe.max' => 'RPE must be between 1 and 10.',
        ];
    }
}
