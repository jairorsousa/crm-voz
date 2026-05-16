<script setup lang="ts">
import { computed, useId } from 'vue';

type Option = {
    value: string | number | null;
    label: string;
    description?: string;
};

const model = defineModel<string | number | null>();

const props = withDefaults(
    defineProps<{
        id?: string;
        label?: string;
        icon?: string;
        error?: string;
        helper?: string;
        placeholder?: string;
        options: Option[];
        required?: boolean;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        label: undefined,
        icon: undefined,
        error: undefined,
        helper: undefined,
        placeholder: 'Selecione',
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

        <div
            class="flex h-12 items-center gap-2 rounded-pill border bg-mono-white px-4 transition-all duration-200"
            :class="
                error
                    ? 'border-error shadow-[0_0_0_3px_rgba(255,71,71,.1)]'
                    : 'border-mono-200 focus-within:border-primary-500 focus-within:shadow-[0_0_0_3px_rgba(255,111,0,.1)]'
            "
        >
            <span
                v-if="icon"
                class="material-icons-outlined text-[20px]"
                :class="error ? 'text-error' : 'text-mono-300'"
            >
                {{ icon }}
            </span>
            <select
                :id="inputId"
                v-model="model"
                :required="required"
                :disabled="disabled"
                class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-mono-900 focus:border-0 focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <option value="">{{ placeholder }}</option>
                <option
                    v-for="option in options"
                    :key="String(option.value)"
                    :value="option.value ?? ''"
                >
                    {{ option.label }}
                </option>
            </select>
        </div>

        <p v-if="error" class="mt-2 text-xs font-medium text-error">
            {{ error }}
        </p>
        <p v-else-if="helper" class="mt-2 text-xs text-mono-600">
            {{ helper }}
        </p>
    </div>
</template>
