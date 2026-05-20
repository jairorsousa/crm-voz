<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import JrTable from '@/Components/Jr/JrTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Paginated, ProductFormData } from '@/types/crm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    products: Paginated<ProductFormData>;
    filters: {
        search?: string;
        status?: string;
        category?: string;
    };
    categories: string[];
}>();

const form = useForm({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    category: props.filters.category ?? '',
});

const statusOptions = [
    { value: '', label: 'Todos' },
    { value: 'active', label: 'Ativos' },
    { value: 'inactive', label: 'Inativos' },
];

const categoryOptions = [
    { value: '', label: 'Todas' },
    ...props.categories.map((category) => ({
        value: category,
        label: category,
    })),
];

const submit = () => {
    form.get(route('products.index'), {
        preserveScroll: true,
        preserveState: true,
    });
};

const clearFilters = () => {
    form.search = '';
    form.status = '';
    form.category = '';
    submit();
};

const toggleProduct = (product: ProductFormData) => {
    router.patch(
        route('products.toggle', product.id),
        {},
        { preserveScroll: true },
    );
};

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Anterior')
        .replace('Next &raquo;', 'Próxima');
</script>

<template>
    <Head title="Produtos" />

    <AuthenticatedLayout title="Produtos">
        <JrPageHeader
            title="Produtos"
            description="Gerencie os produtos e serviços comerciais usados nas oportunidades."
            icon="inventory_2"
        >
            <template #actions>
                <JrButton :href="route('products.create')" icon="add" size="sm">
                    Novo produto
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard class="mb-5">
            <form class="grid gap-4 lg:grid-cols-4" @submit.prevent="submit">
                <JrInput
                    v-model="form.search"
                    label="Busca"
                    icon="search"
                    placeholder="Nome, categoria ou descrição"
                />
                <JrSelect
                    v-model="form.category"
                    label="Categoria"
                    icon="category"
                    :options="categoryOptions"
                />
                <JrSelect
                    v-model="form.status"
                    label="Status"
                    icon="toggle_on"
                    :options="statusOptions"
                />
                <div class="flex items-end gap-2">
                    <JrButton type="submit" icon="filter_alt" size="sm">
                        Filtrar
                    </JrButton>
                    <JrButton
                        type="button"
                        icon="backspace"
                        variant="standard"
                        size="sm"
                        @click="clearFilters"
                    >
                        Limpar
                    </JrButton>
                </div>
            </form>
        </JrCard>

        <JrCard>
            <JrTable v-if="products.data.length">
                <template #head>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500">
                            Produto
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500">
                            Categoria
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500">
                            Preço base
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-mono-500">
                            Oportunidades
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-bold uppercase text-mono-500">
                            Ações
                        </th>
                    </tr>
                </template>

                <tr v-for="product in products.data" :key="product.id">
                    <td class="px-4 py-3 align-top">
                        <p class="text-sm font-bold text-mono-900">
                            {{ product.name }}
                        </p>
                        <p v-if="product.description" class="mt-1 max-w-xl text-xs text-mono-500">
                            {{ product.description }}
                        </p>
                        <JrBadge
                            class="mt-2"
                            :variant="product.is_active ? 'success' : 'neutral'"
                        >
                            {{ product.is_active ? 'Ativo' : 'Inativo' }}
                        </JrBadge>
                    </td>
                    <td class="px-4 py-3 text-sm text-mono-700">
                        {{ product.category ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm font-semibold text-mono-900">
                        {{ product.formatted_base_price ?? '-' }}
                    </td>
                    <td class="px-4 py-3 text-sm text-mono-700">
                        {{ product.opportunities_count ?? 0 }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <JrButton
                                :href="route('products.edit', product.id)"
                                icon="edit"
                                variant="standard"
                                size="sm"
                            >
                                Editar
                            </JrButton>
                            <JrButton
                                type="button"
                                :icon="product.is_active ? 'toggle_off' : 'toggle_on'"
                                variant="standard"
                                size="sm"
                                @click="toggleProduct(product)"
                            >
                                {{ product.is_active ? 'Inativar' : 'Ativar' }}
                            </JrButton>
                        </div>
                    </td>
                </tr>
            </JrTable>

            <JrEmptyState
                v-else
                icon="inventory_2"
                title="Nenhum produto encontrado"
                description="Cadastre os produtos e serviços vendidos pelo time comercial."
            />
        </JrCard>

        <div v-if="products.links.length > 3" class="mt-4 flex flex-wrap gap-2">
            <Link
                v-for="link in products.links"
                :key="link.label"
                :href="link.url ?? '#'"
                class="rounded-lg border px-3 py-2 text-sm font-semibold"
                :class="[
                    link.active
                        ? 'border-primary-500 bg-primary-500 text-white'
                        : 'border-mono-200 bg-white text-mono-600',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
            >
                {{ paginationLabel(link.label) }}
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
