<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    name: string;
}>();

const initials = computed(() =>
    props.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill bg-primary-100 text-sm font-bold text-primary-600"
    >
        {{ initials }}
    </div>
</template>
