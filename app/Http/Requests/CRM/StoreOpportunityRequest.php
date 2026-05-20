<?php

namespace App\Http\Requests\CRM;

use App\Models\Company;
use App\Models\PipelineStage;
use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->normalizeNullableText($this->input('title')),
            'source' => $this->normalizeNullableText($this->input('source')),
            'products_interests' => $this->normalizeNullableText($this->input('products_interests')),
            'notes' => $this->normalizeNullableText($this->input('notes')),
            'lost_reason' => $this->normalizeNullableText($this->input('lost_reason')),
            'product_ids' => collect($this->input('product_ids', []))->filter()->values()->all(),
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
            'contact_id' => [
                'nullable',
                Rule::exists('contacts', 'id')->where('company_id', $companyId),
            ],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'pipeline_stage_id' => ['required', 'exists:pipeline_stages,id'],
            'title' => ['required', 'string', 'max:255'],
            'estimated_value' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:255'],
            'products_interests' => ['nullable', 'string', 'max:5000'],
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lost_reason' => ['nullable', 'required_if_stage_lost:pipeline_stage_id', 'string', 'max:5000'],
            'closed_value' => ['nullable', 'required_if_stage_won:pipeline_stage_id', 'numeric', 'min:0', 'max:999999999999.99'],
            'closed_at' => ['nullable', 'required_if_stage_won:pipeline_stage_id', 'date'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        $stage = PipelineStage::query()->find($validated['pipeline_stage_id']);
        $company = Company::query()->find($validated['company_id']);

        $validated['pipeline_id'] = $stage->pipeline_id;
        $validated['responsible_user_id'] = $validated['responsible_user_id'] ?: $company?->responsible_user_id;

        return $validated;
    }
}
