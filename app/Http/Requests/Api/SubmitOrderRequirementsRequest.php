<?php

namespace App\Http\Requests\Api;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class SubmitOrderRequirementsRequest extends FormRequest
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
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'max:20480'],
        ];
    }
}
