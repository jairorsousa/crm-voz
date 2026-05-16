<?php

namespace App\Http\Requests\CRM;

use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailCommunicationRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'to_address' => $this->normalizeNullableEmail($this->input('to_address')),
            'cc' => $this->normalizeNullableText($this->input('cc')),
            'bcc' => $this->normalizeNullableText($this->input('bcc')),
            'subject' => $this->normalizeNullableText($this->input('subject')),
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
            'communication_template_id' => ['nullable', Rule::exists('communication_templates', 'id')->where('channel', 'email')],
            'to_address' => ['required', 'email:rfc', 'max:255'],
            'cc' => ['nullable', 'string', 'max:1000'],
            'bcc' => ['nullable', 'string', 'max:1000'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $validated['cc'] = $this->parseEmails($validated['cc'] ?? null);
        $validated['bcc'] = $this->parseEmails($validated['bcc'] ?? null);

        return $validated;
    }

    /**
     * @return list<string>
     */
    private function parseEmails(?string $value): array
    {
        if (blank($value)) {
            return [];
        }

        return collect(preg_split('/[,;\s]+/', $value) ?: [])
            ->map(fn (string $email): string => mb_strtolower(trim($email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            ->all();
    }
}
