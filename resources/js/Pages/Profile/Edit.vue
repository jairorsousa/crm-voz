<script setup lang="ts">
import JrCard from '@/Components/Jr/JrCard.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout title="Perfil">
        <JrPageHeader
            title="Perfil"
            description="Atualize seus dados de acesso, senha e preferências de segurança."
            icon="person"
        />

        <div class="space-y-6">
            <JrCard>
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </JrCard>

            <JrCard>
                <UpdatePasswordForm class="max-w-xl" />
            </JrCard>

            <JrCard>
                <DeleteUserForm class="max-w-xl" />
            </JrCard>
        </div>
    </AuthenticatedLayout>
</template>
