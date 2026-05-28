<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminGigStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = in_array($this->input('action'), ['publish', 'approve', 'pause', 'reactivate', 'deactivate'], true)
            ? 'gigs.publish'
            : 'gigs.review';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:publish,approve,pause,reject,request_edits,deactivate,reactivate'],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:action,reject,request_edits,deactivate'],
        ];
    }
}
