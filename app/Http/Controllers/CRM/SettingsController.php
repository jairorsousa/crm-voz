<?php

namespace App\Http\Controllers\CRM;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CrmOptionValue;
use App\Models\CrmSetting;
use App\Models\PipelineStage;
use App\Models\User;
use App\Support\CRM\AuditLogger;
use App\Support\CRM\IntegrationSettings;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const OPTION_GROUPS = [
        'lost_reasons' => 'Motivos de perda',
        'lead_sources' => 'Origens',
        'segments' => 'Segmentos',
        'contact_types' => 'Tipos de contato',
    ];

    public function index(): Response
    {
        PipelineDefaults::ensureDefaultPipeline();

        return Inertia::render('Settings/Index', [
            'settings' => [
                'voz' => CrmSetting::valueFor('voz.company', [
                    'name' => 'VOZ',
                    'document' => null,
                    'site' => null,
                    'email' => null,
                    'phone' => null,
                    'address' => null,
                ]),
                'integrations' => [
                    'twilio' => $this->maskedIntegration('twilio', IntegrationSettings::twilio()),
                    'evolution' => $this->maskedIntegration('evolution', IntegrationSettings::evolution()),
                    'mail' => $this->maskedIntegration('mail', IntegrationSettings::mail()),
                ],
            ],
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => [
                        'value' => $user->role?->value,
                        'label' => $user->role?->label(),
                    ],
                ]),
            'roles' => $this->enumOptions(UserRole::cases()),
            'pipelineStages' => PipelineStage::query()
                ->orderBy('position')
                ->get(['id', 'name', 'slug', 'position', 'color', 'is_won', 'is_lost']),
            'optionGroups' => collect(self::OPTION_GROUPS)
                ->map(fn (string $label, string $key): array => [
                    'key' => $key,
                    'label' => $label,
                    'items' => CrmOptionValue::query()
                        ->where('group', $key)
                        ->orderBy('position')
                        ->orderBy('label')
                        ->get(['id', 'group', 'key', 'label', 'color', 'position', 'is_active'])
                        ->map(fn (CrmOptionValue $option): array => [
                            'id' => $option->id,
                            'group' => $option->group,
                            'key' => $option->key,
                            'label' => $option->label,
                            'color' => $option->color,
                            'position' => $option->position,
                            'is_active' => $option->is_active,
                        ])
                        ->all(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $oldValue = CrmSetting::valueFor('voz.company');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'document' => ['nullable', 'string', 'max:30'],
            'site' => ['nullable', 'url', 'max:180'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:240'],
        ]);

        $setting = CrmSetting::putValue('voz', 'voz.company', 'Dados da VOZ', $validated);

        AuditLogger::record(
            event: 'settings.general.updated',
            auditable: $setting,
            oldValues: $oldValue,
            newValues: $validated,
            description: 'Dados institucionais da VOZ atualizados.',
        );

        return back()->with('success', 'Dados da VOZ atualizados.');
    }

    public function updateIntegration(Request $request, string $integration): RedirectResponse
    {
        abort_unless(in_array($integration, ['twilio', 'evolution', 'mail'], true), 404);

        $rules = match ($integration) {
            'twilio' => [
                'account_sid' => ['nullable', 'string', 'max:180'],
                'auth_token' => ['nullable', 'string', 'max:180'],
                'api_key' => ['nullable', 'string', 'max:180'],
                'api_secret' => ['nullable', 'string', 'max:180'],
                'twiml_app_sid' => ['nullable', 'string', 'max:180'],
                'caller_id' => ['nullable', 'string', 'max:40'],
                'from_number' => ['nullable', 'string', 'max:40'],
                'voice_webhook_url' => ['nullable', 'url', 'max:240'],
                'webhook_token' => ['nullable', 'string', 'max:180'],
            ],
            'evolution' => [
                'url' => ['nullable', 'url', 'max:240'],
                'key' => ['nullable', 'string', 'max:180'],
                'instance' => ['nullable', 'string', 'max:120'],
                'webhook_token' => ['nullable', 'string', 'max:180'],
            ],
            default => [
                'mailer' => ['nullable', 'string', 'max:80'],
                'from_address' => ['nullable', 'email', 'max:160'],
                'from_name' => ['nullable', 'string', 'max:160'],
                'password' => ['nullable', 'string', 'max:180'],
            ],
        };
        $oldValue = match ($integration) {
            'twilio' => IntegrationSettings::twilio(),
            'evolution' => IntegrationSettings::evolution(),
            default => IntegrationSettings::mail(),
        };
        $validated = $this->mergeSensitiveIntegrationValues($integration, $oldValue, $request->validate($rules));

        $setting = CrmSetting::putValue('integrations', "integrations.{$integration}", ucfirst($integration), $validated);

        AuditLogger::record(
            event: 'settings.integration.updated',
            auditable: $setting,
            oldValues: $oldValue,
            newValues: $validated,
            description: "Integracao {$integration} atualizada.",
            metadata: ['integration' => $integration],
        );

        return back()->with('success', 'Integracao atualizada sem deploy.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);
        $oldRole = $user->role?->value;

        $user->update(['role' => $validated['role']]);

        AuditLogger::record(
            event: 'user.role.updated',
            auditable: $user,
            oldValues: ['role' => $oldRole],
            newValues: ['role' => $validated['role']],
            description: "Perfil de {$user->name} atualizado.",
        );

        return back()->with('success', 'Perfil do usuario atualizado.');
    }

    public function updateStage(Request $request, PipelineStage $stage): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['required', 'string', 'max:20'],
            'position' => ['required', 'integer', 'min:1', 'max:99'],
            'is_won' => ['boolean'],
            'is_lost' => ['boolean'],
        ]);
        $oldValues = $stage->only(['name', 'color', 'position', 'is_won', 'is_lost']);

        $stage->update([
            ...$validated,
            'is_won' => $request->boolean('is_won'),
            'is_lost' => $request->boolean('is_lost'),
        ]);

        AuditLogger::record(
            event: 'pipeline.stage.updated',
            auditable: $stage,
            oldValues: $oldValues,
            newValues: $stage->only(['name', 'color', 'position', 'is_won', 'is_lost']),
            description: "Etapa {$stage->name} atualizada.",
        );

        return back()->with('success', 'Etapa do pipeline atualizada.');
    }

    public function storeOption(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['required', Rule::in(array_keys(self::OPTION_GROUPS))],
            'label' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);
        $key = str($validated['label'])->ascii()->slug()->toString();

        $option = CrmOptionValue::query()->updateOrCreate([
            'group' => $validated['group'],
            'key' => $key,
        ], [
            'label' => $validated['label'],
            'color' => $validated['color'] ?? null,
            'position' => CrmOptionValue::query()->where('group', $validated['group'])->max('position') + 1,
            'is_active' => true,
        ]);

        AuditLogger::record(
            event: 'settings.option.saved',
            auditable: $option,
            newValues: $option->only(['group', 'key', 'label', 'color', 'position', 'is_active']),
            description: "Opcao {$option->label} salva.",
        );

        return back()->with('success', 'Opcao configuravel salva.');
    }

    public function updateOption(Request $request, CrmOptionValue $option): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'color' => ['nullable', 'string', 'max:20'],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);
        $oldValues = $option->only(['label', 'color', 'position', 'is_active']);

        $option->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        AuditLogger::record(
            event: 'settings.option.updated',
            auditable: $option,
            oldValues: $oldValues,
            newValues: $option->only(['label', 'color', 'position', 'is_active']),
            description: "Opcao {$option->label} atualizada.",
        );

        return back()->with('success', 'Opcao atualizada.');
    }

    /**
     * @param  array<int, object>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(array $cases): array
    {
        return array_map(fn (object $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
        ], $cases);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function maskedIntegration(string $integration, array $settings): array
    {
        foreach ($this->sensitiveFieldsFor($integration) as $field) {
            $settings[$field] = null;
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $oldValue
     * @param  array<string, mixed>  $newValue
     * @return array<string, mixed>
     */
    private function mergeSensitiveIntegrationValues(string $integration, array $oldValue, array $newValue): array
    {
        $merged = array_replace($oldValue, $newValue);

        foreach ($this->sensitiveFieldsFor($integration) as $field) {
            if (blank($newValue[$field] ?? null) && filled($oldValue[$field] ?? null)) {
                $merged[$field] = $oldValue[$field];
            }
        }

        return $merged;
    }

    /**
     * @return list<string>
     */
    private function sensitiveFieldsFor(string $integration): array
    {
        return match ($integration) {
            'twilio' => ['auth_token', 'api_secret', 'webhook_token'],
            'evolution' => ['key', 'webhook_token'],
            'mail' => ['password'],
            default => [],
        };
    }
}
