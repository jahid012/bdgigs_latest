<?php

namespace App\Http\Requests\Api;

class UpdateSellerServiceRequest extends StoreSellerServiceRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'title' => ['sometimes', 'required', 'string', 'max:160'],
        ];
    }
}
