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
    CompanyListItem,
    CrmOptions,
    Option,
    Paginated,
} from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    companies: Paginated<CompanyListItem>;
    filters: {
        search?: string;
        status?: string;
        lead_source?: string;
        segment?: string;
        responsible_user_id?: string;
        lead_temperature?: string;
        priority?: string;
    };
    options: CrmOptions;
}>();

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    lead_source: props.filters.lead_source ?? '',
    segment: props.filters.segment ?? '',
    responsible_user_id: props.filters.responsible_user_id ?? '',
    lead_temperature: props.filters.lead_temperature ?? '',
    priority: props.filters.priority ?? '',
});

const segmentOptions = computed<Option[]>(() =>
    (props.options.segments ?? []).map((segment) => ({
        value: segment,
        label: segment,
    })),
);

const leadSourceOptions = computed<Option[]>(() =>
    (props.options.leadSources ?? []).map((source) => ({
        value: source,
        label: source,
    })),
);

const submit = () => {
    form.get(route('companies.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.status = '';
    form.lead_source = '';
    form.segment = '';
    form.responsible_user_id = '';
    form.lead_temperature = '';
    form.priority = '';
    submit();
};

const destroyCompany = (company: CompanyListItem) => {
    if (!confirm(`Remover ${company.display_name}?`)) {
        return;
    }

    router.delete(route('companies.destroy', company.id), {
        preserveScroll: true,
    });
};

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Anterior')
        .replace('Next &raquo;', 'Próxima');
</script>

<template>
    <Head title="Empresas" />

    <AuthenticatedLayout title="Empresas">
        <JrPageHeader
            title="Empresas"
            description="Cadastro central B2B com busca por empresa, CNPJ, contato, telefone e e-mail."
            icon="business"
        >
            <template #actions>
                <JrButton
                    :href="route('companies.create')"
                    icon="add"
                    size="sm"
                >
                    Nova empresa
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
                    placeholder="Razão social, CNPJ, telefone, e-mail ou contato"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="flag"
                    :options="options.companyStatuses"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.responsible_user_id"
                    label="Responsável"
                    icon="person"
                    :options="options.users ?? []"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.segment"
                    label="Segmento"
                    icon="category"
                    :options="segmentOptions"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.lead_source"
                    label="Origem"
                    icon="conversion_path"
                    :options="leadSourceOptions"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="form.lead_temperature"
                    label="Temperatura"
                    icon="local_fire_department"
                    :options="options.leadTemperatures"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="form.priority"
                    label="Prioridade"
                    icon="priority_high"
                    :options="options.priorities"
                    placeholder="Todas"
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
                        Empresa
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Status
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Responsável
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Classificação
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Contatos
                    </th>
                    <th
                        class="px-4 py-3 text-right text-xs font-semibold uppercase text-mono-600"
                    >
                        Ações
                    </th>
                </tr>
            </template>

            <tr v-for="company in companies.data" :key="company.id">
                <td class="px-4 py-4">
                    <Link
                        :href="route('companies.show', company.id)"
                        class="font-bold text-mono-900 hover:text-primary-500"
                    >
                        {{ company.display_name }}
                    </Link>
                    <p class="mt-1 text-xs text-mono-600">
                        {{ company.formatted_cnpj }}
                        <span v-if="company.city || company.state">
                            · {{ company.city }} {{ company.state }}
                        </span>
                    </p>
                </td>
                <td class="px-4 py-4">
                    <JrBadge variant="primary">{{
                        company.status.label
                    }}</JrBadge>
                </td>
                <td class="px-4 py-4 text-sm text-mono-600">
                    {{ company.responsible_user?.name ?? 'Sem responsável' }}
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-wrap gap-1.5">
                        <JrBadge variant="info" size="sm">
                            {{ company.lead_temperature.label }}
                        </JrBadge>
                        <JrBadge variant="neutral" size="sm">
                            {{ company.priority.label }}
                        </JrBadge>
                    </div>
                </td>
                <td class="px-4 py-4 text-sm font-semibold text-mono-900">
                    {{ company.contacts_count }}
                </td>
                <td class="px-4 py-4">
                    <div class="flex justify-end gap-2">
                        <JrButton
                            :href="route('companies.edit', company.id)"
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
                            @click="destroyCompany(company)"
                        >
                            Excluir
                        </JrButton>
                    </div>
                </td>
            </tr>

            <tr v-if="companies.data.length === 0">
                <td
                    colspan="6"
                    class="px-4 py-10 text-center text-sm text-mono-600"
                >
                    Nenhuma empresa encontrada.
                </td>
            </tr>
        </JrTable>

        <div class="mt-5 flex flex-wrap gap-2">
            <Link
                v-for="link in companies.links"
                :key="link.label"
                :href="link.url ?? '#'"
                class="rounded-pill px-3 py-2 text-sm font-semibold"
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
