<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrStatCard from '@/Components/Jr/JrStatCard.vue';
import JrTable from '@/Components/Jr/JrTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    ActivityListItem,
    CrmOptions,
    Option,
    Paginated,
} from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    activities: Paginated<ActivityListItem>;
    summary: {
        today: number;
        overdue: number;
        pending: number;
        completed: number;
    };
    filters: {
        search?: string;
        status?: string;
        type?: string;
        priority?: string;
        assigned_to_user_id?: string;
        company_id?: string;
        period?: string;
    };
    options: CrmOptions;
}>();

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
    priority: props.filters.priority ?? '',
    assigned_to_user_id: props.filters.assigned_to_user_id ?? '',
    company_id: props.filters.company_id ?? '',
    period: props.filters.period ?? '',
});

const periodOptions: Option[] = [
    { value: 'today', label: 'Hoje' },
    { value: 'overdue', label: 'Vencidas' },
];

const submit = () => {
    form.get(route('activities.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.status = '';
    form.type = '';
    form.priority = '';
    form.assigned_to_user_id = '';
    form.company_id = '';
    form.period = '';
    submit();
};

const completeActivity = (activity: ActivityListItem) => {
    router.patch(
        route('activities.complete', activity.id),
        {},
        { preserveScroll: true },
    );
};

const cancelActivity = (activity: ActivityListItem) => {
    if (!confirm(`Cancelar ${activity.title}?`)) {
        return;
    }

    router.patch(
        route('activities.cancel', activity.id),
        {},
        { preserveScroll: true },
    );
};

const destroyActivity = (activity: ActivityListItem) => {
    if (!confirm(`Remover ${activity.title}?`)) {
        return;
    }

    router.delete(route('activities.destroy', activity.id), {
        preserveScroll: true,
    });
};

const formatDate = (value: string | null) => {
    if (!value) {
        return 'Sem prazo';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Anterior')
        .replace('Next &raquo;', 'Próxima');
</script>

<template>
    <Head title="Atividades" />

    <AuthenticatedLayout title="Atividades">
        <JrPageHeader
            title="Atividades"
            description="Tarefas, reuniões e follow-ups com destaque para vencidas e pendências do dia."
            icon="calendar_today"
        >
            <template #actions>
                <JrButton
                    :href="route('activities.create')"
                    icon="add"
                    size="sm"
                >
                    Nova atividade
                </JrButton>
            </template>
        </JrPageHeader>

        <div class="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <JrStatCard
                label="Hoje"
                :value="String(summary.today)"
                icon="today"
                variant="info"
            />
            <JrStatCard
                label="Vencidas"
                :value="String(summary.overdue)"
                icon="warning"
                variant="down"
            />
            <JrStatCard
                label="Pendentes"
                :value="String(summary.pending)"
                icon="pending_actions"
                variant="primary"
            />
            <JrStatCard
                label="Concluídas"
                :value="String(summary.completed)"
                icon="check_circle"
                variant="success"
            />
        </div>

        <JrCard class="mb-5">
            <form class="grid gap-3 lg:grid-cols-4" @submit.prevent="submit">
                <JrInput
                    v-model="form.search"
                    class="lg:col-span-2"
                    icon="search"
                    label="Busca"
                    placeholder="Título, empresa, contato ou descrição"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="flag"
                    :options="options.activityStatuses"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.type"
                    label="Tipo"
                    icon="event_note"
                    :options="options.activityTypes"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.priority"
                    label="Prioridade"
                    icon="priority_high"
                    :options="options.priorities"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="form.assigned_to_user_id"
                    label="Responsável"
                    icon="person"
                    :options="options.users ?? []"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.company_id"
                    label="Empresa"
                    icon="business"
                    :options="options.companies ?? []"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="form.period"
                    label="Período"
                    icon="date_range"
                    :options="periodOptions"
                    placeholder="Todos"
                />
                <div class="flex items-end gap-2 lg:col-span-4">
                    <JrButton type="submit" icon="filter_list" size="sm"
                        >Filtrar</JrButton
                    >
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
                        Atividade
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Empresa
                    </th>
                    <th
                        class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                    >
                        Prazo
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

            <tr
                v-for="activity in activities.data"
                :key="activity.id"
                :class="activity.is_overdue ? 'bg-down-bg/40' : ''"
            >
                <td class="px-4 py-4">
                    <p class="font-bold text-mono-900">{{ activity.title }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <JrBadge variant="info" size="sm">{{
                            activity.type.label
                        }}</JrBadge>
                        <JrBadge
                            :variant="
                                activity.status.value === 'completed'
                                    ? 'success'
                                    : activity.status.value === 'canceled'
                                      ? 'error'
                                      : 'primary'
                            "
                            size="sm"
                        >
                            {{ activity.status.label }}
                        </JrBadge>
                        <JrBadge variant="neutral" size="sm">{{
                            activity.priority.label
                        }}</JrBadge>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <Link
                        :href="route('companies.show', activity.company.id)"
                        class="text-sm font-bold text-mono-900 hover:text-primary-500"
                    >
                        {{ activity.company.display_name }}
                    </Link>
                    <p
                        v-if="activity.contact"
                        class="mt-1 text-xs text-mono-600"
                    >
                        {{ activity.contact.name }}
                    </p>
                </td>
                <td
                    class="px-4 py-4 text-sm font-semibold"
                    :class="
                        activity.is_overdue ? 'text-error' : 'text-mono-600'
                    "
                >
                    {{ formatDate(activity.due_at) }}
                </td>
                <td class="px-4 py-4 text-sm text-mono-600">
                    {{ activity.assigned_to.name }}
                </td>
                <td class="px-4 py-4">
                    <div class="flex justify-end gap-2">
                        <JrButton
                            v-if="activity.status.value === 'pending'"
                            type="button"
                            variant="mono"
                            icon="check"
                            size="sm"
                            @click="completeActivity(activity)"
                        >
                            Concluir
                        </JrButton>
                        <JrButton
                            :href="route('activities.edit', activity.id)"
                            variant="standard"
                            icon="edit"
                            size="sm"
                        >
                            Editar
                        </JrButton>
                        <JrButton
                            v-if="activity.status.value === 'pending'"
                            type="button"
                            variant="standard"
                            icon="event_repeat"
                            size="sm"
                            @click="
                                router.patch(
                                    route('activities.reschedule', activity.id),
                                    {
                                        due_at: new Date(
                                            Date.now() + 86400000,
                                        ).toISOString(),
                                    },
                                    { preserveScroll: true },
                                )
                            "
                        >
                            +1 dia
                        </JrButton>
                        <JrButton
                            v-if="activity.status.value === 'pending'"
                            type="button"
                            variant="danger"
                            icon="close"
                            size="sm"
                            @click="cancelActivity(activity)"
                        >
                            Cancelar
                        </JrButton>
                        <JrButton
                            type="button"
                            variant="danger"
                            icon="delete_outline"
                            size="sm"
                            @click="destroyActivity(activity)"
                        >
                            Excluir
                        </JrButton>
                    </div>
                </td>
            </tr>

            <tr v-if="activities.data.length === 0">
                <td
                    colspan="5"
                    class="px-4 py-10 text-center text-sm text-mono-600"
                >
                    Nenhuma atividade encontrada.
                </td>
            </tr>
        </JrTable>

        <div class="mt-5 flex flex-wrap gap-2">
            <Link
                v-for="link in activities.links"
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
