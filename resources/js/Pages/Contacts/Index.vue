<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTable from '@/Components/Jr/JrTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ContactListItem, CrmOptions, Paginated } from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    contacts: Paginated<ContactListItem>;
    filters: {
        search?: string;
        company_id?: string;
        type?: string;
    };
    options: CrmOptions;
}>();

const form = useForm({
    search: props.filters.search ?? '',
    company_id: props.filters.company_id ?? '',
    type: props.filters.type ?? '',
});

const submit = () => {
    form.get(route('contacts.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.company_id = '';
    form.type = '';
    submit();
};

const destroyContact = (contact: ContactListItem) => {
    if (!confirm(`Remover ${contact.name}?`)) {
        return;
    }

    router.delete(route('contacts.destroy', contact.id), {
        preserveScroll: true,
    });
};

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Anterior')
        .replace('Next &raquo;', 'Próxima');
</script>

<template>
    <Head title="Contatos" />

    <AuthenticatedLayout title="Contatos">
        <JrPageHeader
            title="Contatos"
            description="Decisores, influenciadores e responsáveis sempre vinculados a uma empresa."
            icon="person"
        >
            <template #actions>
                <JrButton :href="route('contacts.create')" icon="add" size="sm">
                    Novo contato
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard class="mb-5">
            <form class="grid gap-3 lg:grid-cols-4" @submit.prevent="submit">
                <JrInput
                    v-model="form.search"
                    class="lg:col-span-2"
                    icon="search"
                    label="Busca"
                    placeholder="Nome, empresa, e-mail, telefone ou cargo"
                />
                <JrSelect
                    v-model="form.company_id"
                    label="Empresa"
                    icon="business"
                    :options="options.companies ?? []"
                    placeholder="Todas"
                />
                <JrSelect
                    v-model="form.type"
                    label="Tipo"
                    icon="badge"
                    :options="options.contactTypes"
                    placeholder="Todos"
                />
                <div class="flex items-end gap-2 lg:col-span-4">
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

        <JrTable>
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
                        Empresa
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

            <tr v-for="contact in contacts.data" :key="contact.id">
                <td class="px-4 py-4">
                    <p class="font-bold text-mono-900">{{ contact.name }}</p>
                    <p class="mt-1 text-xs text-mono-600">
                        {{ contact.position ?? 'Cargo não informado' }}
                        <span v-if="contact.department">
                            · {{ contact.department }}</span
                        >
                    </p>
                </td>
                <td class="px-4 py-4">
                    <Link
                        :href="route('companies.show', contact.company.id)"
                        class="text-sm font-bold text-mono-900 hover:text-primary-500"
                    >
                        {{ contact.company.display_name }}
                    </Link>
                    <p class="mt-1 text-xs text-mono-600">
                        {{ contact.company.formatted_cnpj }}
                    </p>
                </td>
                <td class="px-4 py-4">
                    <div class="flex flex-wrap gap-1.5">
                        <JrBadge variant="info" size="sm">
                            {{ contact.type.label }}
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
                            :href="route('contacts.edit', contact.id)"
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
                            @click="destroyContact(contact)"
                        >
                            Excluir
                        </JrButton>
                    </div>
                </td>
            </tr>

            <tr v-if="contacts.data.length === 0">
                <td
                    colspan="5"
                    class="px-4 py-10 text-center text-sm text-mono-600"
                >
                    Nenhum contato encontrado.
                </td>
            </tr>
        </JrTable>

        <div class="mt-5 flex flex-wrap gap-2">
            <Link
                v-for="link in contacts.links"
                :key="link.label"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-2 text-sm font-semibold"
                :class="[
                    link.active
                        ? 'bg-primary-500 text-white'
                        : 'bg-mono-white text-mono-600 hover:bg-mono-100',
                    !link.url ? 'pointer-events-none opacity-40' : '',
                ]"
                preserve-scroll
            >
                {{ paginationLabel(link.label) }}
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
