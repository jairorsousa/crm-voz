<script setup lang="ts">
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { CommunicationChannelFormData, Option } from '@/types/crm';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps<{
    channel: CommunicationChannelFormData | null;
    options: {
        types: Option[];
        providers: Record<string, Option[]>;
        users: Option[];
    };
}>();

const defaultType = props.channel?.type ?? 'email';
const defaultProvider =
    props.channel?.provider ??
    String(props.options.providers[defaultType]?.[0]?.value ?? 'smtp');

const form = useForm<CommunicationChannelFormData>({
    name: props.channel?.name ?? '',
    type: defaultType,
    provider: defaultProvider,
    config: {
        account_sid: props.channel?.config?.account_sid ?? '',
        auth_token: '',
        api_key: props.channel?.config?.api_key ?? '',
        api_secret: '',
        twiml_app_sid: props.channel?.config?.twiml_app_sid ?? '',
        caller_id:
            props.channel?.config?.caller_id ??
            props.channel?.config?.from_number ??
            '',
        from_number: props.channel?.config?.from_number ?? '',
        voice_webhook_url: props.channel?.config?.voice_webhook_url ?? '',
        webhook_token: '',
        url: props.channel?.config?.url ?? '',
        key: '',
        instance: props.channel?.config?.instance ?? '',
        host: props.channel?.config?.host ?? '',
        port: props.channel?.config?.port ?? 587,
        username: props.channel?.config?.username ?? '',
        password: '',
        encryption: props.channel?.config?.encryption ?? 'tls',
        from_address: props.channel?.config?.from_address ?? '',
        from_name: props.channel?.config?.from_name ?? '',
    },
    is_active: props.channel?.is_active ?? true,
    is_shared: props.channel?.is_shared ?? false,
    is_default: props.channel?.is_default ?? false,
    user_ids: props.channel?.user_ids ?? [],
});

const providerOptions = computed(() => props.options.providers[form.type] ?? []);
const isEditing = computed(() => Boolean(props.channel?.id));

watch(
    () => form.type,
    (type) => {
        form.provider = String(props.options.providers[type]?.[0]?.value ?? '');
    },
);

const toggleUser = (id: number | string | null) => {
    if (!id) return;

    const userId = Number(id);

    form.user_ids = form.user_ids.includes(userId)
        ? form.user_ids.filter((item) => item !== userId)
        : [...form.user_ids, userId];
};

