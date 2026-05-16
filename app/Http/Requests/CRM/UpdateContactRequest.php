<?php

namespace App\Http\Requests\CRM;

use App\Enums\ContactType;
use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeNullableText($this->input('name')),
            'email' => $this->normalizeNullableEmail($this->input('email')),
            'phone' => $this->normalizeNullableDigits($this->input('phone')),
            'whatsapp' => $this->normalizeNullableDigits($this->input('whatsapp')),
            'linkedin_url' => $this->normalizeNullableText($this->input('linkedin_url')),
            'notes' => $this->normalizeNullableText($this->input('notes')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'digits_between:10,11'],
            'whatsapp' => ['nullable', 'digits_between:10,13'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'type' => ['required', Rule::enum(ContactType::class)],
            'is_primary' => ['boolean'],
            'receives_automations' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
