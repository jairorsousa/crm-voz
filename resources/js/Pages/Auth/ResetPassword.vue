<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout
        title="Criar nova senha"
        subtitle="Defina uma senha segura para voltar ao VOZ CRM."
    >
        <Head title="Nova senha" />

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

            <div class="pt-2">
                <JrButton
                    class="w-full"
                    type="submit"
                    icon="check_circle"
                    :disabled="form.processing"
                >
                    Redefinir senha
                </JrButton>
            </div>
        </form>
    </GuestLayout>
</template>
