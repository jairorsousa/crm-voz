<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <GuestLayout
        title="Confirmar senha"
        subtitle="Esta área é protegida. Confirme sua senha para continuar."
    >
        <Head title="Confirmar senha" />

        <form class="space-y-4" @submit.prevent="submit">
            <JrInput
                id="password"
                v-model="form.password"
                label="Senha"
                icon="lock"
                type="password"
                required
                autocomplete="current-password"
                autofocus
                :error="form.errors.password"
            />

            <div class="pt-2">
                <JrButton
                    class="w-full"
                    type="submit"
                    icon="check_circle"
                    :disabled="form.processing"
                >
                    Confirmar
                </JrButton>
            </div>
        </form>
    </GuestLayout>
</template>
