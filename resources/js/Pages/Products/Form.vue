<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrTextarea from '@/Components/Jr/JrTextarea.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ProductFormData } from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    mode: 'create' | 'edit';
    product: ProductFormData | null;
}>();

const title = computed(() =>
    props.mode === 'create' ? 'Novo produto' : 'Editar produto',
);

const slugify = (value: string) =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

const form = useForm<ProductFormData>({
    name: props.product?.name ?? '',
    slug: props.product?.slug ?? '',
    category: props.product?.category ?? '',
    description: props.product?.description ?? '',
    base_price: props.product?.base_price ?? '',
    is_active: props.product?.is_active ?? true,
    sort_order: props.product?.sort_order ?? 0,
});

watch(
    () => form.name,
    (name) => {
        if (props.mode === 'create') {
            form.slug = slugify(name);
        }
    },
);

const submit = () => {
    if (props.mode === 'create') {
        form.post(route('products.store'));
        return;
    }

    form.put(route('products.update', props.product?.id));
};
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout title="Produtos">
        <JrPageHeader
            :title="title"
            description="Configure produtos e serviços que podem ser vinculados às oportunidades."
            icon="inventory_2"
        >
            <template #actions>
                <JrButton
                    :href="route('products.index')"
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
                <JrInput
                    v-model="form.name"
                    label="Nome"
                    icon="inventory_2"
                    :error="form.errors.name"
                    required
                    autofocus
                />
                <JrInput
                    v-model="form.slug"
                    label="Identificador"
                    icon="link"
                    :error="form.errors.slug"
                    required
                />
                <JrInput
                    v-model="form.category"
                    label="Categoria"
                    icon="category"
                    :error="form.errors.category"
                />
                <JrInput
                    v-model="form.base_price"
                    label="Preço base"
                    icon="attach_money"
                    type="number"
                    :error="form.errors.base_price"
                />
                <JrInput
                    v-model="form.sort_order"
                    label="Ordem"
                    icon="sort"
                    type="number"
                    :error="form.errors.sort_order"
                />
                <label
                    class="flex items-center gap-2 rounded-lg border border-mono-100 p-4 text-sm font-semibold text-mono-700"
                >
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-mono-300 text-primary-500"
                    />
                    Produto ativo
                </label>
                <JrTextarea
                    v-model="form.description"
                    class="lg:col-span-2"
                    label="Descrição"
                    :rows="8"
                    :error="form.errors.description"
                />
                <div class="flex justify-end gap-2 lg:col-span-2">
                    <JrButton
                        :href="route('products.index')"
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
                        Salvar produto
                    </JrButton>
                </div>
            </form>
        </JrCard>
    </AuthenticatedLayout>
</template>
