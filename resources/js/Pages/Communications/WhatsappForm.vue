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

const form = useForm({
    communication_channel_id: props.options.channels?.[0]?.value ?? '',
    company_id: props.selectedCompanyId ?? '',
    contact_id: props.selectedContactId ?? '',
    opportunity_id: '',
    communication_template_id: '',
    to_address: '',
    body: '',
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
        form.to_address = contact?.whatsapp ?? contact?.phone ?? '';
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

        form.body = template.body ?? form.body;
    },
);

const submit = () => {
    form.post(route('whatsapp.store'));
};
</script>

<template>
    <Head title="Nova mensagem WhatsApp" />

    <AuthenticatedLayout title="Nova mensagem WhatsApp">
        <JrPageHeader
            title="Nova mensagem WhatsApp"
            description="Envie uma mensagem manual usando um canal de WhatsApp disponível para você."
            icon="chat"
        >
            <template #actions>
                <JrButton
                    :href="route('whatsapp.index')"
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
                    icon="settings_input_antenna"
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
                    label="Número"
                    icon="phone_iphone"
                    :error="form.errors.to_address"
                    required
                />
                <div />
                <JrTextarea
                    v-model="form.body"
                    class="lg:col-span-2"
                    label="Mensagem"
                    :rows="8"
                    :error="form.errors.body"
                    required
                />
                <div class="flex justify-end gap-2 lg:col-span-2">
                    <JrButton
                        :href="route('whatsapp.index')"
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
                        Enviar WhatsApp
                    </JrButton>
                </div>
            </form>
        </JrCard>
    </AuthenticatedLayout>
</template>
