<script setup lang="ts">
withDefaults(
    defineProps<{
        padding?: boolean;
    }>(),
    {
        padding: true,
    },
);
</script>

<template>
    <section
        class="rounded-2xl border border-mono-100 bg-mono-white shadow-card"
        :class="{ 'p-6': padding }"
    >
        <slot />
    </section>
</template>
