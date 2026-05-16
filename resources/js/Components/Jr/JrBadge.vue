<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?:
            | 'up'
            | 'down'
            | 'success'
            | 'error'
            | 'info'
            | 'primary'
            | 'neutral';
        size?: 'default' | 'sm';
    }>(),
    {
        variant: 'neutral',
        size: 'default',
    },
);

const variantClasses = computed(() => {
    return {
        up: 'bg-up-bg text-up',
        down: 'bg-down-bg text-down',
        success: 'bg-success-bg text-success',
        error: 'bg-down-bg text-error',
        info: 'bg-info-bg text-info',
        primary: 'bg-primary-100 text-primary-600',
        neutral: 'bg-mono-100 text-mono-600',
    }[props.variant];
});

const sizeClasses = computed(() =>
    props.size === 'sm' ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-xs',
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-pill font-semibold"
        :class="[variantClasses, sizeClasses]"
    >
        <slot />
    </span>
</template>
