<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTable from '@/Components/Jr/JrTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    CrmOptions,
    OpportunityListItem,
    Option,
    Paginated,
} from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    opportunities: Paginated<OpportunityListItem>;
    filters: {
        search?: string;
        responsible_user_id?: string;
        pipeline_stage_id?: string;
        source?: string;
        status?: string;
        expected_close_from?: string;
        expected_close_to?: string;
    };
    options: CrmOptions;
}>();

const form = useForm({
    search: props.filters.search ?? '',
    responsible_user_id: props.filters.responsible_user_id ?? '',
    pipeline_stage_id: props.filters.pipeline_stage_id ?? '',
    source: props.filters.source ?? '',
    status: props.filters.status ?? '',
    expected_close_from: props.filters.expected_close_from ?? '',
    expected_close_to: props.filters.expected_close_to ?? '',
});

const sourceOptions = computed<Option[]>(() =>
    (props.options.sources ?? []).map((source) => ({
        value: source,
        label: source,
    })),
);

const submit = () => {
    form.get(route('opportunities.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.responsible_user_id = '';
    form.pipeline_stage_id = '';
    form.source = '';
    form.status = '';
    form.expected_close_from = '';
    form.expected_close_to = '';
    submit();
};

const destroyOpportunity = (opportunity: OpportunityListItem) => {
    if (!confirm(`Remover ${opportunity.title}?`)) {
        return;
    }

    router.delete(route('opportunities.destroy', opportunity.id), {
        preserveScroll: true,
    });
};

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Anterior')
        .replace('Next &raquo;', 'Próxima');
</script>

<template>
    <Head title="Oportunidades" />

    <AuthenticatedLayout title="Oportunidades">
        <JrPageHeader
            title="Oportunidades"
            description="Negócios comerciais ativos, ganhos e perdidos, com filtros por funil e previsão."
            icon="payments"
        >
            <template #actions>
                <JrButton
                    :href="route('pipeline.index')"
                    variant="standard"
                    icon="view_kanban"
                    size="sm"
                >
                    Pipeline
                </JrButton>
                <JrButton
                    :href="route('opportunities.create')"
                    icon="add"
                    size="sm"
                >
                    Nova oportunidade
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard class="mb-5">
            <form class="grid gap-3 lg:grid-cols-4" @submit.prevent="submit">
                <JrInput
                    v-model="form.search"
                    class="lg:col-span-2"
                    icon="search"
                    label="Busca"
                    placeholder="Oportunidade, empresa, contato ou origem"
                />
                <JrSelect
                    v-model="form.pipeline_stage_id"
                    label="Etapa"
                    icon="view_kanban"
                    :options="options.stages ?? []"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="form.responsible_user_id"
                    label="Responsável"
                    icon="person"
                    :options="options.users ?? []"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.source"
                    label="Origem"
                    icon="conversion_path"
                    :options="sourceOptions"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="flag"
                    :options="options.opportunityStatuses"
                    placeholder="Todos"
                />
                <JrInput
                    v-model="form.expected_close_from"
                    label="Previsão de"
                    icon="event"
                    type="date"
                />
                <JrInput
                    v-model="form.expected_close_to"
                    label="Previsão até"
                    icon="event"
                    type="date"
                />
                <div class="flex items-end gap-2 lg:col-span-4">
                    <JrButton type="submit" icon="filter_list" size="sm">
                        Filtrar
                    </JrButton>
                    <JrButton
                        type="button"
                        variant="standard"
                        icon="close"
                        size="sm"
                        @click="clearFilters"
                    >
                        Limpar
                    </JrButton>
                </div>
            </form>
        </JrCard>

        <JrTable>
            <template #head>
                <tr>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Oportunidade
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Etapa
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Valor
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Responsável
                    </th>
                    <th
                        class="px-4 py-3 text-right text-xs font-semibold uppercase text-mono-600"
                    >
                        Ações
                    </th>
                </tr>
            </template>

            <tr v-for="opportunity in opportunities.data" :key="opportunity.id">
                <td class="px-4 py-4">
                    <p class="font-bold text-mono-900">
                        {{ opportunity.title }}
                    </p>
                    <Link
                        :href="route('companies.show', opportunity.company.id)"
                        class="mt-1 block text-xs font-semibold text-primary-600"
                    >
                        {{ opportunity.company.display_name }}
                    </Link>
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-wrap gap-1.5">
                        <JrBadge variant="info" size="sm">{{
                            opportunity.stage.name
                        }}</JrBadge>
                        <JrBadge variant="neutral" size="sm">{{
                            opportunity.status.label
                        }}</JrBadge>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <p class="text-sm font-bold text-mono-900">
                        {{ opportunity.formatted_estimated_value }}
                    </p>
                    <p class="mt-1 text-xs text-mono-600">
                        {{ opportunity.probability }}% ·
                        {{ opportunity.expected_close_date ?? 'Sem previsão' }}
                    </p>
                </td>
                <td class="px-4 py-4 text-sm text-mono-600">
                    {{
                        opportunity.responsible_user?.name ?? 'Sem responsável'
                    }}
                </td>
                <td class="px-4 py-4">
                    <div class="flex justify-end gap-2">
                        <JrButton
                            :href="route('opportunities.edit', opportunity.id)"
                            variant="standard"
                            icon="edit"
                            size="sm"
                        >
                            Editar
                        </JrButton>
                        <JrButton
                            type="button"
                            variant="danger"
                            icon="delete_outline"
                            size="sm"
                            @click="destroyOpportunity(opportunity)"
                        >
                            Excluir
                        </JrButton>
                    </div>
                </td>
            </tr>

            <tr v-if="opportunities.data.length === 0">
                <td
                    colspan="5"
                    class="px-4 py-10 text-center text-sm text-mono-600"
                >
                    Nenhuma oportunidade encontrada.
                </td>
            </tr>
        </JrTable>

        <div class="mt-5 flex flex-wrap gap-2">
            <Link
                v-for="link in opportunities.links"
                :key="link.label"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-2 text-sm font-semibold"
                :class="[
                    link.active
                        ? 'bg-primary-500 text-white'
                        : 'bg-mono-white text-mono-600 hover:bg-mono-100',
                    !link.url ? 'pointer-events-none opacity-40' : '',
                ]"
                preserve-scroll
            >
                {{ paginationLabel(link.label) }}
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
