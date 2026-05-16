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
import type { Call, Device } from '@twilio/voice-sdk';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, shallowRef, watch } from 'vue';

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
    dial_mode: 'browser',
});

const voiceDevice = shallowRef<Device | null>(null);
const activeCall = shallowRef<Call | null>(null);
const softphoneStatus = ref<'idle' | 'connecting' | 'ready' | 'dialing' | 'in_call' | 'ended'>('idle');
const softphoneError = ref<string | null>(null);
const callerId = ref<string | null>(null);

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

const canStartBrowserCall = computed(
    () =>
        Boolean(callForm.communication_channel_id) &&
        Boolean(callForm.company_id) &&
        Boolean(callForm.contact_id) &&
        Boolean(callForm.to_address) &&
        softphoneStatus.value !== 'connecting' &&
        softphoneStatus.value !== 'dialing' &&
        softphoneStatus.value !== 'in_call',
);

const softphoneLabel = computed(() => {
    const labels = {
        idle: 'Telefone desconectado',
        connecting: 'Conectando microfone',
        ready: 'Telefone pronto',
        dialing: 'Chamando cliente',
        in_call: 'Em ligação',
        ended: 'Ligação encerrada',
    };

    return labels[softphoneStatus.value];
});

const normalizeBrazilPhone = (value: string) => {
    let digits = value.replace(/\D+/g, '');

    if (digits.startsWith('00')) {
        digits = digits.slice(2);
    }

    if (!digits.startsWith('55')) {
        digits = `55${digits}`;
    }

    return `+${digits}`;
};

const bindCallEvents = (call: Call) => {
    call.on('accept', () => {
        softphoneStatus.value = 'in_call';
        void recordBrowserCall().catch(() => {
            softphoneError.value =
                'A ligação conectou, mas não foi possível registrar o histórico.';
        });
    });
    call.on('disconnect', () => {
        softphoneStatus.value = 'ended';
        activeCall.value = null;
    });
    call.on('cancel', () => {
        softphoneStatus.value = 'ended';
        activeCall.value = null;
    });
    call.on('reject', () => {
        softphoneStatus.value = 'ended';
        activeCall.value = null;
    });
    call.on('error', (error) => {
        softphoneStatus.value = 'ended';
        softphoneError.value = error.message;
        activeCall.value = null;
    });
};

const ensureDevice = async () => {
    if (voiceDevice.value) return voiceDevice.value;

    softphoneError.value = null;
    softphoneStatus.value = 'connecting';

    const response = await window.axios.post(
        route('calls.token'),
        {
            communication_channel_id: callForm.communication_channel_id,
        },
        {
            headers: {
                Accept: 'application/json',
            },
        },
    );
    callerId.value = response.data.caller_id ?? null;
    const { Device: TwilioDevice } = await import('@twilio/voice-sdk');
    const device = new TwilioDevice(response.data.token, {
        logLevel: 1,
    });

    device.on('registered', () => {
        softphoneStatus.value = 'ready';
    });
    device.on('unregistered', () => {
        softphoneStatus.value = 'idle';
    });
    device.on('error', (error) => {
        softphoneStatus.value = 'idle';
        softphoneError.value = error.message;
    });

    await device.register();
    voiceDevice.value = device;

    return device;
};

const recordBrowserCall = async () => {
    await window.axios.post(
        route('calls.store'),
        {
            communication_channel_id: callForm.communication_channel_id,
            company_id: callForm.company_id,
            contact_id: callForm.contact_id,
            opportunity_id: callForm.opportunity_id || null,
            to_address: normalizeBrazilPhone(callForm.to_address),
            notes: callForm.notes,
            dial_mode: 'browser',
        },
        {
            headers: {
                Accept: 'application/json',
            },
        },
    );
};

const startBrowserCall = async () => {
    if (!canStartBrowserCall.value) {
        softphoneError.value = 'Selecione canal, empresa, contato e telefone antes de ligar.';
        return;
    }

    try {
        softphoneError.value = null;
        const device = await ensureDevice();

        softphoneStatus.value = 'dialing';
        const to = normalizeBrazilPhone(callForm.to_address);
        const call = await device.connect({
            params: {
                To: to,
                CallerId: callerId.value ?? '',
            },
        });

        activeCall.value = call;
        bindCallEvents(call);
    } catch (error) {
        softphoneStatus.value = 'idle';
        softphoneError.value =
            error instanceof Error
                ? error.message
                : 'Não foi possível iniciar a ligação.';
    }
};

const hangupCall = () => {
    activeCall.value?.disconnect();
    voiceDevice.value?.disconnectAll();
    activeCall.value = null;
    softphoneStatus.value = 'ended';
};

onBeforeUnmount(() => {
    activeCall.value?.disconnect();
    voiceDevice.value?.destroy();
});

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
                    <div class="rounded-lg border border-mono-200 bg-mono-50 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase text-mono-500">
                                    Softphone
                                </p>
                                <p class="mt-1 text-sm font-bold text-mono-900">
                                    {{ softphoneLabel }}
                                </p>
                            </div>
                            <JrBadge
                                :variant="
                                    softphoneStatus === 'in_call'
                                        ? 'success'
                                        : softphoneStatus === 'ended'
                                          ? 'info'
                                          : 'neutral'
                                "
                            >
                                {{
                                    softphoneStatus === 'in_call'
                                        ? 'Ao vivo'
                                        : softphoneStatus === 'ready'
                                          ? 'Pronto'
                                          : 'Aguardando'
                                }}
                            </JrBadge>
                        </div>
                        <p
                            v-if="softphoneError"
                            class="mt-3 text-sm font-semibold text-error"
                        >
                            {{ softphoneError }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <JrButton
                            type="button"
                            icon="call"
                            :disabled="!canStartBrowserCall"
                            @click="startBrowserCall"
                        >
                            Ligar pelo CRM
                        </JrButton>
                        <JrButton
                            type="button"
                            icon="call_end"
                            variant="standard"
                            :disabled="softphoneStatus !== 'in_call' && softphoneStatus !== 'dialing'"
                            @click="hangupCall"
                        >
                            Encerrar
                        </JrButton>
                    </div>
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
