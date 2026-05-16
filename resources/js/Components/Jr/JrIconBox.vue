<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        icon: string;
        variant?: 'primary' | 'up' | 'down' | 'info' | 'success' | 'neutral';
    }>(),
    {
        variant: 'primary',
    },
);

const variantClasses = computed(() => {
    return {
        primary: 'bg-primary-100 text-primary-500',
        up: 'bg-up-bg text-up',
        down: 'bg-down-bg text-down',
        info: 'bg-info-bg text-info',
        success: 'bg-success-bg text-success',
        neutral: 'bg-mono-100 text-mono-600',
    }[props.variant];
});
</script>

<template>
    <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
        :class="variantClasses"
    >
        <span class="material-icons-outlined text-[22px]">{{ icon }}</span>
    </div>
</template>
