<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import JrAlert from '@/Components/Jr/JrAlert.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout
        title="Entrar no VOZ CRM"
        subtitle="Use seu acesso interno para continuar a operação comercial."
    >
        <Head title="Entrar" />

        <JrAlert v-if="status" class="mb-4" variant="success">
            {{ status }}
        </JrAlert>

        <form class="space-y-4" @submit.prevent="submit">
            <JrInput
                id="email"
                v-model="form.email"
                label="E-mail"
                icon="email"
                type="email"
                required
                autofocus
                autocomplete="username"
                placeholder="voce@empresa.com"
                :error="form.errors.email"
            />

            <JrInput
                id="password"
                v-model="form.password"
                label="Senha"
                icon="lock"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Sua senha"
                :error="form.errors.password"
            />

            <div class="flex items-center justify-between gap-4">
                <label class="flex items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-sm text-mono-600">Manter conectado</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm font-semibold text-primary-600 hover:text-primary-500"
                >
                    Esqueci a senha
                </Link>
            </div>

            <div class="pt-2">
                <JrButton
                    class="w-full"
                    type="submit"
                    icon="login"
                    :disabled="form.processing"
                >
                    Entrar
                </JrButton>
            </div>
        </form>
    </GuestLayout>
</template>
