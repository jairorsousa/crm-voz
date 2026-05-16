<?php

namespace App\Http\Requests\CRM;

use App\Enums\CommunicationChannel;
use App\Support\CRM\NormalizesCrmData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationChannelRequest extends FormRequest
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
            'is_active' => $this->boolean('is_active'),
            'is_shared' => $this->boolean('is_shared'),
            'is_default' => $this->boolean('is_default'),
            'user_ids' => collect($this->input('user_ids', []))->filter()->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CommunicationChannel::class)],
            'provider' => ['required', 'string', Rule::in($this->providersForType())],
            'is_active' => ['boolean'],
            'is_shared' => ['boolean'],
            'is_default' => ['boolean'],
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'config' => ['array'],
            'config.account_sid' => ['nullable', 'required_if:provider,twilio', 'string', 'max:255'],
            'config.auth_token' => ['nullable', 'string', 'max:255'],
            'config.from_number' => ['nullable', 'required_if:provider,twilio', 'string', 'max:30'],
            'config.voice_webhook_url' => ['nullable', 'required_if:provider,twilio', 'url', 'max:255'],
            'config.webhook_token' => ['nullable', 'string', 'max:255'],
            'config.url' => ['nullable', 'required_if:provider,evolution', 'url', 'max:255'],
            'config.key' => ['nullable', 'string', 'max:255'],
            'config.instance' => ['nullable', 'required_if:provider,evolution', 'string', 'max:255'],
            'config.host' => ['nullable', 'required_if:provider,smtp', 'string', 'max:255'],
            'config.port' => ['nullable', 'required_if:provider,smtp', 'integer', 'between:1,65535'],
            'config.username' => ['nullable', 'string', 'max:255'],
            'config.password' => ['nullable', 'string', 'max:255'],
            'config.encryption' => ['nullable', 'string', Rule::in(['', 'tls', 'ssl'])],
            'config.from_address' => ['nullable', 'required_if:provider,smtp', 'email', 'max:255'],
            'config.from_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return list<string>
     */
    private function providersForType(): array
    {
        return match ($this->input('type')) {
            CommunicationChannel::Call->value => ['twilio'],
            CommunicationChannel::Whatsapp->value => ['evolution'],
            CommunicationChannel::Email->value => ['smtp'],
            default => ['twilio', 'evolution', 'smtp'],
        };
    }
}
