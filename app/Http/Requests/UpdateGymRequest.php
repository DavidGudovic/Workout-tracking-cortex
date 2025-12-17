<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGymRequest extends FormRequest
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
        $gymId = $this->route('id');

        return [
            'name' => 'sometimes|string|max:150',
            'slug' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('gyms', 'slug')->ignore($gymId),
            ],
            'description' => 'nullable|string|max:5000',
            'logo_url' => 'nullable|string|url|max:500',
            'cover_image_url' => 'nullable|string|url|max:500',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'website_url' => 'nullable|string|url|max:500',
        ];
    }
}
