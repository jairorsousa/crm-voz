<script setup lang="ts">
defineProps<{
    title: string;
    description?: string;
    icon?: string;
}>();
</script>

<template>
    <div
        class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div class="flex min-w-0 items-center gap-3">
            <div
                v-if="icon"
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-500"
            >
                <span class="material-icons-outlined text-[24px]">
                    {{ icon }}
                </span>
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-bold leading-sm text-mono-900">
                    {{ title }}
                </h1>
                <p v-if="description" class="mt-1 text-sm text-mono-600">
                    {{ description }}
                </p>
            </div>
        </div>
        <div v-if="$slots.actions" class="flex items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
