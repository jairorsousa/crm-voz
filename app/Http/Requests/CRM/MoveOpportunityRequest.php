<?php

namespace App\Http\Requests\CRM;

use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;

class MoveOpportunityRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'lost_reason' => $this->normalizeNullableText($this->input('lost_reason')),
            'movement_notes' => $this->normalizeNullableText($this->input('movement_notes')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pipeline_stage_id' => ['required', 'exists:pipeline_stages,id'],
            'lost_reason' => ['nullable', 'required_if_stage_lost:pipeline_stage_id', 'string', 'max:5000'],
            'closed_value' => ['nullable', 'required_if_stage_won:pipeline_stage_id', 'numeric', 'min:0', 'max:999999999999.99'],
            'closed_at' => ['nullable', 'required_if_stage_won:pipeline_stage_id', 'date'],
            'movement_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
