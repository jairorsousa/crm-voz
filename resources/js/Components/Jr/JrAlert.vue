<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'success' | 'error' | 'info';
        dismissible?: boolean;
    }>(),
    {
        variant: 'info',
        dismissible: true,
    },
);

const visible = ref(true);

const variantClasses = computed(() => {
    return {
        success: 'border-success/20 bg-success-bg text-success',
        error: 'border-error/20 bg-down-bg text-error',
        info: 'border-info/20 bg-info-bg text-info',
    }[props.variant];
});

const icon = computed(() => {
    return {
        success: 'check_circle',
        error: 'error',
        info: 'info',
    }[props.variant];
});
</script>

<template>
    <div
        v-if="visible"
        class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-medium"
        :class="variantClasses"
    >
        <span class="material-icons-outlined mt-0.5 text-[20px]">
            {{ icon }}
        </span>
        <div class="min-w-0 flex-1">
            <slot />
        </div>
        <button
            v-if="dismissible"
            type="button"
            class="jr-focus-ring rounded-lg p-1"
            @click="visible = false"
        >
            <span class="material-icons-outlined text-[18px]">close</span>
        </button>
    </div>
</template>
