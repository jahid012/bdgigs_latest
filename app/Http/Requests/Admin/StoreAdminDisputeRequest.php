<?php

namespace App\Http\Requests\Admin;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('disputes.resolve') ?? false;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'priority' => ['required', 'string', Rule::in(Dispute::PRIORITIES)],
        ];
    }
}
