<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrIconBox from '@/Components/Jr/JrIconBox.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrTable from '@/Components/Jr/JrTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CompanyDetail, CrmOptions } from '@/types/crm';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{
    company: CompanyDetail;
    options: CrmOptions;
}>();

const formatDate = (value: string | null) => {
    if (!value) {
        return 'Sem registro';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(new Date(value));
};

const money = (value: string | number | null) => {
    if (value === null || value === '') {
        return 'Não informado';
    }

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(value));
};

const destroyContact = (contactId: number, name: string) => {
    if (!confirm(`Remover o contato ${name}?`)) {
        return;
    }

    router.delete(route('contacts.destroy', contactId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="company.display_name" />

    <AuthenticatedLayout :title="company.display_name">
        <JrPageHeader
            :title="company.display_name"
            :description="`${company.formatted_cnpj} · ${company.status_label}`"
            icon="business"
        >
            <template #actions>
                <JrButton
                    :href="route('contacts.create', { company_id: company.id })"
                    icon="person_add"
                    size="sm"
                >
                    Novo contato
                </JrButton>
                <JrButton
                    :href="
                        route('activities.create', { company_id: company.id })
                    "
                    variant="standard"
                    icon="calendar_today"
                    size="sm"
                >
                    Nova atividade
                </JrButton>
                <JrButton
                    :href="route('companies.edit', company.id)"
                    variant="standard"
                    icon="edit"
                    size="sm"
                >
                    Editar
                </JrButton>
            </template>
        </JrPageHeader>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-4">
                <JrCard>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-medium text-mono-600">
                                Razão social
                            </p>
                            <p class="mt-1 text-sm font-bold text-mono-900">
                                {{ company.legal_name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-mono-600">
                                Responsável
                            </p>
                            <p class="mt-1 text-sm font-bold text-mono-900">
                                {{
                                    company.responsible_user?.name ??
                                    'Sem responsável'
                                }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-mono-600">
                                Última interação
                            </p>
                            <p class="mt-1 text-sm font-bold text-mono-900">
                                {{ formatDate(company.last_interaction_at) }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <JrBadge variant="primary">{{
                            company.status_label
                        }}</JrBadge>
                        <JrBadge variant="info">{{
                            company.lead_temperature_label
                        }}</JrBadge>
                        <JrBadge variant="neutral">{{
                            company.priority_label
                        }}</JrBadge>
                    </div>
                </JrCard>

                <JrCard>
                    <div class="mb-5 flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-base font-bold text-mono-900">
                                Contatos
                            </h2>
                            <p class="mt-1 text-sm text-mono-600">
                                Pessoas vinculadas à empresa.
                            </p>
                        </div>
                        <JrButton
                            :href="
                                route('contacts.create', {
                                    company_id: company.id,
                                })
                            "
                            icon="add"
                            size="sm"
                        >
                            Adicionar
                        </JrButton>
                    </div>

                    <JrTable v-if="company.contacts.length">
                        <template #head>
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                                >
                                    Contato
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase text-mono-600"
                                >
                                    Comunicação
                                </th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase text-mono-600"
                                >
                                    Ações
                                </th>
                            </tr>
                        </template>

                        <tr
                            v-for="contact in company.contacts"
                            :key="contact.id"
                        >
                            <td class="px-4 py-4">
                                <p class="font-bold text-mono-900">
                                    {{ contact.name }}
                                </p>
                                <p class="mt-1 text-xs text-mono-600">
                                    {{
                                        contact.position ??
                                        'Cargo não informado'
                                    }}
                                    <span v-if="contact.department">
                                        · {{ contact.department }}
                                    </span>
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1.5">
                                    <JrBadge variant="info" size="sm">
                                        {{ contact.type_label }}
                                    </JrBadge>
                                    <JrBadge
                                        v-if="contact.is_primary"
                                        variant="primary"
                                        size="sm"
                                    >
                                        principal
                                    </JrBadge>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-mono-600">
                                <p>{{ contact.email ?? 'Sem e-mail' }}</p>
                                <p>
                                    {{
                                        contact.formatted_whatsapp ??
                                        contact.formatted_phone ??
                                        'Sem telefone'
                                    }}
                                </p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <JrButton
                                        :href="
                                            route('calls.index', {
                                                company_id: company.id,
                                                contact_id: contact.id,
                                            })
                                        "
                                        variant="standard"
                                        icon="call"
                                        size="sm"
                                    >
                                        Ligar
                                    </JrButton>
                                    <JrButton
                                        :href="
                                            route('emails.create', {
                                                company_id: company.id,
                                                contact_id: contact.id,
                                            })
                                        "
                                        variant="standard"
                                        icon="email"
                                        size="sm"
                                    >
                                        E-mail
                                    </JrButton>
                                    <JrButton
                                        :href="
                                            route('whatsapp.create', {
                                                company_id: company.id,
                                                contact_id: contact.id,
                                            })
                                        "
                                        variant="standard"
                                        icon="chat"
                                        size="sm"
                                    >
                                        WhatsApp
                                    </JrButton>
                                    <JrButton
                                        :href="
                                            route('contacts.edit', contact.id)
                                        "
                                        variant="standard"
                                        icon="edit"
                                        size="sm"
                                    >
                                        Editar
                                    </JrButton>
                                    <JrButton
                                        type="button"
                                        variant="danger"
                                        icon="delete_outline"
                                        size="sm"
                                        @click="
                                            destroyContact(
                                                contact.id,
                                                contact.name,
                                            )
                                        "
                                    >
                                        Excluir
                                    </JrButton>
                                </div>
                            </td>
                        </tr>
                    </JrTable>

                    <JrEmptyState
                        v-else
                        icon="person_add"
                        title="Nenhum contato cadastrado"
                        description="Cadastre decisores, influenciadores e responsáveis operacionais para concentrar o relacionamento na empresa."
                        action-label="Novo contato"
                        :action-href="
                            route('contacts.create', { company_id: company.id })
                        "
                    />
                </JrCard>

                <div class="grid gap-4 lg:grid-cols-2">
                    <JrCard>
                        <div
                            class="mb-4 flex items-center justify-between gap-4"
                        >
                            <h2 class="text-base font-bold text-mono-900">
                                Oportunidades
                            </h2>
                            <JrButton
                                :href="
                                    route('opportunities.create', {
                                        company_id: company.id,
                                    })
                                "
                                icon="add"
                                size="sm"
                            >
                                Nova
                            </JrButton>
                        </div>
                        <div
                            v-if="company.opportunities.length"
                            class="space-y-3"
                        >
                            <div
                                v-for="opportunity in company.opportunities"
                                :key="opportunity.id"
                                class="rounded-2xl bg-mono-50 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'opportunities.edit',
                                                opportunity.id,
                                            )
                                        "
                                        class="text-sm font-bold text-mono-900 hover:text-primary-500"
                                    >
                                        {{ opportunity.title }}
                                    </Link>
                                    <JrBadge variant="info" size="sm">
                                        {{ opportunity.stage.name }}
                                    </JrBadge>
                                </div>
                                <p class="mt-2 text-sm font-bold text-mono-900">
                                    {{ opportunity.formatted_estimated_value }}
                                </p>
                                <p class="mt-1 text-xs text-mono-600">
                                    {{ opportunity.probability }}% ·
                                    {{
                                        opportunity.responsible_user?.name ??
                                        'Sem responsável'
                                    }}
                                </p>
                            </div>
                        </div>
                        <JrEmptyState
                            v-else
                            icon="payments"
                            title="Nenhuma oportunidade"
                            description="Cadastre a primeira oportunidade para acompanhar esta empresa no pipeline."
                            action-label="Nova oportunidade"
                            :action-href="
                                route('opportunities.create', {
                                    company_id: company.id,
                                })
                            "
                        />
                    </JrCard>
                    <JrCard>
                        <div
                            class="mb-4 flex items-center justify-between gap-4"
                        >
                            <h2 class="text-base font-bold text-mono-900">
                                Atividades
                            </h2>
                            <JrButton
                                :href="
                                    route('activities.create', {
                                        company_id: company.id,
                                    })
                                "
                                icon="add"
                                size="sm"
                            >
                                Nova
                            </JrButton>
                        </div>
                        <div v-if="company.activities.length" class="space-y-3">
                            <div
                                v-for="activity in company.activities"
                                :key="activity.id"
                                class="rounded-2xl bg-mono-50 p-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'activities.edit',
                                                activity.id,
                                            )
                                        "
                                        class="text-sm font-bold text-mono-900 hover:text-primary-500"
                                    >
                                        {{ activity.title }}
                                    </Link>
                                    <JrBadge
                                        :variant="
                                            activity.is_overdue
                                                ? 'error'
                                                : 'info'
                                        "
                                        size="sm"
                                    >
                                        {{ activity.type_label }}
                                    </JrBadge>
                                </div>
                                <p class="mt-2 text-xs text-mono-600">
                                    {{ activity.status_label }} ·
                                    {{ activity.assigned_to.name }}
                                </p>
                                <p
                                    class="mt-1 text-xs font-semibold"
                                    :class="
                                        activity.is_overdue
                                            ? 'text-error'
                                            : 'text-mono-600'
                                    "
                                >
                                    {{ formatDate(activity.due_at) }}
                                </p>
                            </div>
                        </div>
                        <JrEmptyState
                            v-else
                            icon="calendar_today"
                            title="Nenhuma atividade"
                            description="Crie tarefas, reuniões e follow-ups conectados ao histórico da empresa."
                            action-label="Nova atividade"
                            :action-href="
                                route('activities.create', {
                                    company_id: company.id,
                                })
                            "
                        />
                    </JrCard>
                </div>
            </div>

            <div class="space-y-4">
                <JrCard>
                    <h2 class="text-base font-bold text-mono-900">Carteira</h2>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <JrIconBox icon="payments" variant="up" />
                            <div>
                                <p class="text-xs text-mono-600">
                                    Ticket médio
                                </p>
                                <p class="text-sm font-bold text-mono-900">
                                    {{
                                        money(company.average_collection_ticket)
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <JrIconBox icon="account_balance" variant="down" />
                            <div>
                                <p class="text-xs text-mono-600">
                                    Inadimplência total
                                </p>
                                <p class="text-sm font-bold text-mono-900">
                                    {{ money(company.total_default_amount) }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl bg-mono-50 p-4 text-sm text-mono-600"
                        >
                            {{
                                company.portfolio_notes ??
                                'Sem observações sobre a carteira.'
                            }}
                        </div>
                    </div>
                </JrCard>

                <JrCard>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-base font-bold text-mono-900">
                            Histórico recente
                        </h2>
                        <JrButton
                            :href="route('companies.timeline', company.id)"
                            variant="standard"
                            icon="history"
                            size="sm"
                        >
                            Ver tudo
                        </JrButton>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="event in company.timeline_events"
                            :key="event.id"
                            class="rounded-2xl bg-mono-50 p-4"
                        >
                            <p class="text-sm font-bold text-mono-900">
                                {{ event.title }}
                            </p>
                            <p
                                v-if="event.description"
                                class="mt-1 text-sm text-mono-600"
                            >
                                {{ event.description }}
                            </p>
                            <p class="mt-2 text-xs text-mono-600">
                                {{ formatDate(event.occurred_at) }}
                                <span v-if="event.user_name">
                                    · {{ event.user_name }}</span
                                >
                            </p>
                        </div>
                        <p
                            v-if="company.timeline_events.length === 0"
                            class="text-sm text-mono-600"
                        >
                            Nenhum evento registrado ainda.
                        </p>
                    </div>
                </JrCard>

                <JrCard>
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-base font-bold text-mono-900">
                            Comunicações
                        </h2>
                        <div class="flex gap-2">
                            <JrButton
                                :href="
                                    route('emails.create', {
                                        company_id: company.id,
                                    })
                                "
                                variant="standard"
                                icon="email"
                                size="sm"
                            >
                                E-mail
                            </JrButton>
                            <JrButton
                                :href="
                                    route('whatsapp.create', {
                                        company_id: company.id,
                                    })
                                "
                                variant="standard"
                                icon="chat"
                                size="sm"
                            >
                                WhatsApp
                            </JrButton>
                        </div>
                    </div>
                    <div
                        v-if="company.communication_messages.length"
                        class="mt-4 space-y-3"
                    >
                        <div
                            v-for="message in company.communication_messages"
                            :key="message.id"
                            class="rounded-2xl bg-mono-50 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-bold text-mono-900"
                                    >
                                        {{
                                            message.subject ??
                                            message.channel.label
                                        }}
                                    </p>
                                    <p class="mt-1 text-xs text-mono-600">
                                        {{ message.contact.name }} ·
                                        {{ message.to_address }}
                                    </p>
                                </div>
                                <JrBadge variant="info" size="sm">
                                    {{ message.status.label }}
                                </JrBadge>
                            </div>
                            <p
                                v-if="message.body"
                                class="mt-2 line-clamp-2 text-sm text-mono-600"
                            >
                                {{ message.body }}
                            </p>
                            <p
                                v-if="message.error_message"
                                class="mt-2 text-xs font-semibold text-error"
                            >
                                {{ message.error_message }}
                            </p>
                        </div>
                    </div>
                    <JrEmptyState
                        v-else
                        icon="chat"
                        title="Nenhuma comunicação"
                        description="Ligações, e-mails e WhatsApp aparecerão aqui vinculados à empresa."
                    />
                </JrCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
