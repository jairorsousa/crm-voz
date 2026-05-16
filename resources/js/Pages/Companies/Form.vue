<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CompanyFormData, CrmOptions } from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    mode: 'create' | 'edit';
    company: CompanyFormData | null;
    options: CrmOptions;
}>();

const isEdit = computed(() => props.mode === 'edit' && props.company);
const title = computed(() =>
    isEdit.value ? 'Editar empresa' : 'Nova empresa',
);

const form = useForm<CompanyFormData>({
    legal_name: props.company?.legal_name ?? '',
    trade_name: props.company?.trade_name ?? '',
    cnpj: props.company?.cnpj ?? '',
    segment: props.company?.segment ?? '',
    site: props.company?.site ?? '',
    phone: props.company?.phone ?? '',
    email: props.company?.email ?? '',
    whatsapp: props.company?.whatsapp ?? '',
    city: props.company?.city ?? '',
    state: props.company?.state ?? '',
    address: props.company?.address ?? '',
    status: props.company?.status ?? 'new_lead',
    lead_source: props.company?.lead_source ?? '',
    responsible_user_id: props.company?.responsible_user_id ?? '',
    average_collection_ticket: props.company?.average_collection_ticket ?? '',
    overdue_customers_count: props.company?.overdue_customers_count ?? '',
    total_default_amount: props.company?.total_default_amount ?? '',
    approx_customers_count: props.company?.approx_customers_count ?? '',
    current_system: props.company?.current_system ?? '',
    has_internal_collection_team:
        props.company?.has_internal_collection_team ?? false,
    has_erp_integration: props.company?.has_erp_integration ?? false,
    portfolio_notes: props.company?.portfolio_notes ?? '',
    company_type: props.company?.company_type ?? '',
    company_size: props.company?.company_size ?? '',
    commercial_potential: props.company?.commercial_potential ?? '',
    lead_temperature: props.company?.lead_temperature ?? 'cold',
    priority: props.company?.priority ?? 'medium',
    pain_profile: props.company?.pain_profile ?? '',
    closing_probability: props.company?.closing_probability ?? 0,
});

