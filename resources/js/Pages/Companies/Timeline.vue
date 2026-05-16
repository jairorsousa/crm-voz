<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Option, Paginated, TimelineEvent } from '@/types/crm';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    company: {
        id: number;
        display_name: string;
        legal_name: string;
    };
    events: Paginated<TimelineEvent>;
    filters: {
        search?: string;
        type?: string;
        user_id?: string;
        contact_id?: string;
    };
    options: {
        types: Option[];
        users: Option[];
        contacts: Option[];
    };
}>();

const form = useForm({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    user_id: props.filters.user_id ?? '',
    contact_id: props.filters.contact_id ?? '',
});

const submit = () => {
    form.get(route('companies.timeline', props.company.id), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.type = '';
    form.user_id = '';
    form.contact_id = '';
    submit();
};

const formatDate = (value: string | null) => {
    if (!value) {
        return 'Sem data';
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
    <Head :title="`Histórico · ${company.display_name}`" />

    <AuthenticatedLayout title="Histórico">
        <JrPageHeader
            :title="`Histórico de ${company.display_name}`"
            description="Linha do tempo centralizada com eventos de empresa, contatos, oportunidades e atividades."
            icon="history"
        >
            <template #actions>
                <JrButton
                    :href="route('companies.show', company.id)"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Empresa
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
                    placeholder="Título ou descrição"
                />
                <JrSelect
                    v-model="form.type"
                    label="Tipo"
                    icon="category"
                    :options="options.types"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.user_id"
                    label="Usuário"
                    icon="person"
                    :options="options.users"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.contact_id"
                    label="Contato"
                    icon="badge"
                    :options="options.contacts"
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

        <div class="space-y-3">
            <JrCard v-for="event in events.data" :key="event.id">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-bold text-mono-900">
                                {{ event.title }}
                            </h2>
                            <JrBadge variant="info" size="sm">{{
                                event.type
                            }}</JrBadge>
                        </div>
                        <p
                            v-if="event.description"
                            class="mt-2 text-sm leading-lg text-mono-600"
                        >
                            {{ event.description }}
                        </p>
                        <p class="mt-3 text-xs text-mono-600">
                            {{ formatDate(event.occurred_at) }}
                            <span v-if="event.user_name">
                                · {{ event.user_name }}</span
                            >
                            <span v-if="event.contact_name">
                                · {{ event.contact_name }}</span
                            >
                        </p>
                    </div>
                    <span
                        class="material-icons-outlined text-[22px] text-primary-500"
                        >history</span
                    >
                </div>
            </JrCard>

            <JrCard v-if="events.data.length === 0">
                <p class="text-center text-sm text-mono-600">
                    Nenhum evento encontrado.
                </p>
            </JrCard>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <Link
                v-for="link in events.links"
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
