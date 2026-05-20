<?php

namespace App\Http\Requests\CRM;

use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->normalizeNullableText($this->input('name'));

        $this->merge([
            'name' => $name,
            'slug' => $this->normalizeNullableText($this->input('slug')) ?: Str::slug((string) $name),
            'category' => $this->normalizeNullableText($this->input('category')),
            'description' => $this->normalizeNullableText($this->input('description')),
            'is_active' => $this->boolean('is_active'),
            'sort_order' => (int) $this->input('sort_order', 0),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('products', 'slug')],
            'category' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'base_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0', 'max:65535'],
        ];
    }
}
