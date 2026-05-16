<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';

const open = ref(false);

const closeOnEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));
</script>

<template>
    <div class="relative">
        <div @click="open = !open">
            <slot name="trigger" />
        </div>

        <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 z-dropdown mt-2 w-56 origin-top-right rounded-2xl border border-mono-100 bg-mono-white p-2 shadow-dropdown"
                @click="open = false"
            >
                <slot name="content" />
            </div>
        </Transition>
    </div>
</template>
