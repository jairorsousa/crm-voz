<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrStatCard from '@/Components/Jr/JrStatCard.vue';
import JrTable from '@/Components/Jr/JrTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    DashboardMetricCard,
    Option,
    ReportCatalogItem,
    ReportExportItem,
    ReportTable,
} from '@/types/crm';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    filters: {
        start_date?: string;
        end_date?: string;
        user_id?: string | number;
        stage_id?: string | number;
        source?: string;
        segment?: string;
        status?: string;
    };
    reports: ReportCatalogItem[];
    overview: DashboardMetricCard[];
    previews: ReportTable[];
    options: {
        users: Option[];
        stages: Option[];
        sources: Option[];
        segments: Option[];
        statuses: Option[];
    };
    exports: ReportExportItem[];
}>();

const form = useForm({
    start_date: props.filters.start_date ?? '',
    end_date: props.filters.end_date ?? '',
    user_id: props.filters.user_id ?? '',
    stage_id: props.filters.stage_id ?? '',
    source: props.filters.source ?? '',
    segment: props.filters.segment ?? '',
    status: props.filters.status ?? '',
});

const activeFilters = computed(() =>
    Object.fromEntries(
        Object.entries(form.data()).filter(
            ([, value]) => value !== null && value !== '',
        ),
    ),
);

