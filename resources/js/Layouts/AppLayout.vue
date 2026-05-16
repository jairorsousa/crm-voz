<script setup lang="ts">
import AppHeader from '@/Components/AppHeader.vue';
import AppSidebar from '@/Components/AppSidebar.vue';
import { ref } from 'vue';

withDefaults(
    defineProps<{
        title?: string;
    }>(),
    {
        title: 'Dashboard',
    },
);

const sidebarOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-mono-50">
        <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <div class="min-h-screen lg:pl-72">
            <AppHeader :title="title" @menu="sidebarOpen = true" />

            <main class="p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
