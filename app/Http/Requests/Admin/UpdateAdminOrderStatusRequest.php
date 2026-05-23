<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:Pending Payment Review,Payment Rejected,Pending Requirements,In Progress,Revision Requested,Delivered,Completed,Cancelled',
            ],
        ];
    }
}
