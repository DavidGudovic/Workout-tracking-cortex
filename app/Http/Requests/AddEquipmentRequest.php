<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddEquipmentRequest extends FormRequest
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
            'equipment_id' => 'required|uuid|exists:equipment,id',
            'quantity' => 'nullable|integer|min:1|max:9999',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'equipment_id.required' => 'Equipment ID is required.',
            'equipment_id.exists' => 'The specified equipment does not exist.',
        ];
    }
}
