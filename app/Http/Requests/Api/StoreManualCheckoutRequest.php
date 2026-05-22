<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'packageId' => ['required', 'string', 'max:80'],
            'manualPaymentMethodId' => ['required', 'integer', 'exists:manual_payment_methods,id'],
            'reference' => ['required', 'string', 'max:180'],
            'proofReference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
