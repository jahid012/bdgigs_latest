<?php

namespace App\Http\Requests\Api;

use App\Models\Dispute;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && $this->user()
            && (
                (int) $order->buyer_id === (int) $this->user()->id
                || (int) $order->seller_id === (int) $this->user()->id
                || $this->user()->can('orders.manage')
            );
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'priority' => ['nullable', 'string', Rule::in(Dispute::PRIORITIES)],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'max:20480'],
        ];
    }
}
