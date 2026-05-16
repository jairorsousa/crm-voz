<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ActivityFormData, CrmOptions, Option } from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    mode: 'create' | 'edit';
    activity: ActivityFormData | null;
    selectedCompanyId: number | null;
    options: CrmOptions;
}>();

const isEdit = computed(() => props.mode === 'edit' && props.activity);
const title = computed(() =>
    isEdit.value ? 'Editar atividade' : 'Nova atividade',
);

const form = useForm<ActivityFormData>({
    company_id: props.activity?.company_id ?? props.selectedCompanyId ?? '',
    contact_id: props.activity?.contact_id ?? '',
    opportunity_id: props.activity?.opportunity_id ?? '',
    assigned_to_user_id: props.activity?.assigned_to_user_id ?? '',
    type: props.activity?.type ?? 'task',
    status: props.activity?.status ?? 'pending',
    priority: props.activity?.priority ?? 'medium',
    title: props.activity?.title ?? '',
    description: props.activity?.description ?? '',
    due_at: props.activity?.due_at ?? '',
});

const contactOptions = computed<Option[]>(() =>
    (props.options.contacts ?? []).filter(
        (contact) =>
            !form.company_id ||
            String(contact.company_id) === String(form.company_id),
    ),
);

const opportunityOptions = computed<Option[]>(() =>
    (props.options.opportunities ?? []).filter(
        (opportunity) =>
            !form.company_id ||
            String(opportunity.company_id) === String(form.company_id),
    ),
);

const submit = () => {
    if (isEdit.value && props.activity?.id) {
        form.put(route('activities.update', props.activity.id));
        return;
    }

    form.post(route('activities.store'));
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :title="title">
        <JrPageHeader
            :title="title"
            description="Crie tarefas, reuniões e follow-ups sempre vinculados a uma empresa."
            icon="calendar_today"
        >
            <template #actions>
                <JrButton
                    :href="route('activities.index')"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Atividades
                </JrButton>
            </template>
        </JrPageHeader>

        <form class="space-y-5" @submit.prevent="submit">
            <JrCard>
                <h2 class="text-base font-bold text-mono-900">
                    Dados da atividade
                </h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <JrInput
                        v-model="form.title"
                        class="lg:col-span-2"
                        label="Título"
                        icon="task_alt"
                        required
                        autofocus
                        :error="form.errors.title"
                    />
                    <JrSelect
                        v-model="form.type"
                        label="Tipo"
                        icon="event_note"
                        required
                        :options="options.activityTypes"
                        :error="form.errors.type"
                    />
                    <JrSelect
                        v-model="form.company_id"
                        label="Empresa"
                        icon="business"
                        required
                        :options="options.companies ?? []"
                        :error="form.errors.company_id"
                    />
                    <JrSelect
                        v-model="form.contact_id"
                        label="Contato"
                        icon="person"
                        :options="contactOptions"
                        :error="form.errors.contact_id"
                    />
                    <JrSelect
                        v-model="form.opportunity_id"
                        label="Oportunidade"
                        icon="payments"
                        :options="opportunityOptions"
                        :error="form.errors.opportunity_id"
                    />
                    <JrSelect
                        v-model="form.assigned_to_user_id"
                        label="Responsável"
                        icon="person_pin"
                        required
                        :options="options.users ?? []"
                        :error="form.errors.assigned_to_user_id"
                    />
                    <JrSelect
                        v-model="form.priority"
                        label="Prioridade"
                        icon="priority_high"
                        required
                        :options="options.priorities"
                        :error="form.errors.priority"
                    />
                    <JrSelect
                        v-if="isEdit"
                        v-model="form.status"
                        label="Status"
                        icon="flag"
                        required
                        :options="options.activityStatuses"
                        :error="form.errors.status"
                    />
                    <JrInput
                        v-model="form.due_at"
                        label="Data e hora"
                        icon="event"
                        type="datetime-local"
                        required
                        :error="form.errors.due_at"
                    />
                    <JrTextarea
                        v-model="form.description"
                        class="lg:col-span-3"
                        label="Descrição"
                        :error="form.errors.description"
                    />
                </div>
            </JrCard>

            <div class="flex justify-end gap-2">
                <JrButton :href="route('activities.index')" variant="standard">
                    Cancelar
                </JrButton>
                <JrButton
                    type="submit"
                    icon="check_circle"
                    :disabled="form.processing"
                >
                    {{ isEdit ? 'Salvar alterações' : 'Criar atividade' }}
                </JrButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
