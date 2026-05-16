<?php

namespace App\Support\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationStatus;
use App\Enums\CompanyStatus;
use App\Enums\OpportunityStatus;
use App\Models\Activity;
use App\Models\CommunicationMessage;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DashboardMetrics
{
    private const VERSION_KEY = 'crm_dashboard_metrics_version';

    public static function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, now()->format('Uu'));
    }

    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $version = Cache::rememberForever(self::VERSION_KEY, fn (): string => now()->format('Uu'));
        $scope = $user->role?->canManage() ? 'team' : "user:{$user->id}";

        return Cache::remember(
            "crm_dashboard_metrics:{$version}:{$scope}",
            now()->addMinutes(5),
            fn (): array => $this->build($user),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function build(User $user): array
    {
        $companyQuery = $this->companiesVisibleTo($user);
        $opportunityQuery = $this->opportunitiesVisibleTo($user);
        $activityQuery = $this->activitiesVisibleTo($user);

        $openOpportunityQuery = (clone $opportunityQuery)->where('status', OpportunityStatus::Open);
        $wonOpportunityQuery = (clone $opportunityQuery)->where('status', OpportunityStatus::Won);
        $lostOpportunityQuery = (clone $opportunityQuery)->where('status', OpportunityStatus::Lost);
        $closedCount = (clone $wonOpportunityQuery)->count() + (clone $lostOpportunityQuery)->count();
        $wonCount = (clone $wonOpportunityQuery)->count();
        $conversionRate = $closedCount > 0 ? round(($wonCount / $closedCount) * 100, 1) : 0.0;

        $stalledThreshold = now()->subDays(7);

        return [
            'profile' => $this->profile($user),
            'cards' => [
                [
                    'label' => 'Empresas',
                    'value' => (string) (clone $companyQuery)->count(),
                    'helper' => $user->role?->canManage() ? 'Carteira total visível' : 'Sua carteira atribuída',
                    'icon' => 'business',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Leads novos',
                    'value' => (string) (clone $companyQuery)->where('status', CompanyStatus::NewLead)->count(),
                    'helper' => 'Empresas aguardando avanço',
                    'icon' => 'campaign',
                    'variant' => 'info',
                ],
                [
                    'label' => 'Oportunidades abertas',
                    'value' => (string) (clone $openOpportunityQuery)->count(),
                    'helper' => FormatsCrmData::money((clone $openOpportunityQuery)->sum('estimated_value')).' em negociação',
                    'icon' => 'payments',
                    'variant' => 'up',
                ],
                [
                    'label' => 'Ganhas / perdidas',
                    'value' => $wonCount.' / '.(clone $lostOpportunityQuery)->count(),
                    'helper' => 'Taxa de conversão: '.number_format($conversionRate, 1, ',', '.').'%',
                    'icon' => 'flag',
                    'variant' => 'success',
                ],
                [
                    'label' => 'Valor em negociação',
                    'value' => FormatsCrmData::money((clone $openOpportunityQuery)->sum('estimated_value')),
                    'helper' => 'Pipeline aberto',
                    'icon' => 'trending_up',
                    'variant' => 'primary',
                ],
                [
                    'label' => 'Valor ganho',
                    'value' => FormatsCrmData::money((clone $wonOpportunityQuery)->sum('closed_value')),
                    'helper' => 'Receita fechada',
                    'icon' => 'workspace_premium',
                    'variant' => 'success',
                ],
                [
                    'label' => 'Oportunidades paradas',
                    'value' => (string) (clone $openOpportunityQuery)->where('last_stage_changed_at', '<', $stalledThreshold)->count(),
                    'helper' => 'Sem avanço há mais de 7 dias',
                    'icon' => 'hourglass_bottom',
                    'variant' => 'down',
                ],
                [
                    'label' => 'Atividades vencidas',
                    'value' => (string) (clone $activityQuery)
                        ->where('status', ActivityStatus::Pending)
                        ->where('due_at', '<', now())
                        ->count(),
                    'helper' => 'Pendências críticas',
                    'icon' => 'warning',
                    'variant' => 'down',
                ],
            ],
            'pipeline' => $this->pipeline($user, $stalledThreshold),
            'productivity' => $this->productivity($user),
            'portfolio' => $this->portfolio($user),
            'todayActivities' => $this->todayActivities($user),
            'stalledOpportunities' => $this->stalledOpportunities($user, $stalledThreshold),
            'cache' => [
                'version' => $version = Cache::get(self::VERSION_KEY),
                'generated_at' => now()->toIso8601String(),
                'ttl_seconds' => 300,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function profile(User $user): array
    {
        $role = $user->role?->value ?? 'sdr';

        return [
            'role' => $role,
            'label' => $user->role?->label() ?? 'Operação',
            'title' => match ($role) {
                'admin', 'commercial_manager' => 'Visão geral da operação',
                'closer' => 'Sua mesa de negociação',
                default => 'Sua rotina comercial',
            },
            'description' => match ($role) {
                'admin', 'commercial_manager' => 'Funil, carteira e produtividade do time em um resumo executivo.',
                'closer' => 'Oportunidades em negociação, follow-ups e gargalos que precisam de ação.',
                default => 'Leads atribuídos, atividades do dia e pendências para manter o ritmo.',
            },
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pipeline(User $user, mixed $stalledThreshold): array
    {
        $pipeline = PipelineDefaults::ensureDefaultPipeline();

        return $pipeline->stages()
            ->orderBy('position')
            ->get()
            ->map(function (PipelineStage $stage) use ($user, $stalledThreshold): array {
                $query = $this->opportunitiesVisibleTo($user)
                    ->where('pipeline_stage_id', $stage->id);

                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'color' => $stage->color,
                    'is_won' => $stage->is_won,
                    'is_lost' => $stage->is_lost,
                    'total_count' => (clone $query)->count(),
                    'total_value' => (float) (clone $query)->sum('estimated_value'),
                    'formatted_total_value' => FormatsCrmData::money((clone $query)->sum('estimated_value')),
                    'stalled_count' => (clone $query)
                        ->where('status', OpportunityStatus::Open)
                        ->where('last_stage_changed_at', '<', $stalledThreshold)
                        ->count(),
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productivity(User $user): array
    {
        $users = $user->role?->canManage()
            ? User::query()->orderBy('name')->get()
            : collect([$user]);

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return $users->map(function (User $item) use ($monthStart, $monthEnd): array {
            $baseActivityQuery = Activity::query()
                ->where('assigned_to_user_id', $item->id)
                ->whereBetween('updated_at', [$monthStart, $monthEnd]);
            $baseCommunicationQuery = CommunicationMessage::query()
                ->where('user_id', $item->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            return [
                'id' => $item->id,
                'name' => $item->name,
                'role_label' => $item->role?->label() ?? 'Operação',
                'calls' => (clone $baseCommunicationQuery)->where('channel', CommunicationChannel::Call)->count(),
                'emails' => (clone $baseCommunicationQuery)->where('channel', CommunicationChannel::Email)->whereIn('status', [
                    CommunicationStatus::Sent->value,
                    CommunicationStatus::Delivered->value,
                ])->count(),
                'whatsapp' => (clone $baseCommunicationQuery)->where('channel', CommunicationChannel::Whatsapp)->whereIn('status', [
                    CommunicationStatus::Sent->value,
                    CommunicationStatus::Delivered->value,
                    CommunicationStatus::Received->value,
                ])->count(),
                'meetings' => (clone $baseActivityQuery)->where('type', ActivityType::Meeting)->count(),
                'tasks' => (clone $baseActivityQuery)->where('type', ActivityType::Task)->count(),
                'follow_ups' => (clone $baseActivityQuery)->where('type', ActivityType::FollowUp)->count(),
                'completed_activities' => (clone $baseActivityQuery)->where('status', ActivityStatus::Completed)->count(),
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function portfolio(User $user): array
    {
        $companyQuery = $this->companiesVisibleTo($user);

        return [
            'total_default_amount' => FormatsCrmData::money((clone $companyQuery)->sum('total_default_amount')),
            'average_collection_ticket' => FormatsCrmData::money((clone $companyQuery)->avg('average_collection_ticket')),
            'overdue_customers_count' => (int) (clone $companyQuery)->sum('overdue_customers_count'),
            'top_companies' => (clone $companyQuery)
                ->orderByDesc('total_default_amount')
                ->limit(5)
                ->get(['id', 'legal_name', 'trade_name', 'total_default_amount', 'average_collection_ticket', 'overdue_customers_count'])
                ->map(fn (Company $company): array => [
                    'id' => $company->id,
                    'display_name' => $company->displayName(),
                    'formatted_total_default_amount' => FormatsCrmData::money($company->total_default_amount),
                    'formatted_average_collection_ticket' => FormatsCrmData::money($company->average_collection_ticket),
                    'overdue_customers_count' => $company->overdue_customers_count ?? 0,
                ])
                ->all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function todayActivities(User $user): array
    {
        return $this->activitiesVisibleTo($user)
            ->with(['company:id,legal_name,trade_name', 'assignedTo:id,name'])
            ->where('status', ActivityStatus::Pending)
            ->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('due_at')
            ->limit(6)
            ->get()
            ->map(fn (Activity $activity): array => [
                'id' => $activity->id,
                'title' => $activity->title,
                'type_label' => $activity->type->label(),
                'priority_label' => $activity->priority->label(),
                'due_at' => $activity->due_at?->toIso8601String(),
                'company' => [
                    'id' => $activity->company->id,
                    'display_name' => $activity->company->displayName(),
                ],
                'assigned_to' => [
                    'id' => $activity->assignedTo->id,
                    'name' => $activity->assignedTo->name,
                ],
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function stalledOpportunities(User $user, mixed $stalledThreshold): array
    {
        return $this->opportunitiesVisibleTo($user)
            ->with(['company:id,legal_name,trade_name', 'stage:id,name,color'])
            ->where('status', OpportunityStatus::Open)
            ->where('last_stage_changed_at', '<', $stalledThreshold)
            ->orderBy('last_stage_changed_at')
            ->limit(6)
            ->get()
            ->map(fn (Opportunity $opportunity): array => [
                'id' => $opportunity->id,
                'title' => $opportunity->title,
                'formatted_estimated_value' => FormatsCrmData::money($opportunity->estimated_value),
                'last_stage_changed_at' => $opportunity->last_stage_changed_at?->toIso8601String(),
                'company' => [
                    'id' => $opportunity->company->id,
                    'display_name' => $opportunity->company->displayName(),
                ],
                'stage' => [
                    'id' => $opportunity->stage->id,
                    'name' => $opportunity->stage->name,
                    'color' => $opportunity->stage->color,
                ],
            ])
            ->all();
    }

    private function companiesVisibleTo(User $user): Builder
    {
        $query = Company::query();

        if ($user->role?->canManage()) {
            return $query;
        }

        return $query->where('responsible_user_id', $user->id);
    }

    private function opportunitiesVisibleTo(User $user): Builder
    {
        $query = Opportunity::query();

        if ($user->role?->canManage()) {
            return $query;
        }

        return $query->where('responsible_user_id', $user->id);
    }

    private function activitiesVisibleTo(User $user): Builder
    {
        return Activity::query()->visibleTo($user);
    }
}
