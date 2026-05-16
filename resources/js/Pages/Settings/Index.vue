<script setup lang="ts">
import JrBadge from '@/Components/Jr/JrBadge.vue';
import JrButton from '@/Components/Jr/JrButton.vue';
import JrCard from '@/Components/Jr/JrCard.vue';
import JrInput from '@/Components/Jr/JrInput.vue';
import JrPageHeader from '@/Components/Jr/JrPageHeader.vue';
import JrSelect from '@/Components/Jr/JrSelect.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type {
    EnumValue,
    Option,
    SettingsOptionGroup,
    SettingsPipelineStage,
    SettingsUser,
} from '@/types/crm';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps<{
    settings: {
        voz: {
            name: string;
            document: string | null;
            site: string | null;
            email: string | null;
            phone: string | null;
            address: string | null;
        };
        integrations: {
            twilio: Record<string, string | null>;
            evolution: Record<string, string | null>;
            mail: Record<string, string | null>;
        };
    };
    users: SettingsUser[];
    roles: Option[];
    pipelineStages: SettingsPipelineStage[];
    optionGroups: SettingsOptionGroup[];
}>();

const generalForm = useForm({
    name: props.settings.voz.name ?? '',
    document: props.settings.voz.document ?? '',
    site: props.settings.voz.site ?? '',
    email: props.settings.voz.email ?? '',
    phone: props.settings.voz.phone ?? '',
    address: props.settings.voz.address ?? '',
});

const optionForm = useForm({
    group: props.optionGroups[0]?.key ?? 'lost_reasons',
    label: '',
    color: '#FF6F00',
});

const userRoles = reactive<Record<number, string>>(
    Object.fromEntries(
        props.users.map((user) => [user.id, user.role.value ?? 'sdr']),
    ),
);

const stageForms = reactive<Record<number, SettingsPipelineStage>>(
    Object.fromEntries(
        props.pipelineStages.map((stage) => [stage.id, { ...stage }]),
    ),
);

const optionForms = reactive<
    Record<
        number,
        {
            label: string;
            color: string | null;
            position: number;
            is_active: boolean;
        }
    >
>(
    Object.fromEntries(
        props.optionGroups.flatMap((group) =>
            group.items.map((item) => [
                item.id,
                {
                    label: item.label,
                    color: item.color,
                    position: item.position,
                    is_active: item.is_active,
                },
            ]),
        ),
    ),
);

const updateGeneral = () => {
    generalForm.patch(route('settings.general.update'), {
        preserveScroll: true,
    });
};

const updateUserRole = (user: SettingsUser) => {
    router.patch(
        route('settings.users.update', user.id),
        { role: userRoles[user.id] },
        { preserveScroll: true },
    );
};

const updateStage = (stage: SettingsPipelineStage) => {
    router.patch(route('settings.stages.update', stage.id), stageForms[stage.id], {
        preserveScroll: true,
    });
};

const storeOption = () => {
    optionForm.post(route('settings.options.store'), {
        preserveScroll: true,
        onSuccess: () => {
            optionForm.label = '';
            optionForm.color = '#FF6F00';
        },
    });
};

const updateOption = (id: number) => {
    router.patch(route('settings.options.update', id), optionForms[id], {
        preserveScroll: true,
    });
};

const roleLabel = (role: EnumValue) => role.label ?? role.value;
</script>

