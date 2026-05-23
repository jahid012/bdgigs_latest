<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payoutMethodId' => ['required', 'integer', 'exists:seller_payout_methods,id'],
            'amount' => ['required', 'numeric', 'min:10', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
