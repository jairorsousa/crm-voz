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
    CommunicationTemplateItem,
    Option,
    Paginated,
} from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    templates: Paginated<CommunicationTemplateItem>;
    filters: {
        search?: string;
        channel?: string;
        status?: string;
    };
    summary: {
        total: number;
        email: number;
        whatsapp: number;
        active: number;
    };
    options: {
        channels: Option[];
        statuses: Option[];
    };
}>();

const form = useForm({
    search: props.filters.search ?? '',
    channel: props.filters.channel ?? '',
    status: props.filters.status ?? '',
});

const submit = () => {
    form.get(route('templates.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.channel = '';
    form.status = '';
    submit();
};

const toggleTemplate = (template: CommunicationTemplateItem) => {
    router.patch(
        route('templates.toggle', template.id),
        {},
        { preserveScroll: true },
    );
};

const destroyTemplate = (template: CommunicationTemplateItem) => {
    if (!confirm(`Remover o modelo "${template.name}"?`)) {
        return;
    }

    router.delete(route('templates.destroy', template.id), {
        preserveScroll: true,
    });
};

const bodyPreview = (body: string) =>
    body.length > 130 ? `${body.slice(0, 130)}...` : body;

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Anterior')
        .replace('Next &raquo;', 'Próxima');
</script>

<template>
    <Head title="Modelos" />

    <AuthenticatedLayout title="Modelos">
        <JrPageHeader
            title="Modelos de comunicação"
            description="Crie e gerencie templates usados nos envios manuais e automações de e-mail e WhatsApp."
            icon="drafts"
        >
            <template #actions>
                <JrButton
                    :href="route('templates.create')"
                    icon="add"
                    size="sm"
                >
                    Novo modelo
                </JrButton>
            </template>
        </JrPageHeader>

        <div class="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <JrStatCard
                label="Total"
                :value="String(summary.total)"
                icon="drafts"
                variant="primary"
            />
            <JrStatCard
                label="E-mail"
                :value="String(summary.email)"
                icon="mail"
                variant="info"
            />
            <JrStatCard
                label="WhatsApp"
                :value="String(summary.whatsapp)"
                icon="chat"
                variant="success"
            />
            <JrStatCard
                label="Ativos"
                :value="String(summary.active)"
                icon="check_circle"
                variant="up"
            />
        </div>

        <JrCard class="mb-5">
            <form class="grid gap-4 lg:grid-cols-4" @submit.prevent="submit">
                <JrInput
                    v-model="form.search"
                    label="Busca"
                    icon="search"
                    placeholder="Nome, assunto ou texto"
                />
                <JrSelect
                    v-model="form.channel"
                    label="Canal"
                    icon="forum"
                    :options="options.channels"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="toggle_on"
                    :options="options.statuses"
                />
                <div class="flex items-end gap-2">
                    <JrButton type="submit" icon="filter_alt" size="sm">
                        Filtrar
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

        <JrCard>
            <JrTable v-if="templates.data.length">
                <template #head>
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500"
                        >
                            Modelo
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500"
                        >
                            Canal
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500"
                        >
                            Mensagem
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500"
                        >
                            Uso
                        </th>
                        <th
                            class="px-4 py-3 text-right text-xs font-bold uppercase text-mono-500"
                        >
                            Ações
                        </th>
                    </tr>
                </template>

                <tr v-for="template in templates.data" :key="template.id">
                    <td class="px-4 py-3 align-top">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-mono-900">
                                {{ template.name }}
                            </p>
                            <p
                                v-if="template.subject"
                                class="mt-1 text-xs text-mono-500"
                            >
                                {{ template.subject }}
                            </p>
                            <p class="mt-1 text-xs text-mono-500">
                                Atualizado em {{ formatDate(template.updated_at) }}
                            </p>
                        </div>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="flex flex-wrap gap-2">
                            <JrBadge variant="primary" size="sm">
                                {{ template.channel.label }}
                            </JrBadge>
                            <JrBadge
                                :variant="
                                    template.is_active ? 'success' : 'neutral'
                                "
                                size="sm"
                            >
                                {{ template.is_active ? 'ativo' : 'pausado' }}
                            </JrBadge>
                        </div>
                    </td>
                    <td class="max-w-xl px-4 py-3 align-top">
                        <p class="text-sm leading-relaxed text-mono-700">
                            {{ bodyPreview(template.body) }}
                        </p>
                    </td>
                    <td class="px-4 py-3 align-top text-sm text-mono-700">
                        {{ template.messages_count }} mensagens
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="flex justify-end gap-2">
                            <JrButton
                                :href="route('templates.edit', template.id)"
                                icon="edit"
                                variant="standard"
                                size="sm"
                            >
                                Editar
                            </JrButton>
                            <JrButton
                                type="button"
                                :icon="
                                    template.is_active ? 'pause' : 'play_arrow'
                                "
                                variant="mono"
                                size="sm"
                                @click="toggleTemplate(template)"
                            >
                                {{ template.is_active ? 'Pausar' : 'Ativar' }}
                            </JrButton>
                            <JrButton
                                type="button"
                                icon="delete"
                                variant="danger"
                                size="sm"
                                @click="destroyTemplate(template)"
                            >
                                Remover
                            </JrButton>
                        </div>
                    </td>
                </tr>
            </JrTable>

            <JrEmptyState
                v-else
                icon="drafts"
                title="Nenhum modelo encontrado"
                description="Crie modelos para acelerar envios de e-mail e WhatsApp."
            />

            <div
                v-if="templates.links.length > 3"
                class="mt-4 flex flex-wrap justify-end gap-2"
            >
                <Link
                    v-for="link in templates.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-pill border px-3 py-2 text-sm font-semibold"
                    :class="
                        link.active
                            ? 'border-primary-500 bg-primary-100 text-primary-600'
                            : 'border-mono-200 text-mono-600 hover:bg-mono-50'
                    "
                    preserve-scroll
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </div>
        </JrCard>
    </AuthenticatedLayout>
</template>
