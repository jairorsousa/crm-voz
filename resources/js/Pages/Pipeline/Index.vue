<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrModal from '@/Components/Jr/JrModal.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Option, PipelineCard, PipelineStage } from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    pipeline: {
        id: number;
        name: string;
    };
    stages: PipelineStage[];
    filters: {
        responsible_user_id?: string;
        pipeline_stage_id?: string;
        source?: string;
        lead_temperature?: string;
        expected_close_from?: string;
        expected_close_to?: string;
    };
    options: {
        stages: Option[];
        users: Option[];
        leadTemperatures: Option[];
        sources: string[];
    };
}>();

const draggedOpportunity = ref<PipelineCard | null>(null);
const pendingStage = ref<PipelineStage | null>(null);
const showMoveModal = ref(false);

const filters = useForm({
    responsible_user_id: props.filters.responsible_user_id ?? '',
    pipeline_stage_id: props.filters.pipeline_stage_id ?? '',
    source: props.filters.source ?? '',
    lead_temperature: props.filters.lead_temperature ?? '',
    expected_close_from: props.filters.expected_close_from ?? '',
    expected_close_to: props.filters.expected_close_to ?? '',
});

const moveForm = useForm({
    pipeline_stage_id: '',
    lost_reason: '',
    closed_value: '',
    closed_at: '',
    movement_notes: '',
});

const sourceOptions = computed<Option[]>(() =>
    props.options.sources.map((source) => ({
        value: source,
        label: source,
    })),
);

