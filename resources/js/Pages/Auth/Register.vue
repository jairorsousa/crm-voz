<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout
        title="Criar acesso interno"
        subtitle="Novos acessos entram como SDR e podem ser ajustados por um administrador."
    >
        <Head title="Criar acesso" />

        <form class="space-y-4" @submit.prevent="submit">
            <JrInput
                id="name"
                v-model="form.name"
                label="Nome"
                icon="person"
                required
                autofocus
                autocomplete="name"
                :error="form.errors.name"
            />

            <JrInput
                id="email"
                v-model="form.email"
                label="E-mail"
                icon="email"
                type="email"
                required
                autocomplete="username"
                :error="form.errors.email"
            />

            <JrInput
                id="password"
                v-model="form.password"
                label="Senha"
                icon="lock"
                type="password"
                required
                autocomplete="new-password"
                :error="form.errors.password"
            />

            <JrInput
                id="password_confirmation"
                v-model="form.password_confirmation"
                label="Confirmar senha"
                icon="lock"
                type="password"
                required
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
            />

            <div class="flex items-center justify-between gap-4 pt-2">
                <Link
                    :href="route('login')"
                    class="text-sm font-semibold text-primary-600 hover:text-primary-500"
                >
                    Já tenho acesso
                </Link>

                <JrButton
                    type="submit"
                    icon="person_add"
                    :disabled="form.processing"
                >
                    Criar
                </JrButton>
            </div>
        </form>
    </GuestLayout>
</template>
