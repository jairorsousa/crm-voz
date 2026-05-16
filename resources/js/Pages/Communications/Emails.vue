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
    form.get(route('emails.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const statusVariant = (status: string) => {
    if (['sent', 'delivered'].includes(status)) return 'success';
    if (status === 'failed') return 'error';
    if (status === 'received') return 'primary';
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
    <Head title="E-mails" />

    <AuthenticatedLayout title="E-mails">
        <JrPageHeader
            title="E-mails"
            description="Envio manual, modelos comerciais, anexos e histórico vinculado à empresa."
            icon="email"
        >
            <template #actions>
                <JrButton
                    :href="route('emails.create')"
                    icon="edit_note"
                    size="sm"
                >
                    Novo e-mail
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
                    placeholder="Assunto, empresa, contato ou conteúdo"
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
                        <p class="truncate text-sm font-bold text-mono-900">
                            {{ message.subject }}
                        </p>
                        <p class="mt-1 text-sm text-mono-600">
                            Para {{ message.contact.name }} ·
                            {{ message.to_address }}
                        </p>
                    </div>
                    <JrBadge :variant="statusVariant(message.status.value)">
                        {{ message.status.label }}
                    </JrBadge>
                </div>
                <p class="mt-3 line-clamp-2 text-sm text-mono-600">
                    {{ message.body }}
                </p>
                <div
                    class="mt-4 flex flex-wrap items-center gap-3 text-xs text-mono-500"
                >
                    <Link
                        :href="route('companies.show', message.company.id)"
                        class="font-semibold text-primary-600"
                    >
                        {{ message.company.display_name }}
                    </Link>
                    <span>{{ formatDate(message.created_at) }}</span>
                    <span v-if="message.attachments_count">
                        {{ message.attachments_count }} anexo(s)
                    </span>
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
            icon="email"
            title="Nenhum e-mail registrado"
            description="Envie mensagens comerciais para alimentar o histórico da empresa."
            action-label="Novo e-mail"
            :action-href="route('emails.create')"
        />
    </AuthenticatedLayout>
</template>
