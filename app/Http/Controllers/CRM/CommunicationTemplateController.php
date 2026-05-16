<?php

namespace App\Http\Controllers\CRM;

use App\Enums\CommunicationChannel;
use App\Http\Controllers\Controller;
use App\Models\CommunicationTemplate;
use App\Support\CRM\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'channel' => $request->string('channel')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $templates = CommunicationTemplate::query()
            ->withCount('messages')
            ->whereIn('channel', [CommunicationChannel::Email, CommunicationChannel::Whatsapp])
            ->when($filters['search'], function (Builder $query, string $value): void {
                $term = mb_strtolower(trim($value));
                $query->where(function (Builder $query) use ($term): void {
                    $query
                        ->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(body) LIKE ?', ["%{$term}%"]);
                });
            })
            ->when($filters['channel'], fn (Builder $query, string $value) => $query->where('channel', $value))
            ->when($filters['status'] === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->orderByDesc('is_active')
            ->orderBy('channel')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (CommunicationTemplate $template): array => $this->payload($template));

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
            'filters' => $filters,
            'summary' => [
                'total' => CommunicationTemplate::query()->count(),
                'email' => CommunicationTemplate::query()->where('channel', CommunicationChannel::Email)->count(),
                'whatsapp' => CommunicationTemplate::query()->where('channel', CommunicationChannel::Whatsapp)->count(),
                'active' => CommunicationTemplate::query()->where('is_active', true)->count(),
            ],
            'options' => [
                'channels' => $this->channels(),
                'statuses' => [
                    ['value' => 'active', 'label' => 'Ativos'],
                    ['value' => 'inactive', 'label' => 'Inativos'],
                ],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Templates/Form', [
            'mode' => 'create',
            'template' => [
                'channel' => $request->string('channel')->toString() ?: CommunicationChannel::Email->value,
                'name' => '',
                'subject' => '',
                'body' => '',
                'is_active' => true,
            ],
            'options' => [
                'channels' => $this->channels(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTemplate($request);

        $template = CommunicationTemplate::query()->create($validated);

        AuditLogger::record(
            event: 'communication.template.created',
            auditable: $template,
            newValues: $template->only(['channel', 'name', 'subject', 'body', 'is_active']),
            description: "Modelo {$template->name} criado.",
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Modelo criado com sucesso.');
    }

    public function edit(CommunicationTemplate $template): Response
    {
        return Inertia::render('Templates/Form', [
            'mode' => 'edit',
            'template' => [
                'id' => $template->id,
                'channel' => $template->channel->value,
                'name' => $template->name,
                'subject' => $template->subject,
                'body' => $template->body,
                'is_active' => $template->is_active,
            ],
            'options' => [
                'channels' => $this->channels(),
            ],
        ]);
    }

    public function update(Request $request, CommunicationTemplate $template): RedirectResponse
    {
        $oldValues = $template->only(['channel', 'name', 'subject', 'body', 'is_active']);

        $template->update($this->validateTemplate($request, $template));

        AuditLogger::record(
            event: 'communication.template.updated',
            auditable: $template,
            oldValues: $oldValues,
            newValues: $template->only(['channel', 'name', 'subject', 'body', 'is_active']),
            description: "Modelo {$template->name} atualizado.",
        );

        return redirect()
            ->route('templates.index')
            ->with('success', 'Modelo atualizado com sucesso.');
    }

    public function toggle(CommunicationTemplate $template): RedirectResponse
    {
        $oldValues = $template->only(['is_active']);
        $template->update(['is_active' => ! $template->is_active]);

        AuditLogger::record(
            event: 'communication.template.toggled',
            auditable: $template,
            oldValues: $oldValues,
            newValues: $template->only(['is_active']),
            description: $template->is_active ? 'Modelo ativado.' : 'Modelo pausado.',
        );

        return back()->with('success', $template->is_active ? 'Modelo ativado.' : 'Modelo pausado.');
    }

    public function destroy(CommunicationTemplate $template): RedirectResponse
    {
        $oldValues = $template->only(['channel', 'name', 'subject', 'body', 'is_active']);
        $name = $template->name;

        $template->delete();

        AuditLogger::record(
            event: 'communication.template.deleted',
            oldValues: $oldValues,
            description: "Modelo {$name} removido.",
        );

        return back()->with('success', 'Modelo removido com sucesso.');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function channels(): array
    {
        return collect([CommunicationChannel::Email, CommunicationChannel::Whatsapp])
            ->map(fn (CommunicationChannel $channel): array => [
                'value' => $channel->value,
                'label' => $channel->label(),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTemplate(Request $request, ?CommunicationTemplate $template = null): array
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::enum(CommunicationChannel::class)],
            'name' => [
                'required',
                'string',
                'max:140',
                Rule::unique('communication_templates', 'name')
                    ->where('channel', $request->input('channel'))
                    ->ignore($template?->id),
            ],
            'subject' => ['nullable', 'string', 'max:180', 'required_if:channel,email'],
            'body' => ['required', 'string', 'max:4000'],
            'is_active' => ['boolean'],
        ]);

        return [
            ...$validated,
            'subject' => $validated['channel'] === CommunicationChannel::Whatsapp->value ? null : ($validated['subject'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CommunicationTemplate $template): array
    {
        return [
            'id' => $template->id,
            'channel' => [
                'value' => $template->channel->value,
                'label' => $template->channel->label(),
            ],
            'name' => $template->name,
            'subject' => $template->subject,
            'body' => $template->body,
            'is_active' => $template->is_active,
            'messages_count' => $template->messages_count,
            'created_at' => $template->created_at?->toISOString(),
            'updated_at' => $template->updated_at?->toISOString(),
        ];
    }
}
