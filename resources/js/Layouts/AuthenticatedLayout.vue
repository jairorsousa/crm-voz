<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';

withDefaults(
    defineProps<{
        title?: string;
    }>(),
    {
        title: 'Dashboard',
    },
);
</script>

<template>
    <AppLayout :title="title">
        <slot />
    </AppLayout>
</template>
