<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreatorMarketplaceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('content.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:700'],
            'image' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:80'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $color = $payload['color'] ?? null;
        unset($payload['color']);

        $payload['active'] = $this->boolean('active');
        $payload['metadata'] = array_filter(['color' => $color]);

        return $payload;
    }
}
