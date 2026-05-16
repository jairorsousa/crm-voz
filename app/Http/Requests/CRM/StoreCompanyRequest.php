<?php

namespace App\Http\Requests\CRM;

use App\Enums\CompanySize;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LeadTemperature;
use App\Enums\PriorityLevel;
use App\Support\CRM\NormalizesCrmData;
use App\Support\CRM\ValidatesCnpj;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'legal_name' => $this->normalizeNullableText($this->input('legal_name')),
            'trade_name' => $this->normalizeNullableText($this->input('trade_name')),
            'cnpj' => $this->normalizeNullableDigits($this->input('cnpj')),
            'phone' => $this->normalizeNullableDigits($this->input('phone')),
            'whatsapp' => $this->normalizeNullableDigits($this->input('whatsapp')),
            'email' => $this->normalizeNullableEmail($this->input('email')),
            'state' => $this->normalizeNullableState($this->input('state')),
            'site' => $this->normalizeNullableText($this->input('site')),
            'city' => $this->normalizeNullableText($this->input('city')),
            'address' => $this->normalizeNullableText($this->input('address')),
            'portfolio_notes' => $this->normalizeNullableText($this->input('portfolio_notes')),
            'pain_profile' => $this->normalizeNullableText($this->input('pain_profile')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'digits:14',
                Rule::unique('companies', 'cnpj'),
                fn (string $attribute, mixed $value, Closure $fail) => $this->validateCnpj($value, $fail),
            ],
            'segment' => ['nullable', 'string', 'max:255'],
            'site' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'digits_between:10,11'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'digits_between:10,13'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'lead_source' => ['nullable', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'average_collection_ticket' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'overdue_customers_count' => ['nullable', 'integer', 'min:0'],
            'total_default_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'approx_customers_count' => ['nullable', 'integer', 'min:0'],
            'current_system' => ['nullable', 'string', 'max:255'],
            'has_internal_collection_team' => ['nullable', 'boolean'],
            'has_erp_integration' => ['nullable', 'boolean'],
            'portfolio_notes' => ['nullable', 'string', 'max:5000'],
            'company_type' => ['nullable', Rule::enum(CompanyType::class)],
            'company_size' => ['nullable', Rule::enum(CompanySize::class)],
            'commercial_potential' => ['nullable', 'string', 'max:255'],
            'lead_temperature' => ['required', Rule::enum(LeadTemperature::class)],
            'priority' => ['required', Rule::enum(PriorityLevel::class)],
            'pain_profile' => ['nullable', 'string', 'max:255'],
            'closing_probability' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    private function validateCnpj(mixed $value, Closure $fail): void
    {
        if (! ValidatesCnpj::passes((string) $value)) {
            $fail('O CNPJ informado é inválido.');
        }
    }
}
