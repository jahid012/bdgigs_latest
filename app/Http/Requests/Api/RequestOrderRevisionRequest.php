<?php

namespace App\Http\Requests\Api;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class RequestOrderRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && $this->user()
            && (int) $order->buyer_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ];
    }
}