const submit = () => {
    if (props.channel?.id) {
        form.put(route('channels.update', props.channel.id), {
            preserveScroll: true,
        });
        return;
    }

    form.post(route('channels.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="isEditing ? 'Editar canal' : 'Novo canal'" />

    <AuthenticatedLayout :title="isEditing ? 'Editar canal' : 'Novo canal'">
        <JrPageHeader
            :title="isEditing ? 'Editar canal' : 'Novo canal'"
            description="Configure o tipo, o provedor e os usuários que podem usar este canal."
            icon="settings_input_antenna"
        >
            <template #actions>
                <JrButton
                    :href="route('channels.index')"
                    variant="standard"
                    icon="arrow_back"
                    size="sm"
                >
                    Voltar
                </JrButton>
            </template>
        </JrPageHeader>

        <form class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]" @submit.prevent="submit">
            <div class="space-y-4">
                <JrCard>
                    <div class="grid gap-4 md:grid-cols-2">
                        <JrInput
                            v-model="form.name"
                            label="Nome do canal"
                            icon="label"
                            :error="form.errors.name"
                            required
                        />
                        <JrSelect
                            v-model="form.type"
                            label="Tipo"
                            icon="forum"
                            :options="options.types"
                            :error="form.errors.type"
                            required
                        />
                        <JrSelect
                            v-model="form.provider"
                            label="Provedor"
                            icon="hub"
                            :options="providerOptions"
                            :error="form.errors.provider"
                            required
                        />
                    </div>
                </JrCard>

                <JrCard v-if="form.provider === 'twilio'">
                    <h2 class="mb-4 text-base font-bold text-mono-900">
                        Configuração Twilio
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <JrInput
                            v-model="form.config.account_sid"
                            label="Account SID"
                            :error="form.errors['config.account_sid']"
                            required
                        />
                        <JrInput
                            v-model="form.config.auth_token"
                            label="Auth Token"
                            type="password"
                            :placeholder="isEditing ? 'Manter atual' : ''"
                            :error="form.errors['config.auth_token']"
                        />
                        <JrInput
                            v-model="form.config.api_key"
                            label="API Key SID"
                            :error="form.errors['config.api_key']"
                        />
                        <JrInput
                            v-model="form.config.api_secret"
                            label="API Secret"
                            type="password"
                            :placeholder="isEditing ? 'Manter atual' : ''"
                            :error="form.errors['config.api_secret']"
                        />
                        <JrInput
                            v-model="form.config.twiml_app_sid"
                            label="TwiML App SID"
                            :error="form.errors['config.twiml_app_sid']"
                        />
                        <JrInput
                            v-model="form.config.caller_id"
                            label="Caller ID"
                            icon="phone"
                            placeholder="+5511982403231"
                            :error="form.errors['config.caller_id']"
                            required
                        />
                        <JrInput
                            v-model="form.config.voice_webhook_url"
                            label="URL de voz"
                            icon="link"
                            :error="form.errors['config.voice_webhook_url']"
                        />
                        <JrInput
                            v-model="form.config.webhook_token"
                            label="Token de webhook"
                            type="password"
                            :placeholder="isEditing ? 'Manter atual' : ''"
                            :error="form.errors['config.webhook_token']"
                        />
                    </div>
                </JrCard>

                <JrCard v-else-if="form.provider === 'evolution'">
                    <h2 class="mb-4 text-base font-bold text-mono-900">
                        Configuração Evolution API
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <JrInput
                            v-model="form.config.url"
                            label="URL base"
                            icon="link"
                            :error="form.errors['config.url']"
                            required
                        />
                        <JrInput
                            v-model="form.config.key"
                            label="API key"
                            type="password"
                            :placeholder="isEditing ? 'Manter atual' : ''"
                            :error="form.errors['config.key']"
                        />
                        <JrInput
                            v-model="form.config.instance"
                            label="Instância"
                            icon="chat"
                            :error="form.errors['config.instance']"
                            required
                        />
                        <JrInput
                            v-model="form.config.webhook_token"
                            label="Token de webhook"
                            type="password"
                            :placeholder="isEditing ? 'Manter atual' : ''"
                            :error="form.errors['config.webhook_token']"
                        />
                    </div>
                </JrCard>

                <JrCard v-else>
                    <h2 class="mb-4 text-base font-bold text-mono-900">
                        Configuração SMTP
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <JrInput
                            v-model="form.config.host"
                            label="Host"
                            icon="dns"
                            :error="form.errors['config.host']"
                            required
                        />
                        <JrInput
                            v-model="form.config.port"
                            label="Porta"
                            type="number"
                            :error="form.errors['config.port']"
                            required
                        />
                        <JrInput
                            v-model="form.config.username"
                            label="Usuário"
                            icon="person"
                            :error="form.errors['config.username']"
                        />
                        <JrInput
                            v-model="form.config.password"
                            label="Senha"
                            type="password"
                            :placeholder="isEditing ? 'Manter atual' : ''"
                            :error="form.errors['config.password']"
                        />
                        <JrSelect
                            v-model="form.config.encryption"
                            label="Criptografia"
                            icon="lock"
                            :options="[
                                { value: 'tls', label: 'TLS' },
                                { value: 'ssl', label: 'SSL' },
                                { value: '', label: 'Nenhuma' },
                            ]"
                            :error="form.errors['config.encryption']"
                        />
                        <JrInput
                            v-model="form.config.from_address"
                            label="E-mail remetente"
                            icon="mail"
                            type="email"
                            :error="form.errors['config.from_address']"
                            required
                        />
                        <JrInput
                            v-model="form.config.from_name"
                            label="Nome do remetente"
                            icon="badge"
                            :error="form.errors['config.from_name']"
                        />
                    </div>
                </JrCard>
            </div>

            <div class="space-y-4">
                <JrCard>
                    <h2 class="text-base font-bold text-mono-900">Status</h2>
                    <div class="mt-4 space-y-3">
                        <label class="flex items-center gap-3 text-sm font-semibold text-mono-700">
                            <input v-model="form.is_active" type="checkbox" class="rounded border-mono-300 text-primary-500" />
                            Canal ativo
                        </label>
                        <label class="flex items-center gap-3 text-sm font-semibold text-mono-700">
                            <input v-model="form.is_shared" type="checkbox" class="rounded border-mono-300 text-primary-500" />
                            Compartilhado com o time
                        </label>
                        <label class="flex items-center gap-3 text-sm font-semibold text-mono-700">
                            <input v-model="form.is_default" type="checkbox" class="rounded border-mono-300 text-primary-500" />
                            Canal padrão
                        </label>
                    </div>
                </JrCard>

                <JrCard>
                    <h2 class="text-base font-bold text-mono-900">
                        Usuários com acesso
                    </h2>
                    <div class="mt-4 max-h-[420px] space-y-2 overflow-y-auto pr-1">
                        <label
                            v-for="user in options.users"
                            :key="String(user.value)"
                            class="flex cursor-pointer items-start gap-3 rounded-xl border border-mono-100 p-3 text-sm hover:bg-mono-50"
                        >
                            <input
                                type="checkbox"
                                class="mt-1 rounded border-mono-300 text-primary-500"
                                :checked="form.user_ids.includes(Number(user.value))"
                                @change="toggleUser(user.value)"
                            />
                            <span>
                                <span class="block font-bold text-mono-900">
                                    {{ user.label }}
                                </span>
                                <span class="block text-xs text-mono-500">
                                    {{ user.description }}
                                </span>
                            </span>
                        </label>
                    </div>
                </JrCard>

                <div class="flex justify-end gap-2">
                    <JrButton
                        :href="route('channels.index')"
                        variant="standard"
                        icon="close"
                    >
                        Cancelar
                    </JrButton>
                    <JrButton type="submit" icon="save" :disabled="form.processing">
                        Salvar canal
                    </JrButton>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
