<?php

namespace App\Http\Requests\Api;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof Conversation
            && $this->user()
            && $conversation->participants()
                ->where('user_id', $this->user()->id)
                ->exists();
    }

    public function rules(): array
    {
        return [
            'gigId' => ['required', 'string', 'max:180'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:1', 'max:100000'],
            'deliveryDays' => ['required', 'integer', 'min:1', 'max:90'],
            'revisions' => ['required', 'string', 'max:80'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'expiresInDays' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
