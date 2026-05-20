<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'standard' | 'mono' | 'text' | 'danger';
        size?: 'default' | 'sm';
        type?: 'button' | 'submit' | 'reset';
        href?: string;
        icon?: string;
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'default',
        type: 'button',
        href: undefined,
        icon: undefined,
        disabled: false,
    },
);

const component = computed(() => (props.href ? Link : 'button'));

const variantClasses = computed(() => {
    return {
        primary: 'bg-primary-500 text-white shadow-sm hover:bg-primary-600',
        standard:
            'border border-mono-200 bg-transparent text-mono-900 hover:bg-mono-50',
        mono: 'bg-mono-100 text-mono-900 hover:bg-mono-200',
        text: 'bg-transparent text-mono-900 hover:bg-mono-50',
        danger: 'bg-error text-white shadow-sm hover:bg-down',
    }[props.variant];
});

const sizeClasses = computed(() => {
    return {
        default: 'h-11 px-6 text-sm',
        sm: 'h-9 px-4 text-[13px]',
    }[props.size];
});
</script>

<template>
    <component
        :is="component"
        :href="href"
        :type="href ? undefined : type"
        :disabled="disabled"
        class="jr-focus-ring inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-all duration-200 active:scale-[.97] disabled:cursor-not-allowed disabled:opacity-50"
        :class="[variantClasses, sizeClasses]"
    >
        <span v-if="icon" class="material-icons-outlined text-[18px]">
            {{ icon }}
        </span>
        <slot />
    </component>
</template>
