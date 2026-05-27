<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SubmitIdentityVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legalName' => ['required', 'string', 'max:255'],
            'documentType' => ['required', 'string', 'max:120'],
            'documentReference' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:120'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }
}
