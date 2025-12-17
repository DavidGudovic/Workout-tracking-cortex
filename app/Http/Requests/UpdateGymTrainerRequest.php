<?php

namespace App\Http\Requests;

use App\Shared\Enums\TrainerRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGymTrainerRequest extends FormRequest
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
            'role' => ['sometimes', Rule::enum(TrainerRole::class)],
            'hourly_rate_cents' => 'nullable|integer|min:0',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
