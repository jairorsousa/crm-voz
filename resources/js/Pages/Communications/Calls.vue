<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CommunicationListItem, CrmOptions, Paginated } from '@/types/crm';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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

const filterForm = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    company_id: props.filters.company_id ?? '',
    contact_id: props.filters.contact_id ?? '',
});

const callForm = useForm({
    communication_channel_id: props.options.channels?.[0]?.value ?? '',
    company_id: props.filters.company_id ?? '',
    contact_id: props.filters.contact_id ?? '',
    opportunity_id: '',
    to_address: '',
    notes: '',
});

const filteredContacts = computed(() =>
    (props.options.contacts ?? []).filter(
        (contact) =>
            !callForm.company_id ||
            Number(contact.company_id) === Number(callForm.company_id),
    ),
);

const filteredOpportunities = computed(() =>
    (props.options.opportunities ?? []).filter(
        (opportunity) =>
            !callForm.company_id ||
            Number(opportunity.company_id) === Number(callForm.company_id),
    ),
);

watch(
    () => callForm.company_id,
    () => {
        callForm.contact_id = '';
        callForm.opportunity_id = '';
        callForm.to_address = '';
    },
);

watch(
    () => callForm.contact_id,
    (value) => {
        const contact = (props.options.contacts ?? []).find(
            (item) => Number(item.value) === Number(value),
        );
        callForm.to_address = contact?.phone ?? contact?.whatsapp ?? '';
    },
    { immediate: true },
);

const submitFilters = () => {
    filterForm.get(route('calls.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const submitCall = () => {
    callForm.post(route('calls.store'), {
        preserveScroll: true,
        onSuccess: () => callForm.reset('to_address', 'notes'),
    });
};

const statusVariant = (status: string) => {
    if (['sent', 'completed', 'delivered'].includes(status)) return 'success';
    if (['failed', 'busy', 'no_answer', 'canceled'].includes(status))
        return 'error';
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
    <Head title="Ligações" />

    <AuthenticatedLayout title="Ligações">
        <JrPageHeader
            title="Ligações"
            description="Tentativas via Twilio, status da chamada e anotações pós-contato."
            icon="phone"
        />

        <div class="grid gap-4 xl:grid-cols-[380px_minmax(0,1fr)]">
            <JrCard>
                <h2 class="text-base font-bold text-mono-900">Nova ligação</h2>
                <form class="mt-5 space-y-4" @submit.prevent="submitCall">
                    <JrSelect
                        v-model="callForm.communication_channel_id"
                        label="Canal"
                        icon="settings_input_antenna"
                        :options="options.channels ?? []"
                        :error="callForm.errors.communication_channel_id"
                        required
                    />
                    <JrSelect
                        v-model="callForm.company_id"
                        label="Empresa"
                        icon="business"
                        :options="options.companies ?? []"
                        :error="callForm.errors.company_id"
                        required
                    />
                    <JrSelect
                        v-model="callForm.contact_id"
                        label="Contato"
                        icon="person"
                        :options="filteredContacts"
                        :error="callForm.errors.contact_id"
                        required
                    />
                    <JrSelect
                        v-model="callForm.opportunity_id"
                        label="Oportunidade"
                        icon="payments"
                        :options="filteredOpportunities"
                        :error="callForm.errors.opportunity_id"
                        placeholder="Opcional"
                    />
                    <JrInput
                        v-model="callForm.to_address"
                        label="Telefone"
                        icon="phone"
                        :error="callForm.errors.to_address"
                        required
                    />
                    <JrTextarea
                        v-model="callForm.notes"
                        label="Anotação inicial"
                        :error="callForm.errors.notes"
                        :rows="4"
                    />
                    <JrButton
                        type="submit"
                        icon="call"
                        :disabled="callForm.processing"
                    >
                        Registrar ligação
                    </JrButton>
                </form>
            </JrCard>

            <div class="space-y-4">
                <JrCard>
                    <form
                        class="grid gap-3 md:grid-cols-4"
                        @submit.prevent="submitFilters"
                    >
                        <JrInput
                            v-model="filterForm.search"
                            class="md:col-span-2"
                            label="Busca"
                            icon="search"
                            placeholder="Contato, empresa, telefone ou anotação"
                        />
                        <JrSelect
                            v-model="filterForm.status"
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
                        <div
                            class="flex flex-wrap items-start justify-between gap-3"
                        >
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-mono-900">
                                    {{ message.contact.name }}
                                </p>
                                <Link
                                    :href="
                                        route(
                                            'companies.show',
                                            message.company.id,
                                        )
                                    "
                                    class="mt-1 block text-sm text-mono-600 hover:text-primary-500"
                                >
                                    {{ message.company.display_name }}
                                </Link>
                            </div>
                            <JrBadge
                                :variant="statusVariant(message.status.value)"
                            >
                                {{ message.status.label }}
                            </JrBadge>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm md:grid-cols-3">
                            <p class="text-mono-600">
                                Telefone:
                                <span class="font-semibold text-mono-900">
                                    {{ message.to_address }}
                                </span>
                            </p>
                            <p class="text-mono-600">
                                Canal:
                                <span class="font-semibold text-mono-900">
                                    {{
                                        message.communication_channel?.name ??
                                        'Não informado'
                                    }}
                                </span>
                            </p>
                            <p class="text-mono-600">
                                Data:
                                <span class="font-semibold text-mono-900">
                                    {{ formatDate(message.created_at) }}
                                </span>
                            </p>
                        </div>
                        <p
                            v-if="message.notes"
                            class="mt-3 rounded-xl bg-mono-50 p-3 text-sm text-mono-700"
                        >
                            {{ message.notes }}
                        </p>
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
                    icon="phone"
                    title="Nenhuma ligação registrada"
                    description="Use o formulário para iniciar o histórico de chamadas da operação."
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