const submitFilters = () => {
    filters.get(route('pipeline.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    filters.responsible_user_id = '';
    filters.pipeline_stage_id = '';
    filters.source = '';
    filters.lead_temperature = '';
    filters.expected_close_from = '';
    filters.expected_close_to = '';
    submitFilters();
};

const startDrag = (opportunity: PipelineCard) => {
    draggedOpportunity.value = opportunity;
};

const dropOnStage = (stage: PipelineStage) => {
    if (!draggedOpportunity.value) {
        return;
    }

    if (stage.is_won || stage.is_lost) {
        pendingStage.value = stage;
        moveForm.pipeline_stage_id = String(stage.id);
        moveForm.lost_reason = '';
        moveForm.closed_value = '';
        moveForm.closed_at = new Date().toISOString().slice(0, 10);
        moveForm.movement_notes = '';
        showMoveModal.value = true;
        return;
    }

    moveOpportunity(stage.id);
};

const moveOpportunity = (stageId?: number) => {
    if (!draggedOpportunity.value) {
        return;
    }

    const opportunityId = draggedOpportunity.value.id;

    router.patch(
        route('pipeline.move', opportunityId),
        {
            pipeline_stage_id: stageId ?? moveForm.pipeline_stage_id,
            lost_reason: moveForm.lost_reason,
            closed_value: moveForm.closed_value,
            closed_at: moveForm.closed_at,
            movement_notes: moveForm.movement_notes,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showMoveModal.value = false;
                pendingStage.value = null;
                draggedOpportunity.value = null;
                moveForm.reset();
            },
        },
    );
};

const closeModal = () => {
    showMoveModal.value = false;
    pendingStage.value = null;
    draggedOpportunity.value = null;
    moveForm.reset();
};
</script>

<template>
    <Head title="Pipeline" />

    <AuthenticatedLayout title="Pipeline">
        <JrPageHeader
            :title="pipeline.name"
            description="Kanban comercial com movimentação de oportunidades e registro automático no histórico."
            icon="view_kanban"
        >
            <template #actions>
                <JrButton
                    :href="route('opportunities.create')"
                    icon="add"
                    size="sm"
                >
                    Nova oportunidade
                </JrButton>
                <JrButton
                    :href="route('opportunities.index')"
                    variant="standard"
                    icon="payments"
                    size="sm"
                >
                    Lista
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard class="mb-5">
            <form
                class="grid gap-3 lg:grid-cols-6"
                @submit.prevent="submitFilters"
            >
                <JrSelect
                    v-model="filters.responsible_user_id"
                    label="Responsável"
                    icon="person"
                    :options="options.users"
                    placeholder="Todos"
                />
                <JrSelect
                    v-model="filters.pipeline_stage_id"
                    label="Etapa"
                    icon="view_kanban"
                    :options="options.stages"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="filters.source"
                    label="Origem"
                    icon="conversion_path"
                    :options="sourceOptions"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="filters.lead_temperature"
                    label="Temperatura"
                    icon="local_fire_department"
                    :options="options.leadTemperatures"
                    placeholder="Todas"
                />
                <JrInput
                    v-model="filters.expected_close_from"
                    label="Previsão de"
                    icon="event"
                    type="date"
                />
                <JrInput
                    v-model="filters.expected_close_to"
                    label="Previsão até"
                    icon="event"
                    type="date"
                />
                <div class="flex items-end gap-2 lg:col-span-6">
                    <JrButton type="submit" icon="filter_list" size="sm">
                        Filtrar
                    </JrButton>
                    <JrButton
                        type="button"
                        variant="standard"
                        icon="close"
                        size="sm"
                        @click="clearFilters"
                    >
                        Limpar
                    </JrButton>
                </div>
            </form>
        </JrCard>

        <div class="overflow-x-auto pb-4">
            <div
                class="grid min-w-[1180px] auto-cols-[300px] grid-flow-col gap-4"
            >
                <section
                    v-for="stage in stages"
                    :key="stage.id"
                    class="flex max-h-[calc(100vh-250px)] min-h-[560px] flex-col rounded-2xl border border-mono-100 bg-mono-white"
                    @dragover.prevent
                    @drop="dropOnStage(stage)"
                >
                    <header class="border-b border-mono-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-bold text-mono-900">
                                    {{ stage.name }}
                                </h2>
                                <p class="mt-1 text-xs text-mono-600">
                                    {{ stage.total_count }} oportunidades
                                </p>
                            </div>
                            <JrBadge
                                :variant="
                                    stage.color === 'down'
                                        ? 'error'
                                        : stage.color === 'success'
                                          ? 'success'
                                          : 'primary'
                                "
                                size="sm"
                            >
                                {{ stage.formatted_total_value }}
                            </JrBadge>
                        </div>
                    </header>

                    <div class="flex-1 space-y-3 overflow-y-auto p-3">
                        <article
                            v-for="opportunity in stage.opportunities"
                            :key="opportunity.id"
                            draggable="true"
                            class="cursor-grab rounded-2xl border border-mono-100 bg-mono-50 p-4 shadow-sm transition hover:border-primary-500 active:cursor-grabbing"
                            @dragstart="startDrag(opportunity)"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <Link
                                    :href="
                                        route(
                                            'opportunities.edit',
                                            opportunity.id,
                                        )
                                    "
                                    class="text-sm font-bold leading-md text-mono-900 hover:text-primary-500"
                                >
                                    {{ opportunity.title }}
                                </Link>
                                <span
                                    class="material-icons-outlined text-[18px] text-mono-300"
                                >
                                    drag_indicator
                                </span>
                            </div>

                            <Link
                                :href="
                                    route(
                                        'companies.show',
                                        opportunity.company.id,
                                    )
                                "
                                class="mt-2 block text-xs font-semibold text-primary-600"
                            >
                                {{ opportunity.company.display_name }}
                            </Link>

                            <div
                                class="mt-4 flex items-center justify-between gap-3"
                            >
                                <p class="text-sm font-bold text-mono-900">
                                    {{ opportunity.formatted_estimated_value }}
                                </p>
                                <JrBadge variant="info" size="sm">
                                    {{ opportunity.probability }}%
                                </JrBadge>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <JrBadge variant="neutral" size="sm">
                                    {{
                                        opportunity.company.lead_temperature
                                            .label
                                    }}
                                </JrBadge>
                                <JrBadge
                                    v-if="opportunity.source"
                                    variant="primary"
                                    size="sm"
                                >
                                    {{ opportunity.source }}
                                </JrBadge>
                            </div>

                            <p class="mt-3 text-xs text-mono-600">
                                {{
                                    opportunity.responsible_user?.name ??
                                    'Sem responsável'
                                }}
                                <span v-if="opportunity.expected_close_date">
                                    · {{ opportunity.expected_close_date }}
                                </span>
                            </p>
                        </article>

                        <div
                            v-if="stage.opportunities.length === 0"
                            class="flex min-h-40 items-center justify-center rounded-2xl border border-dashed border-mono-200 p-4 text-center text-sm text-mono-600"
                        >
                            Solte oportunidades aqui.
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <JrModal
            :show="showMoveModal"
            :title="`Mover para ${pendingStage?.name ?? ''}`"
            @close="closeModal"
        >
            <form class="space-y-4" @submit.prevent="moveOpportunity()">
                <JrTextarea
                    v-if="pendingStage?.is_lost"
                    v-model="moveForm.lost_reason"
                    label="Motivo da perda"
                    required
                    :error="moveForm.errors.lost_reason"
                />
                <div
                    v-if="pendingStage?.is_won"
                    class="grid gap-4 sm:grid-cols-2"
                >
                    <JrInput
                        v-model="moveForm.closed_value"
                        label="Valor fechado"
                        icon="payments"
                        type="number"
                        required
                        :error="moveForm.errors.closed_value"
                    />
                    <JrInput
                        v-model="moveForm.closed_at"
                        label="Data de ganho"
                        icon="event_available"
                        type="date"
                        required
                        :error="moveForm.errors.closed_at"
                    />
                </div>
                <JrTextarea
                    v-model="moveForm.movement_notes"
                    label="Observações da movimentação"
                    :error="moveForm.errors.movement_notes"
                />
                <div class="flex justify-end gap-2">
                    <JrButton
                        type="button"
                        variant="standard"
                        @click="closeModal"
                    >
                        Cancelar
                    </JrButton>
                    <JrButton
                        type="submit"
                        icon="check_circle"
                        :disabled="moveForm.processing"
                    >
                        Confirmar movimentação
                    </JrButton>
                </div>
            </form>
        </JrModal>
    </AuthenticatedLayout>
</template>
