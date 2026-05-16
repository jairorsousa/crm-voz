<script setup lang="ts">
import JrAlert from '@/Components/Jr/JrAlert.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout
        title="Recuperar senha"
        subtitle="Informe seu e-mail para receber um link de redefinição."
    >
        <Head title="Recuperar senha" />

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

            <div class="pt-2">
                <JrButton
                    class="w-full"
                    type="submit"
                    icon="send"
                    :disabled="form.processing"
                >
                    Enviar link
                </JrButton>
            </div>
        </form>
    </GuestLayout>
</template>
