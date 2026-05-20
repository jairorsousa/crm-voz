<?php

namespace App\Http\Requests\CRM;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['slug'] = [
            'required',
            'string',
            'max:180',
            Rule::unique('products', 'slug')->ignore($this->route('product')),
        ];

        return $rules;
    }
}
