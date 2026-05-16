<?php

namespace App\Http\Requests\CRM;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'due_at' => ['required', 'date'],
        ];
    }
}
