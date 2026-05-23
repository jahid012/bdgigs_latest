<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminGigStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = in_array($this->input('action'), ['publish', 'pause'], true)
            ? 'gigs.publish'
            : 'gigs.review';

        return $this->user()?->can($permission) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:publish,pause,reject,request_edits'],
        ];
    }
}