<template>
    <Head title="Configurações" />

    <AuthenticatedLayout title="Configurações">
        <JrPageHeader
            title="Configurações"
            description="Ajustes operacionais para usuários, funil, modelos, listas comerciais e canais."
            icon="settings"
        />

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div class="space-y-4">
                <JrCard>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-mono-900">
                                Dados da VOZ
                            </h2>
                            <p class="mt-1 text-sm text-mono-600">
                                Informações institucionais usadas em relatórios
                                e comunicações.
                            </p>
                        </div>
                        <JrBadge variant="primary">geral</JrBadge>
                    </div>

                    <form class="grid gap-4 md:grid-cols-2" @submit.prevent="updateGeneral">
                        <JrInput
                            v-model="generalForm.name"
                            label="Nome"
                            icon="business"
                            :error="generalForm.errors.name"
                            required
                        />
                        <JrInput
                            v-model="generalForm.document"
                            label="Documento"
                            icon="badge"
                            :error="generalForm.errors.document"
                        />
                        <JrInput
                            v-model="generalForm.site"
                            label="Site"
                            icon="language"
                            :error="generalForm.errors.site"
                        />
                        <JrInput
                            v-model="generalForm.email"
                            label="E-mail"
                            icon="mail"
                            :error="generalForm.errors.email"
                        />
                        <JrInput
                            v-model="generalForm.phone"
                            label="Telefone"
                            icon="call"
                            :error="generalForm.errors.phone"
                        />
                        <JrInput
                            v-model="generalForm.address"
                            label="Endereço"
                            icon="location_on"
                            :error="generalForm.errors.address"
                        />
                        <div class="md:col-span-2">
                            <JrButton type="submit" icon="save" size="sm">
                                Salvar dados
                            </JrButton>
                        </div>
                    </form>
                </JrCard>

                <JrCard>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-mono-900">
                                Canais de comunicação
                            </h2>
                            <p class="mt-1 text-sm text-mono-600">
                                Configure ligação, WhatsApp e e-mail em uma seção dedicada.
                            </p>
                        </div>
                        <JrBadge variant="info">canais</JrBadge>
                    </div>

                    <JrButton :href="route('channels.index')" icon="settings_input_antenna" size="sm">
                        Gerenciar canais
                    </JrButton>
                </JrCard>

                <JrCard>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-mono-900">
                                Pipeline e etapas
                            </h2>
                            <p class="mt-1 text-sm text-mono-600">
                                Ordem, cor e marcadores de ganho/perda do funil.
                            </p>
                        </div>
                        <JrBadge variant="success">
                            {{ pipelineStages.length }} etapas
                        </JrBadge>
                    </div>

                    <div class="space-y-3">
                        <form
                            v-for="stage in pipelineStages"
                            :key="stage.id"
                            class="grid gap-3 rounded-2xl border border-mono-100 p-3 lg:grid-cols-[minmax(180px,1fr)_120px_96px_120px]"
                            @submit.prevent="updateStage(stage)"
                        >
                            <JrInput
                                v-model="stageForms[stage.id].name"
                                label="Etapa"
                            />
                            <JrInput
                                v-model="stageForms[stage.id].color"
                                label="Cor"
                                type="color"
                            />
                            <JrInput
                                v-model="stageForms[stage.id].position"
                                label="Ordem"
                                type="number"
                            />
                            <div class="space-y-2 pt-1">
                                <label class="flex items-center gap-2 text-sm text-mono-700">
                                    <input
                                        v-model="stageForms[stage.id].is_won"
                                        type="checkbox"
                                        class="rounded border-mono-300 text-primary-500"
                                    />
                                    Ganha
                                </label>
                                <label class="flex items-center gap-2 text-sm text-mono-700">
                                    <input
                                        v-model="stageForms[stage.id].is_lost"
                                        type="checkbox"
                                        class="rounded border-mono-300 text-primary-500"
                                    />
                                    Perdida
                                </label>
                            </div>
                            <div class="lg:col-span-4">
                                <JrButton type="submit" icon="save" size="sm">
                                    Salvar etapa
                                </JrButton>
                            </div>
                        </form>
                    </div>
                </JrCard>

                <JrCard>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-mono-900">
                                Modelos de comunicação
                            </h2>
                            <p class="mt-1 text-sm text-mono-600">
                                A criação e manutenção dos modelos agora fica
                                em uma seção própria.
                            </p>
                        </div>
                        <JrButton
                            :href="route('templates.index')"
                            icon="drafts"
                            size="sm"
                        >
                            Abrir modelos
                        </JrButton>
                    </div>
                </JrCard>
            </div>

            <div class="space-y-4">
                <JrCard>
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-mono-900">
                                Usuários e perfis
                            </h2>
                            <p class="mt-1 text-sm text-mono-600">
                                Permissões seguem o perfil selecionado.
                            </p>
                        </div>
                        <JrBadge variant="primary">{{ users.length }}</JrBadge>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="user in users"
                            :key="user.id"
                            class="rounded-2xl border border-mono-100 p-3"
                        >
                            <div class="mb-3 min-w-0">
                                <p class="truncate text-sm font-bold text-mono-900">
                                    {{ user.name }}
                                </p>
                                <p class="truncate text-xs text-mono-500">
                                    {{ user.email }} · {{ roleLabel(user.role) }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <JrSelect
                                    v-model="userRoles[user.id]"
                                    class="flex-1"
                                    :options="roles"
                                    placeholder="Perfil"
                                />
                                <JrButton
                                    type="button"
                                    icon="save"
                                    variant="standard"
                                    size="sm"
                                    @click="updateUserRole(user)"
                                >
                                    Salvar
                                </JrButton>
                            </div>
                        </div>
                    </div>
                </JrCard>

                <JrCard>
                    <h2 class="text-base font-bold text-mono-900">
                        Listas comerciais
                    </h2>
                    <p class="mt-1 text-sm text-mono-600">
                        Valores usados em filtros, relatórios e rotina
                        comercial.
                    </p>

                    <form class="mt-4 space-y-3" @submit.prevent="storeOption">
                        <JrSelect
                            v-model="optionForm.group"
                            label="Grupo"
                            :options="
                                optionGroups.map((group) => ({
                                    value: group.key,
                                    label: group.label,
                                }))
                            "
                        />
                        <JrInput
                            v-model="optionForm.label"
                            label="Nova opção"
                            icon="add"
                            :error="optionForm.errors.label"
                        />
                        <JrInput
                            v-model="optionForm.color"
                            label="Cor"
                            type="color"
                            :error="optionForm.errors.color"
                        />
                        <JrButton type="submit" icon="add" size="sm">
                            Adicionar opção
                        </JrButton>
                    </form>

                    <div class="mt-5 space-y-5">
                        <div v-for="group in optionGroups" :key="group.key">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <h3 class="text-sm font-bold text-mono-900">
                                    {{ group.label }}
                                </h3>
                                <JrBadge variant="neutral" size="sm">
                                    {{ group.items.length }}
                                </JrBadge>
                            </div>
                            <div class="space-y-2">
                                <form
                                    v-for="item in group.items"
                                    :key="item.id"
                                    class="rounded-2xl border border-mono-100 p-3"
                                    @submit.prevent="updateOption(item.id)"
                                >
                                    <div class="grid gap-2">
                                        <JrInput
                                            v-model="optionForms[item.id].label"
                                            label="Rótulo"
                                        />
                                        <div class="grid grid-cols-2 gap-2">
                                            <JrInput
                                                v-model="optionForms[item.id].color"
                                                label="Cor"
                                                type="color"
                                            />
                                            <JrInput
                                                v-model="optionForms[item.id].position"
                                                label="Ordem"
                                                type="number"
                                            />
                                        </div>
                                        <label class="flex items-center gap-2 text-sm text-mono-700">
                                            <input
                                                v-model="optionForms[item.id].is_active"
                                                type="checkbox"
                                                class="rounded border-mono-300 text-primary-500"
                                            />
                                            Ativa
                                        </label>
                                        <JrButton
                                            type="submit"
                                            icon="save"
                                            variant="standard"
                                            size="sm"
                                        >
                                            Salvar opção
                                        </JrButton>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </JrCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
