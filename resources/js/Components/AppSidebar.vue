<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import type { PageProps, UserRole } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    open: boolean;
}>();

defineEmits<{
    close: [];
}>();

type NavigationItem = {
    label: string;
    routeName: string;
    icon: string;
    roles: UserRole[];
};

const page = usePage<PageProps>();
const userRole = computed(() => page.props.auth.user?.role ?? 'sdr');

const navigation: NavigationItem[] = [
    {
        label: 'Dashboard',
        routeName: 'dashboard',
        icon: 'dashboard',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'Empresas',
        routeName: 'companies.index',
        icon: 'business',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'Contatos',
        routeName: 'contacts.index',
        icon: 'person',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'Pipeline',
        routeName: 'pipeline.index',
        icon: 'view_kanban',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'Oportunidades',
        routeName: 'opportunities.index',
        icon: 'payments',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'Atividades',
        routeName: 'activities.index',
        icon: 'calendar_today',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'Ligações',
        routeName: 'calls.index',
        icon: 'phone',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'E-mails',
        routeName: 'emails.index',
        icon: 'email',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'WhatsApp',
        routeName: 'whatsapp.index',
        icon: 'chat',
        roles: ['admin', 'commercial_manager', 'sdr', 'closer'],
    },
    {
        label: 'Modelos',
        routeName: 'templates.index',
        icon: 'drafts',
        roles: ['admin', 'commercial_manager'],
    },
    {
        label: 'Canais',
        routeName: 'channels.index',
        icon: 'settings_input_antenna',
        roles: ['admin', 'commercial_manager'],
    },
    {
        label: 'Automações',
        routeName: 'automations.index',
        icon: 'settings_suggest',
        roles: ['admin', 'commercial_manager'],
    },
    {
        label: 'Relatórios',
        routeName: 'reports.index',
        icon: 'bar_chart',
        roles: ['admin', 'commercial_manager'],
    },
    {
        label: 'Configurações',
        routeName: 'settings.index',
        icon: 'settings',
        roles: ['admin'],
    },
];

const visibleNavigation = computed(() =>
    navigation.filter((item) => item.roles.includes(userRole.value)),
);
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-40 bg-mono-black/40 lg:hidden"
        @click="$emit('close')"
    />

    <aside
        class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-mono-100 bg-mono-white transition-transform duration-200 lg:translate-x-0"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <div
            class="flex h-16 items-center justify-between border-b border-mono-100 px-5"
        >
            <Link :href="route('dashboard')" class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-500 text-sm font-extrabold text-white shadow-sm"
                >
                    V
                </div>
                <div>
                    <p class="text-sm font-extrabold leading-sm text-mono-900">
                        VOZ CRM
                    </p>
                    <p class="text-xs text-mono-600">Operação comercial</p>
                </div>
            </Link>

            <button
                type="button"
                class="jr-focus-ring rounded-lg p-2 text-mono-600 hover:bg-mono-50 lg:hidden"
                @click="$emit('close')"
            >
                <span class="material-icons-outlined text-[20px]">close</span>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <p
                class="px-3 pb-2 text-[11px] font-semibold uppercase text-mono-400"
            >
                Principal
            </p>
            <div class="space-y-1">
                <Link
                    v-for="item in visibleNavigation"
                    :key="item.routeName"
                    :href="route(item.routeName)"
                    class="group flex items-center gap-3 rounded-pill px-3 py-2.5 text-sm font-semibold transition-colors"
                    :class="
                        route().current(item.routeName)
                            ? 'bg-primary-100 text-primary-600'
                            : 'text-mono-600 hover:bg-mono-50 hover:text-mono-900'
                    "
                    @click="$emit('close')"
                >
                    <span class="material-icons-outlined text-[20px]">
                        {{ item.icon }}
                    </span>
                    <span class="min-w-0 flex-1 truncate">{{
                        item.label
                    }}</span>
                </Link>
            </div>
        </nav>

        <div class="border-t border-mono-100 p-4">
            <div class="rounded-2xl bg-mono-50 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-mono-600">Perfil</p>
                        <p class="mt-1 text-sm font-bold text-mono-900">
                            {{ page.props.auth.user?.role_label }}
                        </p>
                    </div>
                    <JrBadge variant="primary" size="sm">ativo</JrBadge>
                </div>
            </div>
        </div>
    </aside>
</template>