const submit = () => {
    if (isEdit.value && props.company?.id) {
        form.put(route('companies.update', props.company.id));
        return;
    }

    form.post(route('companies.store'));
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :title="title">
        <JrPageHeader
            :title="title"
            description="Preencha os dados centrais da empresa, carteira e classificação comercial."
            icon="business"
        >
            <template #actions>
                <JrButton
                    :href="route('companies.index')"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Empresas
                </JrButton>
            </template>
        </JrPageHeader>

        <form class="space-y-5" @submit.prevent="submit">
            <JrCard>
                <h2 class="text-base font-bold text-mono-900">Dados básicos</h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    <JrInput
                        v-model="form.legal_name"
                        class="lg:col-span-2"
                        label="Razão social"
                        icon="business"
                        required
                        :error="form.errors.legal_name"
                    />
                    <JrInput
                        v-model="form.trade_name"
                        label="Nome fantasia"
                        icon="storefront"
                        :error="form.errors.trade_name"
                    />
                    <JrInput
                        v-model="form.cnpj"
                        label="CNPJ"
                        icon="badge"
                        required
                        placeholder="00.000.000/0000-00"
                        :error="form.errors.cnpj"
                    />
                    <JrInput
                        v-model="form.segment"
                        label="Segmento"
                        icon="category"
                        :error="form.errors.segment"
                    />
                    <JrInput
                        v-model="form.site"
                        label="Site"
                        icon="public"
                        placeholder="https://"
                        :error="form.errors.site"
                    />
                    <JrInput
                        v-model="form.phone"
                        label="Telefone principal"
                        icon="phone"
                        :error="form.errors.phone"
                    />
                    <JrInput
                        v-model="form.email"
                        label="E-mail principal"
                        icon="email"
                        type="email"
                        :error="form.errors.email"
                    />
                    <JrInput
                        v-model="form.whatsapp"
                        label="WhatsApp principal"
                        icon="chat"
                        :error="form.errors.whatsapp"
                    />
                    <JrInput
                        v-model="form.city"
                        label="Cidade"
                        icon="location_city"
                        :error="form.errors.city"
                    />
                    <JrInput
                        v-model="form.state"
                        label="UF"
                        icon="map"
                        :error="form.errors.state"
                    />
                    <JrInput
                        v-model="form.address"
                        class="lg:col-span-2"
                        label="Endereço"
                        icon="location_on"
                        :error="form.errors.address"
                    />
                </div>
            </JrCard>

            <JrCard>
                <h2 class="text-base font-bold text-mono-900">
                    Classificação comercial
                </h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-4">
                    <JrSelect
                        v-model="form.status"
                        label="Status"
                        icon="flag"
                        required
                        :options="options.companyStatuses"
                        :error="form.errors.status"
                    />
                    <JrInput
                        v-model="form.lead_source"
                        label="Origem do lead"
                        icon="conversion_path"
                        :error="form.errors.lead_source"
                    />
                    <JrSelect
                        v-model="form.responsible_user_id"
                        label="Responsável"
                        icon="person"
                        :options="options.users ?? []"
                        :error="form.errors.responsible_user_id"
                    />
                    <JrSelect
                        v-model="form.lead_temperature"
                        label="Temperatura"
                        icon="local_fire_department"
                        required
                        :options="options.leadTemperatures"
                        :error="form.errors.lead_temperature"
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
                        v-model="form.company_type"
                        label="Tipo de empresa"
                        icon="domain"
                        :options="options.companyTypes"
                        :error="form.errors.company_type"
                    />
                    <JrSelect
                        v-model="form.company_size"
                        label="Porte"
                        icon="groups"
                        :options="options.companySizes"
                        :error="form.errors.company_size"
                    />
                    <JrInput
                        v-model="form.closing_probability"
                        label="Probabilidade (%)"
                        icon="percent"
                        type="number"
                        :error="form.errors.closing_probability"
                    />
                    <JrInput
                        v-model="form.commercial_potential"
                        label="Potencial comercial"
                        icon="trending_up"
                        :error="form.errors.commercial_potential"
                    />
                    <JrInput
                        v-model="form.pain_profile"
                        class="lg:col-span-3"
                        label="Perfil de dor"
                        icon="info"
                        :error="form.errors.pain_profile"
                    />
                </div>
            </JrCard>

            <JrCard>
                <h2 class="text-base font-bold text-mono-900">
                    Carteira e cobrança
                </h2>
                <div class="mt-5 grid gap-4 lg:grid-cols-4">
                    <JrInput
                        v-model="form.average_collection_ticket"
                        label="Ticket médio"
                        icon="payments"
                        type="number"
                        :error="form.errors.average_collection_ticket"
                    />
                    <JrInput
                        v-model="form.overdue_customers_count"
                        label="Clientes inadimplentes"
                        icon="group"
                        type="number"
                        :error="form.errors.overdue_customers_count"
                    />
                    <JrInput
                        v-model="form.total_default_amount"
                        label="Valor total inadimplência"
                        icon="account_balance"
                        type="number"
                        :error="form.errors.total_default_amount"
                    />
                    <JrInput
                        v-model="form.approx_customers_count"
                        label="Quantidade aproximada de clientes"
                        icon="groups"
                        type="number"
                        :error="form.errors.approx_customers_count"
                    />
                    <JrInput
                        v-model="form.current_system"
                        class="lg:col-span-2"
                        label="Sistema utilizado"
                        icon="dns"
                        :error="form.errors.current_system"
                    />
                    <label
                        class="flex min-h-12 items-center gap-3 rounded-2xl border border-mono-100 bg-mono-50 px-4 text-sm font-semibold text-mono-600"
                    >
                        <input
                            v-model="form.has_internal_collection_team"
                            type="checkbox"
                            class="rounded border-mono-200 text-primary-500 focus:ring-primary-500"
                        />
                        Possui equipe interna de cobrança
                    </label>
                    <label
                        class="flex min-h-12 items-center gap-3 rounded-2xl border border-mono-100 bg-mono-50 px-4 text-sm font-semibold text-mono-600"
                    >
                        <input
                            v-model="form.has_erp_integration"
                            type="checkbox"
                            class="rounded border-mono-200 text-primary-500 focus:ring-primary-500"
                        />
                        Possui integração com ERP
                    </label>
                    <JrTextarea
                        v-model="form.portfolio_notes"
                        class="lg:col-span-4"
                        label="Observações da carteira"
                        :error="form.errors.portfolio_notes"
                    />
                </div>
            </JrCard>

            <div class="flex justify-end gap-2">
                <JrButton :href="route('companies.index')" variant="standard">
                    Cancelar
                </JrButton>
                <JrButton
                    type="submit"
                    icon="check_circle"
                    :disabled="form.processing"
                >
                    {{ isEdit ? 'Salvar alterações' : 'Cadastrar empresa' }}
                </JrButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
