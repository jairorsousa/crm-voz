<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CommunicationListItem, CrmOptions, Paginated } from '@/types/crm';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    messages: Paginated<CommunicationListItem>;
    filters: {
        search?: string;
        status?: string;
        company_id?: string;
        contact_id?: string;
    };
    options: CrmOptions;
}>();

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    company_id: props.filters.company_id ?? '',
    contact_id: props.filters.contact_id ?? '',
});

const submit = () => {
    form.get(route('whatsapp.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const statusVariant = (status: string) => {
    if (['sent', 'delivered', 'received'].includes(status)) return 'success';
    if (status === 'failed') return 'error';
    return 'info';
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Sem registro';
</script>

<template>
    <Head title="WhatsApp" />

    <AuthenticatedLayout title="WhatsApp">
        <JrPageHeader
            title="WhatsApp"
            description="Mensagens manuais e recebidas pela Evolution API, sempre vinculadas ao contato e empresa."
            icon="chat"
        >
            <template #actions>
                <JrButton
                    :href="route('whatsapp.create')"
                    icon="send"
                    size="sm"
                >
                    Nova mensagem
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard class="mb-5">
            <form class="grid gap-3 md:grid-cols-4" @submit.prevent="submit">
                <JrInput
                    v-model="form.search"
                    class="md:col-span-2"
                    label="Busca"
                    icon="search"
                    placeholder="Contato, empresa ou conteúdo"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="flag"
                    :options="options.communicationStatuses"
                    placeholder="Todos"
                />
                <div class="flex items-end">
                    <JrButton type="submit" icon="filter_list">
                        Filtrar
                    </JrButton>
                </div>
            </form>
        </JrCard>

        <div v-if="messages.data.length" class="space-y-3">
            <JrCard v-for="message in messages.data" :key="message.id">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-mono-900">
                            {{ message.contact.name }}
                        </p>
                        <Link
                            :href="route('companies.show', message.company.id)"
                            class="mt-1 block text-sm text-mono-600 hover:text-primary-500"
                        >
                            {{ message.company.display_name }}
                        </Link>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <JrBadge variant="neutral">
                            {{ message.direction.label }}
                        </JrBadge>
                        <JrBadge :variant="statusVariant(message.status.value)">
                            {{ message.status.label }}
                        </JrBadge>
                    </div>
                </div>
                <p class="mt-3 rounded-xl bg-mono-50 p-3 text-sm text-mono-700">
                    {{ message.body }}
                </p>
                <div
                    class="mt-4 flex flex-wrap items-center gap-3 text-xs text-mono-500"
                >
                    <span>{{ message.to_address }}</span>
                    <span>{{ formatDate(message.created_at) }}</span>
                    <span v-if="message.user">{{ message.user.name }}</span>
                </div>
                <p
                    v-if="message.error_message"
                    class="mt-3 rounded-xl bg-down-bg p-3 text-sm font-semibold text-error"
                >
                    {{ message.error_message }}
                </p>
            </JrCard>
        </div>

        <JrEmptyState
            v-else
            icon="chat"
            title="Nenhuma mensagem registrada"
            description="Envie ou receba mensagens para criar a conversa do contato."
            action-label="Nova mensagem"
            :action-href="route('whatsapp.create')"
        />
    </AuthenticatedLayout>
</template>
