<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CrmOptions } from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    selectedCompanyId: number | null;
    selectedContactId: number | null;
    options: CrmOptions;
}>();

const form = useForm<{
    company_id: number | string | null;
    communication_channel_id: number | string | null;
    contact_id: number | string | null;
    opportunity_id: number | string | null;
    communication_template_id: number | string | null;
    to_address: string;
    cc: string;
    bcc: string;
    subject: string;
    body: string;
    attachments: File[];
}>({
    company_id: props.selectedCompanyId ?? '',
    communication_channel_id: props.options.channels?.[0]?.value ?? '',
    contact_id: props.selectedContactId ?? '',
    opportunity_id: '',
    communication_template_id: '',
    to_address: '',
    cc: '',
    bcc: '',
    subject: '',
    body: '',
    attachments: [],
});

const filteredContacts = computed(() =>
    (props.options.contacts ?? []).filter(
        (contact) =>
            !form.company_id ||
            Number(contact.company_id) === Number(form.company_id),
    ),
);

const filteredOpportunities = computed(() =>
    (props.options.opportunities ?? []).filter(
        (opportunity) =>
            !form.company_id ||
            Number(opportunity.company_id) === Number(form.company_id),
    ),
);

watch(
    () => form.company_id,
    () => {
        form.contact_id = '';
        form.opportunity_id = '';
        form.to_address = '';
    },
);

watch(
    () => form.contact_id,
    (value) => {
        const contact = (props.options.contacts ?? []).find(
            (item) => Number(item.value) === Number(value),
        );
        form.to_address = contact?.email ?? '';
    },
    { immediate: true },
);

watch(
    () => form.communication_template_id,
    (value) => {
        const template = (props.options.templates ?? []).find(
            (item) => Number(item.value) === Number(value),
        );

        if (!template) return;

        form.subject = template.subject ?? form.subject;
        form.body = template.body ?? form.body;
    },
);

const updateFiles = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.attachments = Array.from(target.files ?? []);
};

const submit = () => {
    form.post(route('emails.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Novo e-mail" />

    <AuthenticatedLayout title="Novo e-mail">
        <JrPageHeader
            title="Novo e-mail"
            description="Componha uma mensagem manual e registre tudo no histórico da empresa."
            icon="email"
        >
            <template #actions>
                <JrButton
                    :href="route('emails.index')"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Voltar
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard>
            <form class="grid gap-4 lg:grid-cols-2" @submit.prevent="submit">
                <JrSelect
                    v-model="form.communication_channel_id"
                    label="Canal"
                    icon="outgoing_mail"
                    :options="options.channels ?? []"
                    :error="form.errors.communication_channel_id"
                    required
                />
                <JrSelect
                    v-model="form.company_id"
                    label="Empresa"
                    icon="business"
                    :options="options.companies ?? []"
                    :error="form.errors.company_id"
                    required
                />
                <JrSelect
                    v-model="form.contact_id"
                    label="Contato"
                    icon="person"
                    :options="filteredContacts"
                    :error="form.errors.contact_id"
                    required
                />
                <JrSelect
                    v-model="form.opportunity_id"
                    label="Oportunidade"
                    icon="payments"
                    :options="filteredOpportunities"
                    :error="form.errors.opportunity_id"
                    placeholder="Opcional"
                />
                <JrSelect
                    v-model="form.communication_template_id"
                    label="Modelo"
                    icon="description"
                    :options="options.templates ?? []"
                    :error="form.errors.communication_template_id"
                    placeholder="Sem modelo"
                />
                <JrInput
                    v-model="form.to_address"
                    label="Para"
                    icon="alternate_email"
                    type="email"
                    :error="form.errors.to_address"
                    required
                />
                <JrInput
                    v-model="form.cc"
                    label="CC"
                    icon="alternate_email"
                    :error="form.errors.cc"
                    placeholder="separe por vírgula"
                />
                <JrInput
                    v-model="form.bcc"
                    label="CCO"
                    icon="alternate_email"
                    :error="form.errors.bcc"
                    placeholder="separe por vírgula"
                />
                <JrInput
                    v-model="form.subject"
                    label="Assunto"
                    icon="subject"
                    :error="form.errors.subject"
                    required
                />
                <JrTextarea
                    v-model="form.body"
                    class="lg:col-span-2"
                    label="Mensagem"
                    :rows="10"
                    :error="form.errors.body"
                    required
                />
                <div class="lg:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-mono-600">
                        Anexos
                    </label>
                    <input
                        type="file"
                        multiple
                        class="block w-full rounded-2xl border border-mono-200 bg-mono-white px-4 py-3 text-sm text-mono-700"
                        @change="updateFiles"
                    />
                    <p class="mt-2 text-xs text-mono-500">
                        Até 5 arquivos, 10MB cada.
                    </p>
                </div>
                <div class="flex justify-end gap-2 lg:col-span-2">
                    <JrButton
                        :href="route('emails.index')"
                        variant="standard"
                        icon="close"
                    >
                        Cancelar
                    </JrButton>
                    <JrButton
                        type="submit"
                        icon="send"
                        :disabled="form.processing"
                    >
                        Enviar e-mail
                    </JrButton>
                </div>
            </form>
        </JrCard>
    </AuthenticatedLayout>
</template>
