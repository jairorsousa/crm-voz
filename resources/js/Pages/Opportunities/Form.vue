<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CrmOptions, OpportunityFormData, Option } from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    mode: 'create' | 'edit';
    opportunity: OpportunityFormData | null;
    selectedCompanyId: number | null;
    options: CrmOptions;
}>();

const isEdit = computed(() => props.mode === 'edit' && props.opportunity);
const title = computed(() =>
    isEdit.value ? 'Editar oportunidade' : 'Nova oportunidade',
);

const form = useForm<OpportunityFormData>({
    company_id: props.opportunity?.company_id ?? props.selectedCompanyId ?? '',
    contact_id: props.opportunity?.contact_id ?? '',
    responsible_user_id: props.opportunity?.responsible_user_id ?? '',
    pipeline_stage_id:
        props.opportunity?.pipeline_stage_id ??
        props.options.stages?.[0]?.value ??
        '',
    title: props.opportunity?.title ?? '',
    estimated_value: props.opportunity?.estimated_value ?? 0,
    probability: props.opportunity?.probability ?? 0,
    expected_close_date: props.opportunity?.expected_close_date ?? '',
    source: props.opportunity?.source ?? '',
    products_interests: props.opportunity?.products_interests ?? '',
    notes: props.opportunity?.notes ?? '',
    lost_reason: props.opportunity?.lost_reason ?? '',
    closed_value: props.opportunity?.closed_value ?? '',
    closed_at: props.opportunity?.closed_at ?? '',
});

const selectedStage = computed(() =>
    (props.options.stages ?? []).find(
        (stage) => String(stage.value) === String(form.pipeline_stage_id),
    ),
);

const contactOptions = computed<Option[]>(() =>
    (props.options.contacts ?? []).filter(
        (contact) =>
            !form.company_id ||
            String(contact.company_id) === String(form.company_id),
    ),
);

const submit = () => {
    if (isEdit.value && props.opportunity?.id) {
        form.put(route('opportunities.update', props.opportunity.id));
        return;
    }

    form.post(route('opportunities.store'));
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :title="title">
        <JrPageHeader
            :title="title"
            description="Vincule a oportunidade a uma empresa, responsável, etapa e previsão comercial."
            icon="payments"
        >
            <template #actions>
                <JrButton
                    :href="route('opportunities.index')"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Oportunidades
                </JrButton>
            </template>
        </JrPageHeader>

        <form class="space-y-5" @submit.prevent="submit">
            <JrCard>
                <h2 class="text-base font-bold text-mono-900">
                    Dados da oportunidade
                </h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <JrInput
                        v-model="form.title"
                        class="lg:col-span-2"
                        label="Nome da oportunidade"
                        icon="payments"
                        required
                        autofocus
                        :error="form.errors.title"
                    />
                    <JrSelect
                        v-model="form.pipeline_stage_id"
                        label="Etapa atual"
                        icon="view_kanban"
                        required
                        :options="options.stages ?? []"
                        :error="form.errors.pipeline_stage_id"
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
                        label="Contato principal"
                        icon="person"
                        :options="contactOptions"
                        :error="form.errors.contact_id"
                    />
                    <JrSelect
                        v-model="form.responsible_user_id"
                        label="Responsável"
                        icon="person_pin"
                        :options="options.users ?? []"
                        :error="form.errors.responsible_user_id"
                    />
                    <JrInput
                        v-model="form.estimated_value"
                        label="Valor estimado"
                        icon="attach_money"
                        type="number"
                        required
                        :error="form.errors.estimated_value"
                    />
                    <JrInput
                        v-model="form.probability"
                        label="Probabilidade (%)"
                        icon="percent"
                        type="number"
                        required
                        :error="form.errors.probability"
                    />
                    <JrInput
                        v-model="form.expected_close_date"
                        label="Previsão de fechamento"
                        icon="event"
                        type="date"
                        :error="form.errors.expected_close_date"
                    />
                    <JrInput
                        v-model="form.source"
                        label="Origem"
                        icon="conversion_path"
                        :error="form.errors.source"
                    />
                    <JrTextarea
                        v-model="form.products_interests"
                        class="lg:col-span-3"
                        label="Produtos ou serviços de interesse"
                        :error="form.errors.products_interests"
                    />
                    <JrTextarea
                        v-model="form.notes"
                        class="lg:col-span-3"
                        label="Observações"
                        :error="form.errors.notes"
                    />
                </div>
            </JrCard>

            <JrCard v-if="selectedStage?.is_won || selectedStage?.is_lost">
                <h2 class="text-base font-bold text-mono-900">
                    Dados de fechamento
                </h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <template v-if="selectedStage?.is_won">
                        <JrInput
                            v-model="form.closed_value"
                            label="Valor fechado"
                            icon="payments"
                            type="number"
                            required
                            :error="form.errors.closed_value"
                        />
                        <JrInput
                            v-model="form.closed_at"
                            label="Data de ganho"
                            icon="event_available"
                            type="date"
                            required
                            :error="form.errors.closed_at"
                        />
                    </template>
                    <JrTextarea
                        v-if="selectedStage?.is_lost"
                        v-model="form.lost_reason"
                        class="lg:col-span-3"
                        label="Motivo da perda"
                        required
                        :error="form.errors.lost_reason"
                    />
                </div>
            </JrCard>

            <div class="flex justify-end gap-2">
                <JrButton
                    :href="route('opportunities.index')"
                    variant="standard"
                >
                    Cancelar
                </JrButton>
                <JrButton
                    type="submit"
                    icon="check_circle"
                    :disabled="form.processing"
                >
                    {{
                        isEdit ? 'Salvar alterações' : 'Cadastrar oportunidade'
                    }}
                </JrButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
