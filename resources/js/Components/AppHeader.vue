<script setup lang="ts">
import JrAvatar from '@/Components/Jr/JrAvatar.vue';
import JrDropdown from '@/Components/Jr/JrDropdown.vue';
import { useTheme } from '@/Composables/useTheme';
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    title: string;
}>();

defineEmits<{
    menu: [];
}>();

const page = usePage<PageProps>();
const { theme, toggleTheme } = useTheme();
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-mono-100 bg-mono-white/95 px-4 backdrop-blur sm:px-6"
    >
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="jr-focus-ring rounded-lg p-2 text-mono-600 hover:bg-mono-50 lg:hidden"
                @click="$emit('menu')"
            >
                <span class="material-icons-outlined text-[22px]">menu</span>
            </button>

            <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase text-mono-400">
                    VOZ CRM
                </p>
                <h1 class="truncate text-xl font-bold leading-sm text-mono-900">
                    {{ title }}
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                class="jr-focus-ring flex h-10 w-10 items-center justify-center rounded-lg text-mono-600 hover:bg-mono-50"
                :title="
                    theme === 'dark'
                        ? 'Ativar tema claro'
                        : 'Ativar tema escuro'
                "
                @click="toggleTheme"
            >
                <span class="material-icons-outlined text-[22px]">
                    {{ theme === 'dark' ? 'light_mode' : 'dark_mode' }}
                </span>
            </button>

            <JrDropdown>
                <template #trigger>
                    <button
                        type="button"
                        class="jr-focus-ring flex items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-mono-50"
                    >
                        <JrAvatar :name="page.props.auth.user?.name ?? 'VOZ'" />
                        <span class="hidden text-left sm:block">
                            <span
                                class="block text-sm font-bold leading-sm text-mono-900"
                            >
                                {{ page.props.auth.user?.name }}
                            </span>
                            <span class="block text-xs text-mono-600">
                                {{ page.props.auth.user?.role_label }}
                            </span>
                        </span>
                        <span
                            class="material-icons-outlined text-[18px] text-mono-600"
                        >
                            expand_more
                        </span>
                    </button>
                </template>

                <template #content>
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-mono-900 hover:bg-mono-50"
                    >
                        <span class="material-icons-outlined text-[18px]">
                            person
                        </span>
                        Perfil
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-semibold text-error hover:bg-down-bg"
                    >
                        <span class="material-icons-outlined text-[18px]">
                            logout
                        </span>
                        Sair
                    </Link>
                </template>
            </JrDropdown>
        </div>
    </header>
</template>
