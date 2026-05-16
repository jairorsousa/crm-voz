<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        title?: string;
        subtitle?: string;
    }>(),
    {
        title: 'Acesse o VOZ CRM',
        subtitle: 'Centralize a operação comercial B2B em um fluxo seguro.',
    },
);
</script>

<template>
    <div
        class="grid min-h-screen bg-mono-50 lg:grid-cols-[minmax(0,1fr)_520px]"
    >
        <div
            class="hidden min-h-screen flex-col justify-between bg-mono-white p-10 lg:flex"
        >
            <Link :href="route('login')" class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-500 text-base font-extrabold text-white shadow-sm"
                >
                    V
                </div>
                <div>
                    <p
                        class="text-base font-extrabold leading-sm text-mono-900"
                    >
                        VOZ CRM
                    </p>
                    <p class="text-sm text-mono-600">Sistema comercial B2B</p>
                </div>
            </Link>

            <div class="max-w-xl">
                <div
                    class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-500"
                >
                    <span class="material-icons-outlined text-[32px]">
                        business_center
                    </span>
                </div>
                <h1 class="text-2xl font-extrabold leading-sm text-mono-900">
                    Relacionamento, pipeline e comunicação em uma única
                    operação.
                </h1>
                <p class="mt-4 text-base leading-lg text-mono-600">
                    Uma base moderna para SDRs, closers e gestão comercial
                    acompanharem empresas, contatos e oportunidades com clareza.
                </p>
            </div>

            <p class="text-xs text-mono-600">
                © {{ new Date().getFullYear() }} VOZ CRM
            </p>
        </div>

        <div
            class="flex min-h-screen items-center justify-center px-4 py-8 sm:px-6"
        >
            <div class="w-full max-w-md">
                <div class="mb-8 text-center lg:hidden">
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary-500 text-base font-extrabold text-white shadow-sm"
                    >
                        V
                    </div>
                    <p class="mt-3 text-base font-extrabold text-mono-900">
                        VOZ CRM
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-mono-100 bg-mono-white p-6 shadow-card sm:p-8"
                >
                    <div class="mb-6">
                        <h1 class="text-xl font-bold leading-sm text-mono-900">
                            {{ title }}
                        </h1>
                        <p class="mt-2 text-sm leading-lg text-mono-600">
                            {{ subtitle }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
