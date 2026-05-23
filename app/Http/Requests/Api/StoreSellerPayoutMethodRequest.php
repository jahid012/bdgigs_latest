<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerPayoutMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:bank,mobile_wallet,manual,other'],
            'label' => ['required', 'string', 'max:120'],
            'accountHolder' => ['required', 'string', 'max:160'],
            'accountNumber' => ['required', 'string', 'max:180'],
            'routingDetails' => ['nullable', 'string', 'max:255'],
        ];
    }
}
