<?php

namespace App\Http\Requests\CRM;

use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWhatsappCommunicationRequest extends FormRequest
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
            'body' => $this->normalizeNullableText($this->input('body')),
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
            'communication_template_id' => ['nullable', Rule::exists('communication_templates', 'id')->where('channel', 'whatsapp')],
            'to_address' => ['required', 'string', 'min:10', 'max:20'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