const applyFilters = () => {
    form.get(route('reports.index'), {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    form.start_date = '';
    form.end_date = '';
    form.user_id = '';
    form.stage_id = '';
    form.source = '';
    form.segment = '';
    form.status = '';
    applyFilters();
};

const exportHref = (report: string, format: string) =>
    route('reports.export', {
        report,
        format,
        ...activeFilters.value,
    });

const queueExport = (report: string, format: string) => {
    router.post(
        route('reports.exports.queue', { report, format }),
        activeFilters.value,
        {
            preserveScroll: true,
        },
    );
};

const formatDateTime = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Pendente';

const statusVariant = (status: string) => {
    if (status === 'completed') return 'success';
    if (status === 'failed') return 'error';
    if (status === 'processing') return 'info';

    return 'neutral';
};
</script>

<template>
    <Head title="Relatórios" />

    <AuthenticatedLayout title="Relatórios">
        <JrPageHeader
            title="Relatórios"
            description="Indicadores filtráveis, exportações e visão gerencial da operação comercial."
            icon="bar_chart"
        >
            <template #actions>
                <JrButton
                    :href="exportHref('companies', 'csv')"
                    icon="download"
                    variant="standard"
                    size="sm"
                >
                    CSV empresas
                </JrButton>
                <JrButton
                    :href="exportHref('opportunities', 'pdf')"
                    icon="picture_as_pdf"
                    size="sm"
                >
                    PDF oportunidades
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard>
            <form
                class="grid gap-4 lg:grid-cols-4 xl:grid-cols-7"
                @submit.prevent="applyFilters"
            >
                <JrInput
                    v-model="form.start_date"
                    type="date"
                    label="Início"
                    icon="event"
                    :error="form.errors.start_date"
                />
                <JrInput
                    v-model="form.end_date"
                    type="date"
                    label="Fim"
                    icon="event"
                    :error="form.errors.end_date"
                />
                <JrSelect
                    v-model="form.user_id"
                    label="Usuário"
                    icon="person"
                    :options="options.users"
                />
                <JrSelect
                    v-model="form.stage_id"
                    label="Etapa"
                    icon="alt_route"
                    :options="options.stages"
                />
                <JrSelect
                    v-model="form.source"
                    label="Origem"
                    icon="campaign"
                    :options="options.sources"
                />
                <JrSelect
                    v-model="form.segment"
                    label="Segmento"
                    icon="category"
                    :options="options.segments"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="tune"
                    :options="options.statuses"
                />

                <div class="flex items-end gap-2 lg:col-span-4 xl:col-span-7">
                    <JrButton type="submit" icon="search" size="sm">
                        Aplicar filtros
                    </JrButton>
                    <JrButton
                        type="button"
                        icon="backspace"
                        variant="standard"
                        size="sm"
                        @click="clearFilters"
                    >
                        Limpar
                    </JrButton>
                </div>
            </form>
        </JrCard>

        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <JrStatCard
                v-for="card in overview"
                :key="card.label"
                :label="card.label"
                :value="card.value"
                :helper="card.helper"
                :icon="card.icon"
                :variant="card.variant"
            />
        </div>

        <div class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-4">
                <JrCard v-for="preview in previews" :key="preview.key">
                    <div
                        class="mb-4 flex flex-wrap items-start justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="material-icons-outlined text-[20px] text-primary-500"
                                >
                                    {{ preview.icon }}
                                </span>
                                <h2 class="text-base font-bold text-mono-900">
                                    {{ preview.label }}
                                </h2>
                            </div>
                            <p class="mt-1 text-sm text-mono-600">
                                {{ preview.description }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <JrButton
                                :href="exportHref(preview.key, 'csv')"
                                icon="table_view"
                                variant="standard"
                                size="sm"
                            >
                                CSV
                            </JrButton>
                            <JrButton
                                :href="exportHref(preview.key, 'excel')"
                                icon="grid_on"
                                variant="standard"
                                size="sm"
                            >
                                Excel
                            </JrButton>
                            <JrButton
                                v-if="
                                    ['companies', 'opportunities', 'forecast'].includes(
                                        preview.key,
                                    )
                                "
                                :href="exportHref(preview.key, 'pdf')"
                                icon="picture_as_pdf"
                                variant="standard"
                                size="sm"
                            >
                                PDF
                            </JrButton>
                            <JrButton
                                type="button"
                                icon="schedule"
                                variant="mono"
                                size="sm"
                                @click="queueExport(preview.key, 'excel')"
                            >
                                Fila
                            </JrButton>
                        </div>
                    </div>

                    <JrTable v-if="preview.rows.length">
                        <template #head>
                            <tr>
                                <th
                                    v-for="label in preview.columns"
                                    :key="label"
                                    class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500"
                                >
                                    {{ label }}
                                </th>
                            </tr>
                        </template>
                        <tr v-for="(row, index) in preview.rows" :key="index">
                            <td
                                v-for="(_, key) in preview.columns"
                                :key="key"
                                class="px-4 py-3 text-sm text-mono-700"
                            >
                                {{ row[key] ?? '-' }}
                            </td>
                        </tr>
                    </JrTable>

                    <JrEmptyState
                        v-else
                        icon="query_stats"
                        title="Sem dados no filtro atual"
                        description="Ajuste o período ou remova algum filtro para ampliar a consulta."
                    />
                </JrCard>
            </div>

            <div class="space-y-4">
                <JrCard>
                    <h2 class="text-base font-bold text-mono-900">
                        Catálogo
                    </h2>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="report in reports"
                            :key="report.key"
                            class="rounded-2xl border border-mono-100 p-3"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="material-icons-outlined text-[20px] text-primary-500"
                                >
                                    {{ report.icon }}
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-mono-900">
                                        {{ report.label }}
                                    </p>
                                    <p class="mt-1 text-xs text-mono-600">
                                        {{ report.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </JrCard>

                <JrCard>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-mono-900">
                                Exportações
                            </h2>
                            <p class="mt-1 text-sm text-mono-600">
                                Arquivos recentes gerados em fila.
                            </p>
                        </div>
                        <JrBadge variant="info">{{ exports.length }}</JrBadge>
                    </div>

                    <div v-if="exports.length" class="mt-4 space-y-3">
                        <div
                            v-for="item in exports"
                            :key="item.id"
                            class="rounded-2xl border border-mono-100 p-3"
                        >
                            <div
                                class="flex items-start justify-between gap-3"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-bold text-mono-900"
                                    >
                                        {{ item.report_label }}
                                    </p>
                                    <p class="mt-1 text-xs text-mono-500">
                                        {{ item.format.toUpperCase() }} ·
                                        {{ item.rows_count }} linhas ·
                                        {{ formatDateTime(item.created_at) }}
                                    </p>
                                </div>
                                <JrBadge
                                    :variant="statusVariant(item.status.value)"
                                    size="sm"
                                >
                                    {{ item.status.label }}
                                </JrBadge>
                            </div>
                            <p
                                v-if="item.error_message"
                                class="mt-2 rounded-xl bg-down-bg p-2 text-xs font-semibold text-error"
                            >
                                {{ item.error_message }}
                            </p>
                            <JrButton
                                v-if="item.download_url"
                                :href="item.download_url"
                                icon="download"
                                variant="standard"
                                size="sm"
                                class="mt-3"
                            >
                                Baixar
                            </JrButton>
                        </div>
                    </div>
                    <JrEmptyState
                        v-else
                        class="mt-4"
                        icon="folder_zip"
                        title="Nenhuma exportação"
                        description="Use o botão Fila em um relatório para gerar arquivos maiores sem travar a tela."
                    />
                </JrCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
