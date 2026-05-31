<?php

namespace App\Http\Requests\Api;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

class StoreConversationMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof Conversation
            && $this->user()
            && (
                $conversation->participants()
                    ->where('user_id', $this->user()->id)
                    ->exists()
                || in_array($this->user()->id, [$conversation->buyer_id, $conversation->seller_id], true)
            );
    }

    public function rules(): array
    {
        return [
            'text' => ['nullable', 'required_without:attachments', 'string', 'max:4000'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'max:51200'],
            'clientId' => ['nullable', 'string', 'max:120'],
        ];
    }
}
