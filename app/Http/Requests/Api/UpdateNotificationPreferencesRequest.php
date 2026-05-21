<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => ['nullable', 'array'],
            'realtimeEnabled' => ['required', 'boolean'],
            'soundEnabled' => ['required', 'boolean'],
        ];
    }
}
