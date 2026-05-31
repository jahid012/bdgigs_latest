<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'targetUserId' => ['nullable', 'integer', 'exists:users,id'],
            'targetName' => ['nullable', 'string', 'max:120'],
            'targetSlug' => ['nullable', 'string', 'max:160'],
            'contextType' => ['required', 'string', Rule::in(['profile', 'gig', 'order'])],
            'contextId' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:4000'],
            'clientId' => ['nullable', 'string', 'max:120'],
        ];
    }
}
