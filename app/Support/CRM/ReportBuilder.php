<?php

namespace App\Support\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationStatus;
use App\Enums\CompanyStatus;
use App\Enums\OpportunityStatus;
use App\Enums\ReportExportStatus;
use App\Models\Activity;
use App\Models\CommunicationMessage;
use App\Models\Company;
use App\Models\CrmOptionValue;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ReportBuilder
{
    /**
     * @var array<string, array{label: string, description: string, icon: string, columns: array<string, string>}>
     */
    private const REPORTS = [
        'companies' => [
            'label' => 'Empresas cadastradas',
            'description' => 'Carteira por status, origem, segmento e responsavel.',
            'icon' => 'business',
            'columns' => [
                'empresa' => 'Empresa',
                'segmento' => 'Segmento',
                'origem' => 'Origem',
                'status' => 'Status',
                'responsavel' => 'Responsavel',
                'potencial' => 'Carteira potencial',
                'criada_em' => 'Criada em',
            ],
        ],
        'opportunities' => [
            'label' => 'Oportunidades por periodo',
            'description' => 'Oportunidades criadas no periodo filtrado.',
            'icon' => 'payments',
            'columns' => [
                'oportunidade' => 'Oportunidade',
                'empresa' => 'Empresa',
                'etapa' => 'Etapa',
                'status' => 'Status',
                'responsavel' => 'Responsavel',
                'origem' => 'Origem',
                'valor' => 'Valor',
                'previsao' => 'Previsao',
            ],
        ],
        'won_lost' => [
            'label' => 'Ganhas e perdidas',
            'description' => 'Fechamentos por periodo, usuario e etapa.',
            'icon' => 'flag',
            'columns' => [
                'oportunidade' => 'Oportunidade',
                'empresa' => 'Empresa',
                'status' => 'Status',
                'responsavel' => 'Responsavel',
                'valor' => 'Valor fechado',
                'motivo' => 'Motivo de perda',
                'fechada_em' => 'Fechada em',
            ],
        ],
        'lost_reasons' => [
            'label' => 'Motivos de perda',
            'description' => 'Principais causas registradas para perda.',
            'icon' => 'block',
            'columns' => [
                'motivo' => 'Motivo',
                'quantidade' => 'Quantidade',
                'valor_estimado' => 'Valor estimado',
            ],
        ],
        'productivity' => [
            'label' => 'Produtividade por usuario',
            'description' => 'Atividades e comunicacoes executadas por pessoa.',
            'icon' => 'groups',
            'columns' => [
                'usuario' => 'Usuario',
                'perfil' => 'Perfil',
                'ligacoes' => 'Ligacoes',
                'emails' => 'E-mails',
                'whatsapp' => 'WhatsApp',
                'reunioes' => 'Reunioes',
                'atividades_concluidas' => 'Atividades concluidas',
                'ganhas' => 'Ganhas',
            ],
        ],
        'calls' => [
            'label' => 'Ligacoes',
            'description' => 'Tentativas, conclusoes e falhas de ligacao.',
            'icon' => 'call',
            'columns' => [
                'empresa' => 'Empresa',
                'contato' => 'Contato',
                'usuario' => 'Usuario',
                'destino' => 'Destino',
                'status' => 'Status',
                'duracao' => 'Duracao',
                'data' => 'Data',
            ],
        ],
        'emails' => [
            'label' => 'E-mails',
            'description' => 'Envios por status, empresa e usuario.',
            'icon' => 'mail',
            'columns' => [
                'empresa' => 'Empresa',
                'contato' => 'Contato',
                'usuario' => 'Usuario',
                'destino' => 'Destino',
                'assunto' => 'Assunto',
                'status' => 'Status',
                'data' => 'Data',
            ],
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'description' => 'Mensagens enviadas e recebidas pela Evolution API.',
            'icon' => 'chat',
            'columns' => [
                'empresa' => 'Empresa',
                'contato' => 'Contato',
                'usuario' => 'Usuario',
                'destino' => 'Destino',
                'status' => 'Status',
                'data' => 'Data',
            ],
        ],
        'meetings' => [
            'label' => 'Reunioes',
            'description' => 'Reunioes agendadas, vencidas e concluidas.',
            'icon' => 'event',
            'columns' => [
                'titulo' => 'Titulo',
                'empresa' => 'Empresa',
                'responsavel' => 'Responsavel',
                'status' => 'Status',
                'prioridade' => 'Prioridade',
                'prazo' => 'Prazo',
            ],
        ],
        'forecast' => [
            'label' => 'Forecast',
            'description' => 'Previsao mensal das oportunidades abertas.',
            'icon' => 'trending_up',
            'columns' => [
                'periodo' => 'Periodo',
                'quantidade' => 'Quantidade',
                'valor_estimado' => 'Valor estimado',
                'valor_ponderado' => 'Valor ponderado',
            ],
        ],
        'portfolio' => [
            'label' => 'Carteira potencial',
            'description' => 'Empresas com maior valor potencial de cobranca.',
            'icon' => 'account_balance_wallet',
            'columns' => [
                'empresa' => 'Empresa',
                'segmento' => 'Segmento',
                'clientes_inadimplentes' => 'Clientes inadimplentes',
                'ticket_medio' => 'Ticket medio',
                'valor_total' => 'Valor total',
                'responsavel' => 'Responsavel',
            ],
        ],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        return collect(self::REPORTS)
            ->map(fn (array $report, string $key): array => [
                'key' => $key,
                'label' => $report['label'],
                'description' => $report['description'],
                'icon' => $report['icon'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function normalizeFilters(array $input): array
    {
        return collect([
            'start_date' => data_get($input, 'start_date'),
            'end_date' => data_get($input, 'end_date'),
            'user_id' => data_get($input, 'user_id'),
            'stage_id' => data_get($input, 'stage_id'),
            'source' => data_get($input, 'source'),
            'segment' => data_get($input, 'segment'),
            'status' => data_get($input, 'status'),
        ])
            ->reject(fn (mixed $value): bool => $value === null || $value === '')
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        $configurable = CrmOptionValue::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('label')
            ->get()
            ->groupBy('group');

        return [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'role'])
                ->map(fn (User $user): array => [
                    'value' => $user->id,
                    'label' => $user->name,
                    'description' => $user->role?->label(),
                ])
                ->all(),
            'stages' => PipelineStage::query()
                ->orderBy('position')
                ->get(['id', 'name', 'color'])
                ->map(fn (PipelineStage $stage): array => [
                    'value' => $stage->id,
                    'label' => $stage->name,
                    'color' => $stage->color,
                ])
                ->all(),
            'sources' => $this->stringOptions('lead_sources', Company::query()->distinct()->pluck('lead_source'), $configurable),
            'segments' => $this->stringOptions('segments', Company::query()->distinct()->pluck('segment'), $configurable),
            'statuses' => collect([
                ...$this->enumOptions(CompanyStatus::cases(), 'Empresa'),
                ...$this->enumOptions(OpportunityStatus::cases(), 'Oportunidade'),
                ...$this->enumOptions(ActivityStatus::cases(), 'Atividade'),
                ...$this->enumOptions(CommunicationStatus::cases(), 'Comunicacao'),
            ])->unique('value')->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(User $user, array $filters): array
    {
        $companies = $this->applyCompanyFilters($this->companiesVisibleTo($user), $filters, 'created_at');
        $opportunities = $this->applyOpportunityFilters($this->opportunitiesVisibleTo($user), $filters, 'created_at');
        $won = $this->applyOpportunityFilters($this->opportunitiesVisibleTo($user)->where('status', OpportunityStatus::Won), $filters, 'closed_at');
        $communications = $this->applyCommunicationFilters(CommunicationMessage::query()->visibleTo($user), $filters, 'created_at');
        $forecast = $this->applyOpportunityFilters($this->opportunitiesVisibleTo($user)->where('status', OpportunityStatus::Open), $filters, 'expected_close_date');

        return [
            [
                'label' => 'Empresas filtradas',
                'value' => (string) (clone $companies)->count(),
                'helper' => 'Carteira dentro dos filtros',
                'icon' => 'business',
                'variant' => 'primary',
            ],
            [
                'label' => 'Oportunidades',
                'value' => (string) (clone $opportunities)->count(),
                'helper' => FormatsCrmData::money((clone $opportunities)->sum('estimated_value')),
                'icon' => 'payments',
                'variant' => 'up',
            ],
            [
                'label' => 'Valor ganho',
                'value' => FormatsCrmData::money((clone $won)->sum('closed_value')),
                'helper' => (clone $won)->count().' oportunidades ganhas',
                'icon' => 'workspace_premium',
                'variant' => 'success',
            ],
            [
                'label' => 'Interacoes',
                'value' => (string) (clone $communications)->count(),
                'helper' => 'Ligacoes, e-mails e WhatsApp',
                'icon' => 'forum',
                'variant' => 'info',
            ],
            [
                'label' => 'Forecast aberto',
                'value' => FormatsCrmData::money((clone $forecast)->sum('estimated_value')),
                'helper' => 'Valor bruto previsto',
                'icon' => 'trending_up',
                'variant' => 'primary',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function previews(User $user, array $filters, int $limit = 6): array
    {
        return collect(array_keys(self::REPORTS))
            ->map(fn (string $report): array => $this->table($user, $report, $filters, $limit))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function table(User $user, string $report, array $filters, ?int $limit = null): array
    {
        if (! isset(self::REPORTS[$report])) {
            throw new InvalidArgumentException('Relatorio inexistente.');
        }

        $rows = match ($report) {
            'companies' => $this->companiesRows($user, $filters, $limit),
            'opportunities' => $this->opportunitiesRows($user, $filters, $limit),
            'won_lost' => $this->wonLostRows($user, $filters, $limit),
            'lost_reasons' => $this->lostReasonsRows($user, $filters, $limit),
            'productivity' => $this->productivityRows($user, $filters, $limit),
            'calls' => $this->communicationRows($user, CommunicationChannel::Call, $filters, $limit),
            'emails' => $this->communicationRows($user, CommunicationChannel::Email, $filters, $limit),
            'whatsapp' => $this->communicationRows($user, CommunicationChannel::Whatsapp, $filters, $limit),
            'meetings' => $this->meetingRows($user, $filters, $limit),
            'forecast' => $this->forecastRows($user, $filters, $limit),
            'portfolio' => $this->portfolioRows($user, $filters, $limit),
        };

        return [
            'key' => $report,
            'label' => self::REPORTS[$report]['label'],
            'description' => self::REPORTS[$report]['description'],
            'icon' => self::REPORTS[$report]['icon'],
            'columns' => self::REPORTS[$report]['columns'],
            'rows' => $rows,
            'rows_count' => count($rows),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function exportsFor(User $user): array
    {
        return ReportExport::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (ReportExport $export): array => [
                'id' => $export->id,
                'report' => $export->report,
                'report_label' => self::REPORTS[$export->report]['label'] ?? $export->report,
                'format' => $export->format,
                'status' => [
                    'value' => $export->status->value,
                    'label' => $export->status->label(),
                ],
                'file_name' => $export->file_name,
                'rows_count' => $export->rows_count,
                'error_message' => $export->error_message,
                'created_at' => $export->created_at?->toISOString(),
                'completed_at' => $export->completed_at?->toISOString(),
                'download_url' => $export->status === ReportExportStatus::Completed
                    ? route('reports.exports.download', $export)
                    : null,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $dbValues
     * @param  Collection<string, Collection<int, CrmOptionValue>>  $configurable
     * @return array<int, array{value: string, label: string}>
     */
    private function stringOptions(string $group, Collection $dbValues, Collection $configurable): array
    {
        return collect($configurable->get($group, collect()))
            ->map(fn (CrmOptionValue $option): string => $option->label)
            ->merge($dbValues)
            ->filter(fn (mixed $value): bool => filled($value))
            ->unique()
            ->sort()
            ->map(fn (string $value): array => [
                'value' => $value,
                'label' => $value,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, object>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function enumOptions(array $cases, string $prefix): array
    {
        return array_map(fn (object $case): array => [
            'value' => $case->value,
            'label' => "{$prefix}: ".$case->label(),
        ], $cases);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCompanyFilters(Builder $query, array $filters, string $dateColumn): Builder
    {
        return $this->applyDateFilter($query, $filters, $dateColumn)
            ->when($filters['user_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('responsible_user_id', $value))
            ->when($filters['source'] ?? null, fn (Builder $query, mixed $value) => $query->where('lead_source', $value))
            ->when($filters['segment'] ?? null, fn (Builder $query, mixed $value) => $query->where('segment', $value))
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $value) => $query->where('status', $value));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyOpportunityFilters(Builder $query, array $filters, string $dateColumn): Builder
    {
        return $this->applyDateFilter($query, $filters, $dateColumn)
            ->when($filters['user_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('responsible_user_id', $value))
            ->when($filters['stage_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('pipeline_stage_id', $value))
            ->when($filters['source'] ?? null, fn (Builder $query, mixed $value) => $query->where('source', $value))
            ->when($filters['segment'] ?? null, fn (Builder $query, mixed $value) => $query->whereHas('company', fn (Builder $query) => $query->where('segment', $value)))
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $value) => $query->where('status', $value));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCommunicationFilters(Builder $query, array $filters, string $dateColumn): Builder
    {
        return $this->applyDateFilter($query, $filters, $dateColumn)
            ->when($filters['user_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('user_id', $value))
            ->when($filters['segment'] ?? null, fn (Builder $query, mixed $value) => $query->whereHas('company', fn (Builder $query) => $query->where('segment', $value)))
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $value) => $query->where('status', $value));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyActivityFilters(Builder $query, array $filters, string $dateColumn): Builder
    {
        return $this->applyDateFilter($query, $filters, $dateColumn)
            ->when($filters['user_id'] ?? null, fn (Builder $query, mixed $value) => $query->where('assigned_to_user_id', $value))
            ->when($filters['segment'] ?? null, fn (Builder $query, mixed $value) => $query->whereHas('company', fn (Builder $query) => $query->where('segment', $value)))
            ->when($filters['status'] ?? null, fn (Builder $query, mixed $value) => $query->where('status', $value));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyDateFilter(Builder $query, array $filters, string $column): Builder
    {
        return $query
            ->when($filters['start_date'] ?? null, fn (Builder $query, mixed $date) => $query->whereDate($column, '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, mixed $date) => $query->whereDate($column, '<=', $date));
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

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function companiesRows(User $user, array $filters, ?int $limit): array
    {
        $query = $this->applyCompanyFilters($this->companiesVisibleTo($user), $filters, 'created_at')
            ->with('responsibleUser:id,name')
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (Company $company): array => [
            'empresa' => $company->displayName(),
            'segmento' => $company->segment ?? '-',
            'origem' => $company->lead_source ?? '-',
            'status' => $company->status->label(),
            'responsavel' => $company->responsibleUser?->name ?? '-',
            'potencial' => FormatsCrmData::money($company->total_default_amount),
            'criada_em' => $this->date($company->created_at),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function opportunitiesRows(User $user, array $filters, ?int $limit): array
    {
        $query = $this->applyOpportunityFilters($this->opportunitiesVisibleTo($user), $filters, 'created_at')
            ->with(['company:id,legal_name,trade_name', 'stage:id,name', 'responsibleUser:id,name'])
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (Opportunity $opportunity): array => [
            'oportunidade' => $opportunity->title,
            'empresa' => $opportunity->company->displayName(),
            'etapa' => $opportunity->stage->name,
            'status' => $opportunity->status->label(),
            'responsavel' => $opportunity->responsibleUser?->name ?? '-',
            'origem' => $opportunity->source ?? '-',
            'valor' => FormatsCrmData::money($opportunity->estimated_value),
            'previsao' => $this->date($opportunity->expected_close_date),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function wonLostRows(User $user, array $filters, ?int $limit): array
    {
        $query = $this->applyOpportunityFilters(
            $this->opportunitiesVisibleTo($user)->whereIn('status', [OpportunityStatus::Won, OpportunityStatus::Lost]),
            $filters,
            'closed_at',
        )
            ->with(['company:id,legal_name,trade_name', 'responsibleUser:id,name'])
            ->latest('closed_at');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (Opportunity $opportunity): array => [
            'oportunidade' => $opportunity->title,
            'empresa' => $opportunity->company->displayName(),
            'status' => $opportunity->status->label(),
            'responsavel' => $opportunity->responsibleUser?->name ?? '-',
            'valor' => FormatsCrmData::money($opportunity->closed_value ?: $opportunity->estimated_value),
            'motivo' => $opportunity->lost_reason ?? '-',
            'fechada_em' => $this->date($opportunity->closed_at),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function lostReasonsRows(User $user, array $filters, ?int $limit): array
    {
        $query = $this->applyOpportunityFilters(
            $this->opportunitiesVisibleTo($user)->where('status', OpportunityStatus::Lost),
            $filters,
            'closed_at',
        );

        $rows = $query->get(['lost_reason', 'estimated_value'])
            ->groupBy(fn (Opportunity $opportunity): string => $opportunity->lost_reason ?: 'Sem motivo informado')
            ->map(fn (Collection $items, string $reason): array => [
                'motivo' => $reason,
                'quantidade' => $items->count(),
                'valor_estimado' => FormatsCrmData::money($items->sum('estimated_value')),
            ])
            ->sortByDesc('quantidade')
            ->values();

        return ($limit ? $rows->take($limit) : $rows)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function productivityRows(User $viewer, array $filters, ?int $limit): array
    {
        $users = User::query()
            ->when($filters['user_id'] ?? null, fn (Builder $query, mixed $value) => $query->whereKey($value))
            ->orderBy('name');

        if ($limit) {
            $users->limit($limit);
        }

        return $users->get()->map(function (User $user) use ($viewer, $filters): array {
            $communicationQuery = $this->applyCommunicationFilters(
                CommunicationMessage::query()->visibleTo($viewer)->where('user_id', $user->id),
                $filters,
                'created_at',
            );
            $activityQuery = $this->applyActivityFilters(
                Activity::query()->visibleTo($viewer)->where('assigned_to_user_id', $user->id),
                $filters,
                'updated_at',
            );
            $wonQuery = $this->applyOpportunityFilters(
                $this->opportunitiesVisibleTo($viewer)->where('responsible_user_id', $user->id)->where('status', OpportunityStatus::Won),
                $filters,
                'closed_at',
            );

            return [
                'usuario' => $user->name,
                'perfil' => $user->role?->label() ?? '-',
                'ligacoes' => (clone $communicationQuery)->where('channel', CommunicationChannel::Call)->count(),
                'emails' => (clone $communicationQuery)->where('channel', CommunicationChannel::Email)->count(),
                'whatsapp' => (clone $communicationQuery)->where('channel', CommunicationChannel::Whatsapp)->count(),
                'reunioes' => (clone $activityQuery)->where('type', ActivityType::Meeting)->count(),
                'atividades_concluidas' => (clone $activityQuery)->where('status', ActivityStatus::Completed)->count(),
                'ganhas' => (clone $wonQuery)->count(),
            ];
        })->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function communicationRows(User $user, CommunicationChannel $channel, array $filters, ?int $limit): array
    {
        $query = $this->applyCommunicationFilters(
            CommunicationMessage::query()->visibleTo($user)->where('channel', $channel),
            $filters,
            'created_at',
        )
            ->with(['company:id,legal_name,trade_name', 'contact:id,name', 'user:id,name'])
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (CommunicationMessage $message): array => array_filter([
            'empresa' => $message->company->displayName(),
            'contato' => $message->contact->name,
            'usuario' => $message->user?->name ?? '-',
            'destino' => $message->to_address,
            'assunto' => $channel === CommunicationChannel::Email ? ($message->subject ?? '-') : null,
            'status' => $message->status->label(),
            'duracao' => $channel === CommunicationChannel::Call ? $this->duration($message->duration_seconds) : null,
            'data' => $this->dateTime($message->created_at),
        ], fn (mixed $value): bool => $value !== null))->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function meetingRows(User $user, array $filters, ?int $limit): array
    {
        $query = $this->applyActivityFilters(
            Activity::query()->visibleTo($user)->where('type', ActivityType::Meeting),
            $filters,
            'due_at',
        )
            ->with(['company:id,legal_name,trade_name', 'assignedTo:id,name'])
            ->orderBy('due_at');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (Activity $activity): array => [
            'titulo' => $activity->title,
            'empresa' => $activity->company->displayName(),
            'responsavel' => $activity->assignedTo->name,
            'status' => $activity->status->label(),
            'prioridade' => $activity->priority->label(),
            'prazo' => $this->dateTime($activity->due_at),
        ])->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function forecastRows(User $user, array $filters, ?int $limit): array
    {
        $query = $this->applyOpportunityFilters(
            $this->opportunitiesVisibleTo($user)
                ->where('status', OpportunityStatus::Open)
                ->whereNotNull('expected_close_date'),
            $filters,
            'expected_close_date',
        );

        $rows = $query->get(['expected_close_date', 'estimated_value', 'probability'])
            ->groupBy(fn (Opportunity $opportunity): string => $opportunity->expected_close_date?->format('Y-m') ?? 'Sem previsao')
            ->map(fn (Collection $items, string $period): array => [
                'periodo' => $period,
                'quantidade' => $items->count(),
                'valor_estimado' => FormatsCrmData::money($items->sum('estimated_value')),
                'valor_ponderado' => FormatsCrmData::money($items->sum(fn (Opportunity $item): float => ((float) $item->estimated_value) * ($item->probability / 100))),
            ])
            ->sortBy('periodo')
            ->values();

        return ($limit ? $rows->take($limit) : $rows)->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function portfolioRows(User $user, array $filters, ?int $limit): array
    {
        $query = $this->applyCompanyFilters($this->companiesVisibleTo($user), $filters, 'created_at')
            ->with('responsibleUser:id,name')
            ->orderByDesc('total_default_amount');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()->map(fn (Company $company): array => [
            'empresa' => $company->displayName(),
            'segmento' => $company->segment ?? '-',
            'clientes_inadimplentes' => $company->overdue_customers_count ?? 0,
            'ticket_medio' => FormatsCrmData::money($company->average_collection_ticket),
            'valor_total' => FormatsCrmData::money($company->total_default_amount),
            'responsavel' => $company->responsibleUser?->name ?? '-',
        ])->all();
    }

    private function date(mixed $value): string
    {
        return $value ? $value->format('d/m/Y') : '-';
    }

    private function dateTime(mixed $value): string
    {
        return $value ? $value->format('d/m/Y H:i') : '-';
    }

    private function duration(?int $seconds): string
    {
        if (! $seconds) {
            return '-';
        }

        return floor($seconds / 60).'m '.($seconds % 60).'s';
    }
}
