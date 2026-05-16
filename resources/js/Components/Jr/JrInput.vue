<script setup lang="ts">
import { computed, useId } from 'vue';

const model = defineModel<string | number | null>();

const props = withDefaults(
    defineProps<{
        id?: string;
        label?: string;
        icon?: string;
        error?: string;
        helper?: string;
        type?: string;
        placeholder?: string;
        autocomplete?: string;
        required?: boolean;
        autofocus?: boolean;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        label: undefined,
        icon: undefined,
        error: undefined,
        helper: undefined,
        type: 'text',
        placeholder: undefined,
        autocomplete: undefined,
        required: false,
        autofocus: false,
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
            <input
                :id="inputId"
                v-model="model"
                :type="type"
                :placeholder="placeholder"
                :autocomplete="autocomplete"
                :required="required"
                :autofocus="autofocus"
                :disabled="disabled"
                class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-mono-900 placeholder:text-mono-300 focus:border-0 focus:ring-0 disabled:cursor-not-allowed disabled:opacity-60"
            />
            <span
                v-if="error"
                class="material-icons-outlined text-[20px] text-error"
            >
                error
            </span>
        </div>

        <p v-if="error" class="mt-2 text-xs font-medium text-error">
            {{ error }}
        </p>
        <p v-else-if="helper" class="mt-2 text-xs text-mono-600">
            {{ helper }}
        </p>
    </div>
</template>
