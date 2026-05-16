<?php

namespace App\Http\Requests\CRM;

use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCallRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'to_address' => $this->normalizeNullableDigits($this->input('to_address')),
            'notes' => $this->normalizeNullableText($this->input('notes')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->input('company_id');

        return [
            'company_id' => ['required', 'exists:companies,id'],
            'contact_id' => ['required', Rule::exists('contacts', 'id')->where('company_id', $companyId)],
            'opportunity_id' => ['nullable', Rule::exists('opportunities', 'id')->where('company_id', $companyId)],
            'communication_channel_id' => ['nullable', 'exists:communication_channels,id'],
            'to_address' => ['required', 'string', 'min:10', 'max:20'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
