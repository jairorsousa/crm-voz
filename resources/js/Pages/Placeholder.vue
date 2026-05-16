<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrEmptyState from '@/Components/Jr/JrEmptyState.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    title: string;
    description: string;
    icon: string;
}>();
</script>

<template>
    <Head :title="title" />

    <AuthenticatedLayout :title="title">
        <JrPageHeader :title="title" :description="description" :icon="icon">
            <template #actions>
                <JrButton
                    href="/dashboard"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Dashboard
                </JrButton>
            </template>
        </JrPageHeader>

        <JrCard>
            <JrEmptyState
                :icon="icon"
                :title="`${title} preparado`"
                description="Esta tela já está conectada ao layout, à autenticação e às policies iniciais. A implementação funcional entra nas próximas etapas do plano."
            />
        </JrCard>
    </AuthenticatedLayout>
</template>
