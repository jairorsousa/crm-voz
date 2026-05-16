<?php

namespace App\Http\Controllers\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\AutomationTrigger;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\RescheduleActivityRequest;
use App\Http\Requests\CRM\StoreActivityRequest;
use App\Http\Requests\CRM\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use App\Support\CRM\AutomationEngine;
use App\Support\CRM\CrmOptions;
use App\Support\CRM\Timeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'type' => $request->string('type')->toString(),
            'priority' => $request->string('priority')->toString(),
            'assigned_to_user_id' => $request->string('assigned_to_user_id')->toString(),
            'company_id' => $request->string('company_id')->toString(),
            'period' => $request->string('period')->toString(),
        ];

        $baseQuery = Activity::query()->visibleTo($request->user());

        $activities = (clone $baseQuery)
            ->with(['company:id,legal_name,trade_name,cnpj', 'contact:id,name', 'opportunity:id,title', 'assignedTo:id,name'])
            ->search($filters['search'])
            ->when($filters['status'], fn (Builder $query, string $value) => $query->where('status', $value))
            ->when($filters['type'], fn (Builder $query, string $value) => $query->where('type', $value))
            ->when($filters['priority'], fn (Builder $query, string $value) => $query->where('priority', $value))
            ->when($filters['assigned_to_user_id'], fn (Builder $query, string $value) => $query->where('assigned_to_user_id', $value))
            ->when($filters['company_id'], fn (Builder $query, string $value) => $query->where('company_id', $value))
            ->when($filters['period'] === 'today', fn (Builder $query) => $query->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]))
            ->when($filters['period'] === 'overdue', fn (Builder $query) => $query->where('status', ActivityStatus::Pending)->where('due_at', '<', now()))
            ->orderByRaw("CASE WHEN status = 'pending' AND due_at < ? THEN 0 ELSE 1 END", [now()])
            ->orderBy('due_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Activity $activity): array => $this->activityPayload($activity));

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'summary' => $this->summary($baseQuery),
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Activities/Form', [
            'mode' => 'create',
            'activity' => null,
            'selectedCompanyId' => $request->integer('company_id') ?: null,
            'options' => $this->options(),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $activity = Activity::query()->create([
            ...$request->validated(),
            'created_by_user_id' => $request->user()?->id,
            'status' => ActivityStatus::Pending,
        ]);

        Timeline::record(
            company: $activity->company,
            type: 'activity.created',
            title: 'Atividade criada',
            description: "{$activity->title} foi criada para {$activity->assignedTo->name}.",
            contact: $activity->contact,
            user: $request->user(),
            metadata: ['activity_id' => $activity->id],
        );

        if ($activity->type === ActivityType::Meeting) {
            app(AutomationEngine::class)->handle(AutomationTrigger::MeetingScheduled, [
                'company' => $activity->company,
                'contact' => $activity->contact,
                'opportunity' => $activity->opportunity,
                'activity' => $activity,
                'user' => $request->user(),
            ], 'meeting-scheduled:activity:'.$activity->id);
        }

        return redirect()
            ->route('activities.index')
            ->with('success', 'Atividade criada com sucesso.');
    }

    public function edit(Activity $activity): Response
    {
        $this->authorizeActivity($activity);

        return Inertia::render('Activities/Form', [
            'mode' => 'edit',
            'activity' => $this->activityFormPayload($activity),
            'selectedCompanyId' => null,
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorizeActivity($activity);

        $activity->update($request->validated());

        Timeline::record(
            company: $activity->company,
            type: 'activity.updated',
            title: 'Atividade atualizada',
            description: "{$activity->title} foi atualizada.",
            contact: $activity->contact,
            user: $request->user(),
            metadata: ['activity_id' => $activity->id],
        );

        if ($activity->type === ActivityType::Meeting) {
            app(AutomationEngine::class)->handle(AutomationTrigger::MeetingScheduled, [
                'company' => $activity->company,
                'contact' => $activity->contact,
                'opportunity' => $activity->opportunity,
                'activity' => $activity,
                'user' => $request->user(),
            ], 'meeting-scheduled:activity:'.$activity->id);
        }

        return redirect()
            ->route('activities.index')
            ->with('success', 'Atividade atualizada com sucesso.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorizeActivity($activity);
        $activity->delete();

        return back()->with('success', 'Atividade removida com sucesso.');
    }

    public function complete(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorizeActivity($activity);

        $activity->update([
            'status' => ActivityStatus::Completed,
            'completed_at' => now(),
            'canceled_at' => null,
        ]);

        Timeline::record(
            company: $activity->company,
            type: 'activity.completed',
            title: 'Atividade concluída',
            description: "{$activity->title} foi concluída.",
            contact: $activity->contact,
            user: $request->user(),
            metadata: ['activity_id' => $activity->id],
        );

        return back()->with('success', 'Atividade concluída com sucesso.');
    }

    public function cancel(Request $request, Activity $activity): RedirectResponse
    {
        $this->authorizeActivity($activity);

        $activity->update([
            'status' => ActivityStatus::Canceled,
            'canceled_at' => now(),
        ]);

        Timeline::record(
            company: $activity->company,
            type: 'activity.canceled',
            title: 'Atividade cancelada',
            description: "{$activity->title} foi cancelada.",
            contact: $activity->contact,
            user: $request->user(),
            metadata: ['activity_id' => $activity->id],
        );

        return back()->with('success', 'Atividade cancelada com sucesso.');
    }

    public function reschedule(RescheduleActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorizeActivity($activity);

        $activity->update([
            'status' => ActivityStatus::Pending,
            'due_at' => $request->date('due_at'),
            'completed_at' => null,
            'canceled_at' => null,
        ]);

        Timeline::record(
            company: $activity->company,
            type: 'activity.rescheduled',
            title: 'Atividade reagendada',
            description: "{$activity->title} foi reagendada.",
            contact: $activity->contact,
            user: $request->user(),
            metadata: ['activity_id' => $activity->id],
        );

        return back()->with('success', 'Atividade reagendada com sucesso.');
    }

    private function authorizeActivity(Activity $activity): void
    {
        abort_unless(
            auth()->user()?->role?->canManage()
                || $activity->assigned_to_user_id === auth()->id()
                || $activity->created_by_user_id === auth()->id(),
            403
        );
    }

    /**
     * @return array<string, int>
     */
    private function summary(Builder $query): array
    {
        return [
            'today' => (clone $query)
                ->where('status', ActivityStatus::Pending)
                ->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])
                ->count(),
            'overdue' => (clone $query)
                ->where('status', ActivityStatus::Pending)
                ->where('due_at', '<', now())
                ->count(),
            'pending' => (clone $query)->where('status', ActivityStatus::Pending)->count(),
            'completed' => (clone $query)->where('status', ActivityStatus::Completed)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            ...CrmOptions::all(),
            'companies' => Company::query()
                ->visibleTo(request()->user())
                ->orderBy('trade_name')
                ->orderBy('legal_name')
                ->get(['id', 'legal_name', 'trade_name', 'cnpj'])
                ->map(fn (Company $company): array => [
                    'value' => $company->id,
                    'label' => $company->displayName(),
                    'description' => $company->cnpj,
                ]),
            'contacts' => Contact::query()
                ->visibleTo(request()->user())
                ->orderBy('name')
                ->get(['id', 'company_id', 'name', 'email'])
                ->map(fn (Contact $contact): array => [
                    'value' => $contact->id,
                    'label' => $contact->name,
                    'description' => $contact->email,
                    'company_id' => $contact->company_id,
                ]),
            'opportunities' => Opportunity::query()
                ->visibleTo(request()->user())
                ->orderBy('title')
                ->get(['id', 'company_id', 'title'])
                ->map(fn (Opportunity $opportunity): array => [
                    'value' => $opportunity->id,
                    'label' => $opportunity->title,
                    'company_id' => $opportunity->company_id,
                ]),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
                ->map(fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                    'description' => $user->email,
                ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activityPayload(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'description' => $activity->description,
            'type' => ['value' => $activity->type->value, 'label' => $activity->type->label()],
            'status' => ['value' => $activity->status->value, 'label' => $activity->status->label()],
            'priority' => ['value' => $activity->priority->value, 'label' => $activity->priority->label()],
            'due_at' => $activity->due_at?->toISOString(),
            'is_overdue' => $activity->isOverdue(),
            'company' => [
                'id' => $activity->company->id,
                'display_name' => $activity->company->displayName(),
            ],
            'contact' => $activity->contact ? [
                'id' => $activity->contact->id,
                'name' => $activity->contact->name,
            ] : null,
            'opportunity' => $activity->opportunity ? [
                'id' => $activity->opportunity->id,
                'title' => $activity->opportunity->title,
            ] : null,
            'assigned_to' => [
                'id' => $activity->assignedTo->id,
                'name' => $activity->assignedTo->name,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function activityFormPayload(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'company_id' => $activity->company_id,
            'contact_id' => $activity->contact_id,
            'opportunity_id' => $activity->opportunity_id,
            'assigned_to_user_id' => $activity->assigned_to_user_id,
            'type' => $activity->type->value,
            'status' => $activity->status->value,
            'priority' => $activity->priority->value,
            'title' => $activity->title,
            'description' => $activity->description,
            'due_at' => $activity->due_at?->format('Y-m-d\TH:i'),
        ];
    }
}
