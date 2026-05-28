<?php

namespace App\Http\Requests\Admin;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdminDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('disputes.resolve') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(Dispute::STATUSES)],
            'priority' => ['required', 'string', Rule::in(Dispute::PRIORITIES)],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            'resolution' => ['nullable', 'string', 'max:3000', 'required_if:status,resolved,rejected,closed'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
