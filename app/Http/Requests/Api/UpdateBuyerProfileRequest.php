<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuyerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'overview' => ['nullable', 'string', 'max:1000'],
            'workingDays' => ['nullable', 'array'],
            'workingDays.start' => ['nullable', 'string', 'max:40'],
            'workingDays.end' => ['nullable', 'string', 'max:40'],
            'workingHours' => ['nullable', 'array'],
            'workingHours.start' => ['nullable', 'string', 'max:40'],
            'workingHours.end' => ['nullable', 'string', 'max:40'],
            'timezone' => ['nullable', 'string', 'max:120'],
            'languages' => ['nullable', 'array'],
        ];
    }
}
