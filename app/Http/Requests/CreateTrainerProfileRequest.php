<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTrainerProfileRequest extends FormRequest
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
            'display_name' => 'required|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'specializations' => 'nullable|array',
            'specializations.*' => 'string|max:100',
            'certifications' => 'nullable|array',
            'certifications.*' => 'string|max:100',
            'years_experience' => 'nullable|integer|min:0|max:100',
            'hourly_rate_cents' => 'nullable|integer|min:0',
        ];
    }
}
