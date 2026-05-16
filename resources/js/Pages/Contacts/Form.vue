<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ContactFormData, CrmOptions } from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    mode: 'create' | 'edit';
    contact: ContactFormData | null;
    selectedCompanyId: number | null;
    options: CrmOptions;
}>();

const isEdit = computed(() => props.mode === 'edit' && props.contact);
const title = computed(() =>
    isEdit.value ? 'Editar contato' : 'Novo contato',
);

const form = useForm<ContactFormData>({
    company_id: props.contact?.company_id ?? props.selectedCompanyId ?? '',
    name: props.contact?.name ?? '',
    position: props.contact?.position ?? '',
    department: props.contact?.department ?? '',
    email: props.contact?.email ?? '',
    phone: props.contact?.phone ?? '',
    whatsapp: props.contact?.whatsapp ?? '',
    linkedin_url: props.contact?.linkedin_url ?? '',
    type: props.contact?.type ?? 'other',
    is_primary: props.contact?.is_primary ?? false,
    receives_automations: props.contact?.receives_automations ?? true,
    notes: props.contact?.notes ?? '',
});

const submit = () => {
    if (isEdit.value && props.contact?.id) {
        form.put(route('contacts.update', props.contact.id));
        return;
    }

    form.post(route('contacts.store'));
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :title="title">
        <JrPageHeader
            :title="title"
            description="Todo contato precisa estar vinculado a uma empresa para manter o histórico centralizado."
            icon="person"
        >
            <template #actions>
                <JrButton
                    :href="route('contacts.index')"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Contatos
                </JrButton>
            </template>
        </JrPageHeader>

        <form class="space-y-5" @submit.prevent="submit">
            <JrCard>
                <h2 class="text-base font-bold text-mono-900">
                    Dados do contato
                </h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <JrSelect
                        v-model="form.company_id"
                        class="lg:col-span-2"
                        label="Empresa"
                        icon="business"
                        required
                        :options="options.companies ?? []"
                        :error="form.errors.company_id"
                    />
                    <JrSelect
                        v-model="form.type"
                        label="Tipo de contato"
                        icon="badge"
                        required
                        :options="options.contactTypes"
                        :error="form.errors.type"
                    />
                    <JrInput
                        v-model="form.name"
                        label="Nome"
                        icon="person"
                        required
                        autofocus
                        :error="form.errors.name"
                    />
                    <JrInput
                        v-model="form.position"
                        label="Cargo"
                        icon="work"
                        :error="form.errors.position"
                    />
                    <JrInput
                        v-model="form.department"
                        label="Departamento"
                        icon="account_tree"
                        :error="form.errors.department"
                    />
                    <JrInput
                        v-model="form.email"
                        label="E-mail"
                        icon="email"
                        type="email"
                        :error="form.errors.email"
                    />
                    <JrInput
                        v-model="form.phone"
                        label="Telefone"
                        icon="phone"
                        :error="form.errors.phone"
                    />
                    <JrInput
                        v-model="form.whatsapp"
                        label="WhatsApp"
                        icon="chat"
                        :error="form.errors.whatsapp"
                    />
                    <JrInput
                        v-model="form.linkedin_url"
                        class="lg:col-span-3"
                        label="LinkedIn"
                        icon="open_in_new"
                        placeholder="https://"
                        :error="form.errors.linkedin_url"
                    />
                    <label
                        class="flex min-h-12 items-center gap-3 rounded-2xl border border-mono-100 bg-mono-50 px-4 text-sm font-semibold text-mono-600"
                    >
                        <input
                            v-model="form.is_primary"
                            type="checkbox"
                            class="rounded border-mono-200 text-primary-500 focus:ring-primary-500"
                        />
                        Contato principal da empresa
                    </label>
                    <label
                        class="flex min-h-12 items-center gap-3 rounded-2xl border border-mono-100 bg-mono-50 px-4 text-sm font-semibold text-mono-600"
                    >
                        <input
                            v-model="form.receives_automations"
                            type="checkbox"
                            class="rounded border-mono-200 text-primary-500 focus:ring-primary-500"
                        />
                        Recebe comunicações automáticas
                    </label>
                    <JrTextarea
                        v-model="form.notes"
                        class="lg:col-span-3"
                        label="Observações"
                        :error="form.errors.notes"
                    />
                </div>
            </JrCard>

            <div class="flex justify-end gap-2">
                <JrButton :href="route('contacts.index')" variant="standard">
                    Cancelar
                </JrButton>
                <JrButton
                    type="submit"
                    icon="check_circle"
                    :disabled="form.processing"
                >
                    {{ isEdit ? 'Salvar alterações' : 'Cadastrar contato' }}
                </JrButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
