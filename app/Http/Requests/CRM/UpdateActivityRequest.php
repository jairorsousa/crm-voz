<?php

namespace App\Http\Requests\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\PriorityLevel;
use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
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
            'description' => $this->normalizeNullableText($this->input('description')),
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
            'contact_id' => ['nullable', Rule::exists('contacts', 'id')->where('company_id', $companyId)],
            'opportunity_id' => ['nullable', Rule::exists('opportunities', 'id')->where('company_id', $companyId)],
            'assigned_to_user_id' => ['required', 'exists:users,id'],
            'type' => ['required', Rule::enum(ActivityType::class)],
            'status' => ['required', Rule::enum(ActivityStatus::class)],
            'priority' => ['required', Rule::enum(PriorityLevel::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['required', 'date'],
        ];
    }
}
