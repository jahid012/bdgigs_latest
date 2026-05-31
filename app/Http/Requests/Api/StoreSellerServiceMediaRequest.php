<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSellerServiceMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['image', 'video', 'document'])],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,mov,webm,pdf', 'max:51200'],
        ];
    }
}
