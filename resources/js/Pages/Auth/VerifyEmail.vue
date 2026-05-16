<script setup lang="ts">
import { computed } from 'vue';
import JrAlert from '@/Components/Jr/JrAlert.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout
        title="Verificar e-mail"
        subtitle="Confirme seu endereço para ativar o acesso ao VOZ CRM."
    >
        <Head title="Verificar e-mail" />

        <p class="mb-4 text-sm leading-lg text-mono-600">
            Enviamos um link de verificação para o e-mail cadastrado. Se ele não
            chegou, você pode solicitar um novo envio.
        </p>

        <JrAlert v-if="verificationLinkSent" class="mb-4" variant="success">
            Um novo link de verificação foi enviado para seu e-mail.
        </JrAlert>

        <form @submit.prevent="submit">
            <div
                class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <JrButton type="submit" icon="send" :disabled="form.processing">
                    Reenviar e-mail
                </JrButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-semibold text-mono-600 hover:text-mono-900"
                >
                    Sair
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
