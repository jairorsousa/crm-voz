<?php

namespace App\Http\Requests\CRM;

use App\Enums\CommunicationStatus;
use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCallRequest extends FormRequest
{
    use NormalizesCrmData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->normalizeNullableText($this->input('notes')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                CommunicationStatus::Completed->value,
                CommunicationStatus::NoAnswer->value,
                CommunicationStatus::Busy->value,
                CommunicationStatus::Canceled->value,
                CommunicationStatus::Failed->value,
            ])],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
