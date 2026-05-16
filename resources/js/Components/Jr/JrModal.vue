<script setup lang="ts">
defineProps<{
    show: boolean;
    title?: string;
}>();

defineEmits<{
    close: [];
}>();
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-modal flex items-center justify-center bg-mono-black/40 p-4"
                @click.self="$emit('close')"
            >
                <section
                    class="w-full max-w-lg overflow-hidden rounded-2xl border border-mono-100 bg-mono-white shadow-elevated"
                >
                    <header
                        v-if="title || $slots.header"
                        class="flex items-center justify-between border-b border-mono-100 px-6 py-4"
                    >
                        <slot name="header">
                            <h2 class="text-lg font-bold text-mono-900">
                                {{ title }}
                            </h2>
                        </slot>
                        <button
                            type="button"
                            class="jr-focus-ring rounded-lg p-1 text-mono-600 hover:bg-mono-50"
                            @click="$emit('close')"
                        >
                            <span class="material-icons-outlined text-[20px]">
                                close
                            </span>
                        </button>
                    </header>
                    <div class="px-6 py-5">
                        <slot />
                    </div>
                    <footer
                        v-if="$slots.footer"
                        class="border-t border-mono-100 px-6 py-4"
                    >
                        <slot name="footer" />
                    </footer>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>
