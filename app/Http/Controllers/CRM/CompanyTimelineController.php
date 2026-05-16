<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Contact;
use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyTimelineController extends Controller
{
    public function __invoke(Request $request, Company $company): Response
    {
        abort_unless(Company::query()->visibleTo($request->user())->whereKey($company->id)->exists(), 403);

        $filters = [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'user_id' => $request->string('user_id')->toString(),
            'contact_id' => $request->string('contact_id')->toString(),
        ];

        $events = TimelineEvent::query()
            ->with(['user:id,name', 'contact:id,name'])
            ->where('company_id', $company->id)
            ->when($filters['search'], function (Builder $query, string $value): void {
                $term = mb_strtolower(trim($value));
                $query->where(function (Builder $query) use ($term): void {
                    $query
                        ->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$term}%"]);
                });
            })
            ->when($filters['type'], fn (Builder $query, string $value) => $query->where('type', $value))
            ->when($filters['user_id'], fn (Builder $query, string $value) => $query->where('user_id', $value))
            ->when($filters['contact_id'], fn (Builder $query, string $value) => $query->where('contact_id', $value))
            ->latest('occurred_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (TimelineEvent $event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'title' => $event->title,
                'description' => $event->description,
                'user_name' => $event->user?->name,
                'contact_name' => $event->contact?->name,
                'occurred_at' => $event->occurred_at?->toISOString(),
            ]);

        return Inertia::render('Companies/Timeline', [
            'company' => [
                'id' => $company->id,
                'display_name' => $company->displayName(),
                'legal_name' => $company->legal_name,
            ],
            'events' => $events,
            'filters' => $filters,
            'options' => [
                'types' => TimelineEvent::query()
                    ->where('company_id', $company->id)
                    ->distinct()
                    ->orderBy('type')
                    ->pluck('type')
                    ->map(fn (string $type): array => ['value' => $type, 'label' => $this->typeLabel($type)]),
                'users' => User::query()
                    ->whereIn('id', TimelineEvent::query()->where('company_id', $company->id)->whereNotNull('user_id')->select('user_id'))
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name]),
                'contacts' => Contact::query()
                    ->where('company_id', $company->id)
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Contact $contact): array => ['value' => $contact->id, 'label' => $contact->name]),
            ],
        ]);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'company.created' => 'Empresa criada',
            'company.updated' => 'Empresa atualizada',
            'contact.created' => 'Contato criado',
            'contact.updated' => 'Contato atualizado',
            'contact.deleted' => 'Contato removido',
            'opportunity.created' => 'Oportunidade criada',
            'opportunity.updated' => 'Oportunidade atualizada',
            'opportunity.stage_changed' => 'Etapa alterada',
            'activity.created' => 'Atividade criada',
            'activity.updated' => 'Atividade atualizada',
            'activity.completed' => 'Atividade concluída',
            'activity.canceled' => 'Atividade cancelada',
            'activity.rescheduled' => 'Atividade reagendada',
            default => $type,
        };
    }
}
