<?php

namespace App\Http\Controllers\CRM;

use App\Enums\CommunicationChannel as CommunicationChannelType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreCommunicationChannelRequest;
use App\Http\Requests\CRM\UpdateCommunicationChannelRequest;
use App\Models\CommunicationChannel;
use App\Models\User;
use App\Support\CRM\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationChannelController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'type' => $request->string('type')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $channels = CommunicationChannel::query()
            ->with(['users:id,name,email,role'])
            ->withCount('messages')
            ->when($filters['type'], fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['status'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('type')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (CommunicationChannel $channel): array => $this->payload($channel));

        return Inertia::render('Channels/Index', [
            'channels' => $channels,
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Channels/Form', [
            'channel' => null,
            'options' => $this->options(),
        ]);
    }

    public function store(StoreCommunicationChannelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $userIds = $validated['user_ids'] ?? [];
        unset($validated['user_ids']);

        $channel = CommunicationChannel::query()->create($validated);
        $channel->users()->sync($userIds);

        AuditLogger::record('communication_channel.created', $channel, [], [
            'name' => $channel->name,
            'type' => $channel->type->value,
            'provider' => $channel->provider,
        ]);

        return redirect()
            ->route('channels.index')
            ->with('success', 'Canal criado com sucesso.');
    }

    public function edit(CommunicationChannel $channel): Response
    {
        $channel->load('users:id');

        return Inertia::render('Channels/Form', [
            'channel' => $this->formPayload($channel),
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateCommunicationChannelRequest $request, CommunicationChannel $channel): RedirectResponse
    {
        $validated = $request->validated();
        $userIds = $validated['user_ids'] ?? [];
        unset($validated['user_ids']);

        $validated['config'] = $this->preserveSensitiveConfig($channel, $validated['config'] ?? []);

        $channel->update($validated);
        $channel->users()->sync($userIds);

        AuditLogger::record('communication_channel.updated', $channel, [], [
            'name' => $channel->name,
            'type' => $channel->type->value,
            'provider' => $channel->provider,
        ]);

        return redirect()
            ->route('channels.index')
            ->with('success', 'Canal atualizado com sucesso.');
    }

    public function toggle(CommunicationChannel $channel): RedirectResponse
    {
        $channel->update(['is_active' => ! $channel->is_active]);

        AuditLogger::record('communication_channel.toggled', $channel, [], [
            'is_active' => $channel->is_active,
        ]);

        return back()->with('success', $channel->is_active ? 'Canal ativado.' : 'Canal desativado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CommunicationChannel $channel): array
    {
        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'type' => ['value' => $channel->type->value, 'label' => $channel->type->label()],
            'provider' => ['value' => $channel->provider, 'label' => $channel->providerLabel()],
            'is_active' => $channel->is_active,
            'is_shared' => $channel->is_shared,
            'is_default' => $channel->is_default,
            'messages_count' => $channel->messages_count,
            'users' => $channel->users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => ['value' => $user->role?->value, 'label' => $user->roleLabel()],
            ])->all(),
            'updated_at' => $channel->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formPayload(CommunicationChannel $channel): array
    {
        return [
            ...$this->payload($channel),
            'config' => $channel->safeConfig(),
            'user_ids' => $channel->users->pluck('id')->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'types' => collect(CommunicationChannelType::cases())->map(fn (CommunicationChannelType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->all(),
            'providers' => [
                CommunicationChannelType::Call->value => [
                    ['value' => 'twilio', 'label' => 'Twilio'],
                ],
                CommunicationChannelType::Whatsapp->value => [
                    ['value' => 'evolution', 'label' => 'Evolution API'],
                ],
                CommunicationChannelType::Email->value => [
                    ['value' => 'smtp', 'label' => 'SMTP'],
                ],
            ],
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role'])
                ->map(fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                    'description' => $user->email.' · '.($user->role?->label() ?? UserRole::Sdr->label()),
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function preserveSensitiveConfig(CommunicationChannel $channel, array $config): array
    {
        $current = $channel->config;

        foreach (['auth_token', 'api_secret', 'webhook_token', 'key', 'password'] as $field) {
            if (blank($config[$field] ?? null) && filled($current[$field] ?? null)) {
                $config[$field] = $current[$field];
            }
        }

        return $config;
    }
}
