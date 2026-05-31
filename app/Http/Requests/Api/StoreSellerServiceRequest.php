<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSellerServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:120'],
            'subcategory' => ['nullable', 'string', 'max:120'],
            'tags' => ['nullable', 'array'],
            'packages' => ['nullable', 'array'],
            'extras' => ['nullable', 'array'],
            'requirements' => ['nullable', 'array'],
            'description' => ['nullable', 'string', 'max:10000'],
            'faqs' => ['nullable', 'array'],
            'galleryImages' => ['nullable', 'array'],
            'media' => ['nullable', 'array', 'max:12'],
            'media.*.type' => ['nullable', 'string', 'in:image,video,document'],
            'media.*.url' => ['required_with:media', 'string', 'max:1500000'],
            'media.*.thumbnailUrl' => ['nullable', 'string', 'max:1500000'],
            'media.*.altText' => ['nullable', 'string', 'max:255'],
            'media.*.primary' => ['nullable', 'boolean'],
            'media.*.metadata' => ['nullable', 'array'],
        ];
    }
}
