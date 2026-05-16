<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    AutomationExecutionItem,
    AutomationListItem,
    Option,
} from '@/types/crm';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{
    automations: AutomationListItem[];
    executions: AutomationExecutionItem[];
    options: {
        triggers: Option[];
        actions: Option[];
        statuses: Option[];
    };
}>();

const toggleAutomation = (automation: AutomationListItem) => {
    router.patch(
        route('automations.toggle', automation.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const runChecks = () => {
    router.post(
        route('automations.run-checks'),
        {},
        {
            preserveScroll: true,
        },
    );
};

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('pt-BR', {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(value))
        : 'Sem execução';

const statusVariant = (status: string) => {
    if (status === 'success') return 'success';
    if (status === 'failed') return 'error';
    return 'neutral';
};
</script>

<template>
    <Head title="Automações" />

    <AuthenticatedLayout title="Automações">
        <JrPageHeader
            title="Automações comerciais"
            description="Gatilhos simples para criar tarefas, mensagens, notificações e registros no histórico."
            icon="settings_suggest"
        >
            <template #actions>
                <JrButton
                    type="button"
                    icon="play_arrow"
                    size="sm"
                    @click="runChecks"
                >
                    Executar checks
                </JrButton>
            </template>
        </JrPageHeader>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-4">
                <JrCard v-for="automation in automations" :key="automation.id">
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-mono-900">
                                    {{ automation.name }}
                                </h2>
                                <JrBadge
                                    :variant="
                                        automation.is_active
                                            ? 'success'
                                            : 'neutral'
                                    "
                                >
                                    {{
                                        automation.is_active
                                            ? 'ativa'
                                            : 'pausada'
                                    }}
                                </JrBadge>
                            </div>
                            <p class="mt-1 text-sm text-mono-600">
                                {{ automation.description }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <JrBadge variant="primary">
                                    {{ automation.trigger.label }}
                                </JrBadge>
                                <JrBadge variant="neutral">
                                    {{ automation.executions_count }} execuções
                                </JrBadge>
                            </div>
                        </div>
                        <JrButton
                            type="button"
                            :variant="
                                automation.is_active ? 'standard' : 'primary'
                            "
                            :icon="
                                automation.is_active ? 'pause' : 'play_arrow'
                            "
                            size="sm"
                            @click="toggleAutomation(automation)"
                        >
                            {{ automation.is_active ? 'Pausar' : 'Ativar' }}
                        </JrButton>
                    </div>

                    <div class="mt-5 grid gap-3 lg:grid-cols-2">
                        <div class="rounded-2xl bg-mono-50 p-4">
                            <p
                                class="text-xs font-semibold uppercase text-mono-500"
                            >
                                Condições
                            </p>
                            <pre
                                class="mt-2 whitespace-pre-wrap break-words text-xs text-mono-700"
                                >{{
                                    JSON.stringify(
                                        automation.conditions,
                                        null,
                                        2,
                                    )
                                }}</pre
                            >
                        </div>
                        <div class="rounded-2xl bg-mono-50 p-4">
                            <p
                                class="text-xs font-semibold uppercase text-mono-500"
                            >
                                Ações
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <JrBadge
                                    v-for="(
                                        action, index
                                    ) in automation.actions"
                                    :key="`${automation.id}-${index}`"
                                    variant="info"
                                    size="sm"
                                >
                                    {{ action.label }}
                                </JrBadge>
                            </div>
                            <p class="mt-3 text-xs text-mono-600">
                                Última execução:
                                {{
                                    formatDate(
                                        automation.latest_execution
                                            ?.executed_at ?? null,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </JrCard>

                <JrEmptyState
                    v-if="automations.length === 0"
                    icon="settings_suggest"
                    title="Nenhuma automação configurada"
                    description="Execute o seed para carregar os fluxos comerciais iniciais."
                />
            </div>

            <JrCard>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-mono-900">
                            Execuções recentes
                        </h2>
                        <p class="mt-1 text-sm text-mono-600">
                            Auditoria dos últimos gatilhos processados.
                        </p>
                    </div>
                    <JrBadge variant="info">logs</JrBadge>
                </div>

                <div v-if="executions.length" class="mt-5 space-y-3">
                    <div
                        v-for="execution in executions"
                        :key="execution.id"
                        class="rounded-2xl border border-mono-100 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-bold text-mono-900"
                                >
                                    {{ execution.automation_name }}
                                </p>
                                <p class="mt-1 text-xs text-mono-500">
                                    {{ execution.trigger.label }} ·
                                    {{ formatDate(execution.executed_at) }}
                                </p>
                            </div>
                            <JrBadge
                                :variant="statusVariant(execution.status.value)"
                                size="sm"
                            >
                                {{ execution.status.label }}
                            </JrBadge>
                        </div>
                        <Link
                            v-if="execution.company"
                            :href="
                                route('companies.show', execution.company.id)
                            "
                            class="mt-3 block text-sm font-semibold text-primary-600"
                        >
                            {{ execution.company.display_name }}
                        </Link>
                        <p
                            v-if="execution.error_message"
                            class="mt-3 rounded-xl bg-down-bg p-3 text-xs font-semibold text-error"
                        >
                            {{ execution.error_message }}
                        </p>
                    </div>
                </div>

                <JrEmptyState
                    v-else
                    class="mt-5"
                    icon="history"
                    title="Sem execuções recentes"
                    description="Quando um gatilho rodar, o log aparecerá aqui."
                />
            </JrCard>
        </div>
    </AuthenticatedLayout>
</template>
