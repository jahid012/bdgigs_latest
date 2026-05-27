<?php

namespace App\Http\Requests\Api;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreDisputeMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
}
