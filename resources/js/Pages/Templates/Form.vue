<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    CommunicationTemplateFormData,
    Option,
} from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    mode: 'create' | 'edit';
    template: CommunicationTemplateFormData;
    options: {
        channels: Option[];
    };
}>();

const form = useForm({
    channel: props.template.channel ?? 'email',
    name: props.template.name ?? '',
    subject: props.template.subject ?? '',
    body: props.template.body ?? '',
    is_active: props.template.is_active ?? true,
});

const isWhatsapp = computed(() => form.channel === 'whatsapp');

watch(
    () => form.channel,
    (value) => {
        if (value === 'whatsapp') {
            form.subject = '';
        }
    },
);

const submit = () => {
    if (props.mode === 'create') {
        form.post(route('templates.store'));
        return;
    }

    form.patch(route('templates.update', props.template.id));
};
</script>

<template>
    <Head :title="mode === 'create' ? 'Novo modelo' : 'Editar modelo'" />

    <AuthenticatedLayout title="Modelos">
        <JrPageHeader
            :title="mode === 'create' ? 'Novo modelo' : 'Editar modelo'"
            description="Configure templates reutilizáveis para e-mail e WhatsApp."
            icon="drafts"
        >
            <template #actions>
                <JrButton
                    :href="route('templates.index')"
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
                    v-model="form.channel"
                    label="Canal"
                    icon="forum"
                    :options="options.channels"
                    :error="form.errors.channel"
                    required
                />
                <JrInput
                    v-model="form.name"
                    label="Nome do modelo"
                    icon="label"
                    :error="form.errors.name"
                    required
                />
                <JrInput
                    v-model="form.subject"
                    class="lg:col-span-2"
                    label="Assunto"
                    icon="subject"
                    :disabled="isWhatsapp"
                    :helper="
                        isWhatsapp
                            ? 'WhatsApp não usa assunto.'
                            : 'Usado como assunto do e-mail.'
                    "
                    :error="form.errors.subject"
                    :required="!isWhatsapp"
                />
                <JrTextarea
                    v-model="form.body"
                    class="lg:col-span-2"
                    label="Mensagem"
                    :rows="12"
                    :error="form.errors.body"
                    helper="Variáveis aceitas: {{empresa}}, {{contato}}, {{oportunidade}}."
                    required
                />
                <label
                    class="flex items-center gap-2 rounded-2xl border border-mono-100 p-4 text-sm font-semibold text-mono-700"
                >
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-mono-300 text-primary-500"
                    />
                    Modelo ativo
                </label>
                <div class="flex items-center justify-end gap-2 lg:col-span-2">
                    <JrButton
                        :href="route('templates.index')"
                        variant="standard"
                        icon="close"
                    >
                        Cancelar
                    </JrButton>
                    <JrButton
                        type="submit"
                        icon="save"
                        :disabled="form.processing"
                    >
                        Salvar modelo
                    </JrButton>
                </div>
            </form>
        </JrCard>
    </AuthenticatedLayout>
</template>
