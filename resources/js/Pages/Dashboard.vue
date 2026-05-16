<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrStatCard from '@/Components/Jr/JrStatCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    DashboardMetricCard,
    DashboardPipelineStage,
    DashboardPortfolio,
    DashboardProductivityUser,
    DashboardProfile,
    DashboardStalledOpportunity,
    DashboardTodayActivity,
} from '@/types/crm';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    profile: DashboardProfile;
    cards: DashboardMetricCard[];
    pipeline: DashboardPipelineStage[];
    productivity: DashboardProductivityUser[];
    portfolio: DashboardPortfolio;
    todayActivities: DashboardTodayActivity[];
    stalledOpportunities: DashboardStalledOpportunity[];
    cache: {
        version: number | string | null;
        generated_at: string;
        ttl_seconds: number;
    };
}>();

const maxStageValue = computed(() =>
    Math.max(...props.pipeline.map((stage) => stage.total_value), 1),
);

const totalPipelineCount = computed(() =>
    props.pipeline.reduce((total, stage) => total + stage.total_count, 0),
);

const stageWidth = (stage: DashboardPipelineStage) =>
    `${Math.max((stage.total_value / maxStageValue.value) * 100, stage.total_count > 0 ? 8 : 0)}%`;

const formatDateTime = (value: string | null) => {
    if (!value) {
        return 'Sem data';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

const daysSince = (value: string | null) => {
    if (!value) {
        return 'Sem movimentação registrada';
    }

    const diff = Date.now() - new Date(value).getTime();
    const days = Math.max(Math.floor(diff / 86_400_000), 0);

    return days === 1 ? '1 dia parada' : `${days} dias parada`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout title="Dashboard">
        <JrPageHeader
            :title="profile.title"
            :description="profile.description"
            icon="dashboard"
        >
            <template #actions>
                <JrButton
                    :href="route('activities.index')"
                    icon="calendar_today"
                    variant="standard"
                    size="sm"
                >
                    Atividades
                </JrButton>
                <JrButton
                    :href="route('pipeline.index')"
                    icon="view_kanban"
                    size="sm"
                >
                    Pipeline
                </JrButton>
            </template>
        </JrPageHeader>

        <div class="mb-5 flex flex-wrap items-center gap-2">
            <JrBadge variant="primary">{{ profile.label }}</JrBadge>
            <span class="text-xs text-mono-500">
                Atualizado em {{ formatDateTime(cache.generated_at) }}. Cache:
                {{ cache.ttl_seconds }}s.
            </span>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <JrStatCard
                v-for="card in cards"
                :key="card.label"
                :label="card.label"
                :value="card.value"
                :helper="card.helper"
                :icon="card.icon"
                :variant="card.variant"
            />
        </div>

        <div
            class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(360px,0.6fr)]"
        >
            <JrCard>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-mono-900">
                            Pipeline por etapa
                        </h2>
                        <p class="mt-1 text-sm text-mono-600">
                            {{ totalPipelineCount }} oportunidades distribuídas
                            no funil.
                        </p>
                    </div>
                    <JrBadge variant="info">valor estimado</JrBadge>
                </div>

                <div class="mt-6 space-y-4">
                    <div
                        v-for="stage in pipeline"
                        :key="stage.id"
                        class="grid gap-3 border-b border-mono-100 pb-4 last:border-b-0 last:pb-0 md:grid-cols-[190px_minmax(0,1fr)_130px]"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-mono-900">
                                {{ stage.name }}
                            </p>
                            <p class="mt-1 text-xs text-mono-500">
                                {{ stage.total_count }} oportunidades
                            </p>
                        </div>
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="h-2 min-w-0 flex-1 overflow-hidden rounded-pill bg-mono-100"
                            >
                                <div
                                    class="h-full rounded-pill bg-primary-500"
                                    :style="{ width: stageWidth(stage) }"
                                />
                            </div>
                            <JrBadge
                                v-if="stage.stalled_count > 0"
                                variant="down"
                                size="sm"
                            >
                                {{ stage.stalled_count }} paradas
                            </JrBadge>
                        </div>
                        <p
                            class="text-right text-sm font-bold text-mono-900 md:text-left"
                        >
                            {{ stage.formatted_total_value }}
                        </p>
                    </div>
                </div>
            </JrCard>

            <JrCard>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-mono-900">
                            Carteira prospectada
                        </h2>
                        <p class="mt-1 text-sm text-mono-600">
                            Potencial comercial das empresas visíveis.
                        </p>
                    </div>
                    <JrBadge variant="success">carteira</JrBadge>
                </div>

                <div class="mt-5 grid gap-3">
                    <div class="border-b border-mono-100 pb-3">
                        <p class="text-xs text-mono-500">Inadimplência total</p>
                        <p class="mt-1 text-xl font-bold text-mono-900">
                            {{ portfolio.total_default_amount }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-mono-500">Ticket médio</p>
                            <p class="mt-1 text-sm font-bold text-mono-900">
                                {{ portfolio.average_collection_ticket }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-mono-500">
                                Clientes inadimplentes
                            </p>
                            <p class="mt-1 text-sm font-bold text-mono-900">
                                {{ portfolio.overdue_customers_count }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    <Link
                        v-for="company in portfolio.top_companies"
                        :key="company.id"
                        :href="route('companies.show', company.id)"
                        class="block rounded-xl border border-mono-100 px-3 py-3 transition-colors hover:border-primary-200 hover:bg-primary-50"
                    >
                        <p class="truncate text-sm font-bold text-mono-900">
                            {{ company.display_name }}
                        </p>
                        <p class="mt-1 text-xs text-mono-600">
                            {{ company.formatted_total_default_amount }} em
                            carteira
                        </p>
                    </Link>
                    <JrEmptyState
                        v-if="portfolio.top_companies.length === 0"
                        icon="business"
                        title="Sem empresas na carteira"
                        description="Cadastre empresas para ativar os indicadores de potencial."
                    />
                </div>
            </JrCard>
        </div>

        <div class="mt-6 grid gap-4 xl:grid-cols-2">
            <JrCard>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-mono-900">
                            Produtividade do mês
                        </h2>
                        <p class="mt-1 text-sm text-mono-600">
                            Atividades registradas por usuário no mês corrente.
                        </p>
                    </div>
                    <JrBadge variant="neutral">mês atual</JrBadge>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="text-xs uppercase text-mono-500">
                            <tr>
                                <th class="py-2 pr-4 font-semibold">Usuário</th>
                                <th class="px-3 py-2 text-center font-semibold">
                                    Reuniões
                                </th>
                                <th class="px-3 py-2 text-center font-semibold">
                                    Tarefas
                                </th>
                                <th class="px-3 py-2 text-center font-semibold">
                                    Follow-ups
                                </th>
                                <th class="px-3 py-2 text-center font-semibold">
                                    Concluídas
                                </th>
                                <th class="px-3 py-2 text-center font-semibold">
                                    Canais
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mono-100">
                            <tr v-for="user in productivity" :key="user.id">
                                <td class="py-3 pr-4">
                                    <p class="font-bold text-mono-900">
                                        {{ user.name }}
                                    </p>
                                    <p class="text-xs text-mono-500">
                                        {{ user.role_label }}
                                    </p>
                                </td>
                                <td
                                    class="px-3 py-3 text-center font-semibold text-mono-900"
                                >
                                    {{ user.meetings }}
                                </td>
                                <td
                                    class="px-3 py-3 text-center font-semibold text-mono-900"
                                >
                                    {{ user.tasks }}
                                </td>
                                <td
                                    class="px-3 py-3 text-center font-semibold text-mono-900"
                                >
                                    {{ user.follow_ups }}
                                </td>
                                <td
                                    class="px-3 py-3 text-center font-semibold text-mono-900"
                                >
                                    {{ user.completed_activities }}
                                </td>
                                <td
                                    class="px-3 py-3 text-center text-xs text-mono-500"
                                >
                                    {{
                                        user.calls + user.emails + user.whatsapp
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </JrCard>

            <JrCard>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-mono-900">
                            Atividades de hoje
                        </h2>
                        <p class="mt-1 text-sm text-mono-600">
                            Próximos compromissos pendentes para execução.
                        </p>
                    </div>
                    <JrButton
                        :href="route('activities.index', { period: 'today' })"
                        icon="open_in_new"
                        variant="standard"
                        size="sm"
                    >
                        Ver todas
                    </JrButton>
                </div>

                <div class="mt-5 space-y-3">
                    <Link
                        v-for="activity in todayActivities"
                        :key="activity.id"
                        :href="route('activities.edit', activity.id)"
                        class="block rounded-xl border border-mono-100 px-3 py-3 transition-colors hover:border-primary-200 hover:bg-primary-50"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-bold text-mono-900"
                                >
                                    {{ activity.title }}
                                </p>
                                <p class="mt-1 truncate text-xs text-mono-600">
                                    {{ activity.company.display_name }}
                                </p>
                            </div>
                            <JrBadge variant="info" size="sm">
                                {{ activity.type_label }}
                            </JrBadge>
                        </div>
                        <p class="mt-2 text-xs text-mono-500">
                            {{ formatDateTime(activity.due_at) }} ·
                            {{ activity.priority_label }}
                        </p>
                    </Link>
                    <JrEmptyState
                        v-if="todayActivities.length === 0"
                        icon="check_circle"
                        title="Dia sem atividades pendentes"
                        description="Nada vencendo hoje dentro da sua visibilidade."
                    />
                </div>
            </JrCard>
        </div>

        <JrCard class="mt-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-mono-900">
                        Oportunidades paradas
                    </h2>
                    <p class="mt-1 text-sm text-mono-600">
                        Negociações abertas sem mudança de etapa há mais de 7
                        dias.
                    </p>
                </div>
                <JrButton
                    :href="route('pipeline.index')"
                    icon="view_kanban"
                    variant="standard"
                    size="sm"
                >
                    Abrir funil
                </JrButton>
            </div>

            <div
                v-if="stalledOpportunities.length > 0"
                class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3"
            >
                <Link
                    v-for="opportunity in stalledOpportunities"
                    :key="opportunity.id"
                    :href="route('opportunities.edit', opportunity.id)"
                    class="rounded-xl border border-mono-100 px-3 py-3 transition-colors hover:border-primary-200 hover:bg-primary-50"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p
                            class="min-w-0 truncate text-sm font-bold text-mono-900"
                        >
                            {{ opportunity.title }}
                        </p>
                        <JrBadge variant="down" size="sm">
                            {{ daysSince(opportunity.last_stage_changed_at) }}
                        </JrBadge>
                    </div>
                    <p class="mt-1 truncate text-xs text-mono-600">
                        {{ opportunity.company.display_name }}
                    </p>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <span class="text-xs text-mono-500">
                            {{ opportunity.stage.name }}
                        </span>
                        <span class="text-sm font-bold text-mono-900">
                            {{ opportunity.formatted_estimated_value }}
                        </span>
                    </div>
                </Link>
            </div>

            <JrEmptyState
                v-else
                class="mt-5"
                icon="trending_up"
                title="Nenhuma oportunidade parada"
                description="O funil visível está sem negociações antigas travadas."
            />
        </JrCard>
    </AuthenticatedLayout>
</template>
