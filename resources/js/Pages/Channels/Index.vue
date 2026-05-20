<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTable from '@/Components/Jr/JrTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    CommunicationChannelItem,
    Option,
    Paginated,
} from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    channels: Paginated<CommunicationChannelItem>;
    filters: {
        type?: string;
        status?: string;
    };
    options: {
        types: Option[];
    };
}>();

const form = useForm({
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
});

const statusOptions: Option[] = [
    { value: 'active', label: 'Ativos' },
    { value: 'inactive', label: 'Inativos' },
];

const submit = () => {
    form.get(route('channels.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleChannel = (channel: CommunicationChannelItem) => {
    router.patch(
        route('channels.toggle', channel.id),
        {},
        { preserveScroll: true },
    );
};

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Anterior')
        .replace('Next &raquo;', 'Próxima');
</script>

<template>
    <Head title="Canais" />

    <AuthenticatedLayout title="Canais">
        <JrPageHeader
            title="Canais de comunicação"
            description="Gerencie as origens de ligação, WhatsApp e e-mail usadas pelo time comercial."
            icon="settings_input_antenna"
        >
            <template #actions>
                <JrButton :href="route('channels.create')" icon="add" size="sm">
                    Novo canal
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard class="mb-5">
            <form class="grid gap-4 md:grid-cols-3" @submit.prevent="submit">
                <JrSelect
                    v-model="form.type"
                    label="Tipo"
                    icon="forum"
                    :options="options.types"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="toggle_on"
                    :options="statusOptions"
                    placeholder="Todos"
                />
                <div class="flex items-end">
                    <JrButton type="submit" icon="filter_alt" size="sm">
                        Filtrar
                    </JrButton>
                </div>
            </form>
        </JrCard>

        <JrCard>
            <JrTable v-if="channels.data.length">
                <template #head>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500">
                            Canal
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500">
                            Acesso
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500">
                            Uso
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase text-mono-500">
                            Ações
                        </th>
                    </tr>
                </template>

                <tr v-for="channel in channels.data" :key="channel.id">
                    <td class="px-4 py-3 align-top">
                        <p class="text-sm font-bold text-mono-900">
                            {{ channel.name }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <JrBadge variant="primary" size="sm">
                                {{ channel.type.label }}
                            </JrBadge>
                            <JrBadge variant="info" size="sm">
                                {{ channel.provider.label }}
                            </JrBadge>
                            <JrBadge
                                :variant="channel.is_active ? 'success' : 'neutral'"
                                size="sm"
                            >
                                {{ channel.is_active ? 'ativo' : 'inativo' }}
                            </JrBadge>
                            <JrBadge v-if="channel.is_default" variant="up" size="sm">
                                padrão
                            </JrBadge>
                        </div>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <p class="text-sm font-semibold text-mono-900">
                            {{ channel.is_shared ? 'Compartilhado' : 'Restrito' }}
                        </p>
                        <p class="mt-1 text-xs text-mono-500">
                            {{ channel.users.length }} usuários com acesso
                        </p>
                    </td>
                    <td class="px-4 py-3 align-top text-sm text-mono-700">
                        {{ channel.messages_count }} comunicações
                    </td>
                    <td class="px-4 py-3 align-top">
                        <div class="flex justify-end gap-2">
                            <JrButton
                                :href="route('channels.edit', channel.id)"
                                icon="edit"
                                variant="standard"
                                size="sm"
                            >
                                Editar
                            </JrButton>
                            <JrButton
                                type="button"
                                :icon="channel.is_active ? 'pause' : 'play_arrow'"
                                variant="mono"
                                size="sm"
                                @click="toggleChannel(channel)"
                            >
                                {{ channel.is_active ? 'Pausar' : 'Ativar' }}
                            </JrButton>
                        </div>
                    </td>
                </tr>
            </JrTable>

            <JrEmptyState
                v-else
                icon="settings_input_antenna"
                title="Nenhum canal cadastrado"
                description="Crie um canal para liberar envio por ligação, WhatsApp ou e-mail."
                action-label="Novo canal"
                :action-href="route('channels.create')"
            />

            <div
                v-if="channels.links.length > 3"
                class="mt-4 flex flex-wrap justify-end gap-2"
            >
                <Link
                    v-for="link in channels.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    class="rounded-lg border px-3 py-2 text-sm font-semibold"
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
