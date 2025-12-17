<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSetLogRequest extends FormRequest
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
            'set_number' => 'required|integer|min:1',
            'target_reps' => 'nullable|integer|min:1|max:1000',
            'actual_reps' => 'nullable|integer|min:1|max:1000',
            'target_duration_seconds' => 'nullable|integer|min:1|max:86400',
            'actual_duration_seconds' => 'nullable|integer|min:1|max:86400',
            'target_distance_meters' => 'nullable|integer|min:1|max:100000',
            'actual_distance_meters' => 'nullable|integer|min:1|max:100000',
            'weight_kg' => 'nullable|numeric|min:0|max:5000',
            'rpe' => 'nullable|integer|min:1|max:10',
            'is_warmup' => 'nullable|boolean',
            'is_failure' => 'nullable|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // At least one actual performance metric must be provided
            if (!$this->actual_reps && !$this->actual_duration_seconds && !$this->actual_distance_meters) {
                $validator->errors()->add(
                    'performance',
                    'At least one performance metric must be specified (actual_reps, actual_duration_seconds, or actual_distance_meters).'
                );
            }
        });
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
