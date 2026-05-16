<script setup lang="ts">
import JrCard from '@/Components/Jr/JrCard.vue';
import JrIconBox from '@/Components/Jr/JrIconBox.vue';

withDefaults(
    defineProps<{
        label: string;
        value: string;
        icon: string;
        variant?: 'primary' | 'up' | 'down' | 'info' | 'success' | 'neutral';
        helper?: string;
    }>(),
    {
        variant: 'primary',
        helper: undefined,
    },
);
</script>

<template>
    <JrCard>
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-medium text-mono-600">{{ label }}</p>
                <p class="mt-2 text-2xl font-bold leading-sm text-mono-900">
                    {{ value }}
                </p>
                <p v-if="helper" class="mt-2 text-xs text-mono-600">
                    {{ helper }}
                </p>
            </div>
            <JrIconBox :icon="icon" :variant="variant" />
        </div>
    </JrCard>
</template>
