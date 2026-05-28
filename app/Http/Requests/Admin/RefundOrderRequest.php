<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RefundOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.release') || $this->user()?->can('orders.manage');
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function amountCents(): ?int
    {
        $amount = $this->validated('amount');

        return $amount === null ? null : (int) round(((float) $amount) * 100);
    }
}
