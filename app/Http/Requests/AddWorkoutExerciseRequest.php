<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddWorkoutExerciseRequest extends FormRequest
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
            'exercise_id' => 'required|string|exists:exercises,id',
            'sort_order' => 'required|integer|min:1',
            'sets' => 'required|integer|min:1|max:20',
            'target_reps' => 'nullable|integer|min:1|max:1000',
            'target_duration_seconds' => 'nullable|integer|min:1|max:86400',
            'target_distance_meters' => 'nullable|integer|min:1|max:100000',
            'rest_seconds' => 'nullable|integer|min:0|max:600',
            'notes' => 'nullable|string|max:500',
            'superset_group' => 'nullable|integer|min:1',
            'is_optional' => 'nullable|boolean',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // At least one target must be provided
            if (!$this->target_reps && !$this->target_duration_seconds && !$this->target_distance_meters) {
                $validator->errors()->add(
                    'target',
                    'At least one target must be specified (reps, duration, or distance).'
                );
            }
        });
    }
}
