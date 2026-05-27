<?php

namespace App\Http\Requests\Admin;

use App\Models\MarketplaceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMarketplaceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('categories.manage') ?? false;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'parent_id' => ['nullable', 'integer', 'exists:marketplace_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('marketplace_categories', 'slug')->ignore($categoryId)],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:80'],
            'image' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'active' => ['sometimes', 'boolean'],
            'show_in_mega_menu' => ['sometimes', 'boolean'],
        ];
    }

    public function payload(): array
    {
        $payload = $this->validated();
        $category = $this->route('category');

        if ($category instanceof MarketplaceCategory && isset($payload['parent_id']) && (int) $payload['parent_id'] === (int) $category->id) {
            $payload['parent_id'] = null;
        }

        $payload['active'] = $this->boolean('active');
        $payload['show_in_mega_menu'] = $this->boolean('show_in_mega_menu');

        return $payload;
    }
}
