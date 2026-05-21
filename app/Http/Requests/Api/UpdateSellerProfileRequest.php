<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSellerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:1500000'],
            'country' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:5000'],
            'languages' => ['nullable', 'array'],
            'skills' => ['nullable', 'array'],
            'projects' => ['nullable', 'array'],
            'workExperience' => ['nullable', 'array'],
            'education' => ['nullable', 'array'],
            'certification' => ['nullable', 'array'],
        ];
    }
}
