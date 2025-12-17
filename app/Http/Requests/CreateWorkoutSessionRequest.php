<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkoutSessionRequest extends FormRequest
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
            'workout_id' => 'required|string|exists:workouts,id',
            'training_plan_id' => 'nullable|string|exists:training_plans,id',
            'training_plan_week_number' => 'nullable|integer|min:1',
            'training_plan_day_number' => 'nullable|integer|min:1',
        ];
    }
}
