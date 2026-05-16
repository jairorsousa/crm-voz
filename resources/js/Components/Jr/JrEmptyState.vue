<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';

defineProps<{
    icon: string;
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
}>();
</script>

<template>
    <div
        class="rounded-3xl border border-dashed border-mono-200 bg-mono-white p-8 text-center"
    >
        <div
            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-500"
        >
            <span class="material-icons-outlined text-[32px]">{{ icon }}</span>
        </div>
        <h2 class="mt-4 text-base font-bold text-mono-900">{{ title }}</h2>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-lg text-mono-600">
            {{ description }}
        </p>
        <div v-if="actionLabel && actionHref" class="mt-5">
            <JrButton :href="actionHref" icon="add">{{ actionLabel }}</JrButton>
        </div>
    </div>
</template>
