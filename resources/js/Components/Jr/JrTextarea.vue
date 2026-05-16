<script setup lang="ts">
import { computed, useId } from 'vue';

const model = defineModel<string | null>();

const props = withDefaults(
    defineProps<{
        id?: string;
        label?: string;
        error?: string;
        helper?: string;
        placeholder?: string;
        rows?: number;
        required?: boolean;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        label: undefined,
        error: undefined,
        helper: undefined,
        placeholder: undefined,
        rows: 4,
        required: false,
        disabled: false,
    },
);

const generatedId = useId();
const inputId = computed(() => props.id ?? generatedId);
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="inputId"
            class="mb-2 block text-sm font-medium text-mono-600"
        >
            {{ label }}
        </label>
        <textarea
            :id="inputId"
            v-model="model"
            :rows="rows"
            :placeholder="placeholder"
            :required="required"
            :disabled="disabled"
            class="w-full rounded-2xl border bg-mono-white px-4 py-3 text-sm text-mono-900 placeholder:text-mono-300 focus:border-primary-500 focus:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-60"
            :class="error ? 'border-error' : 'border-mono-200'"
        />
        <p v-if="error" class="mt-2 text-xs font-medium text-error">
            {{ error }}
        </p>
        <p v-else-if="helper" class="mt-2 text-xs text-mono-600">
            {{ helper }}
        </p>
    </div>
</template>
