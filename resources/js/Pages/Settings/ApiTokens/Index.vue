<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader,
    ZyBreadcrumb,
    ZyCard,
    ZyTable,
    ZyButton,
    ZyInput,
    ZyModal,
    ZyBadge,
    ZyCheckbox,
    ZyConfirmDialog,
    ZyAlert,
    ZyEmptyState,
} from '@design';
import {
    PlusIcon,
    TrashIcon,
    ClipboardDocumentIcon,
    KeyIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import type { PageProps } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface ApiToken {
    id: string;
    name: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
}

interface Props {
    tokens: ApiToken[];
    availableAbilities: string[];
}

const props = defineProps<Props>();
const page = usePage<PageProps>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Configuracoes' },
    { label: 'Tokens de API' },
];

// ─── Create Token Modal ──────────────────────────────────
const showCreateModal = ref(false);
const newTokenValue = ref<string | null>(null);
const tokenCopied = ref(false);

const form = useForm({
    name: '',
    abilities: [] as string[],
    expires_at: '',
});

const abilityGroups = computed(() => {
    const groups: Record<string, string[]> = {};
    props.availableAbilities.forEach((ability) => {
        const [group] = ability.split(':');
        if (!groups[group]) groups[group] = [];
        groups[group].push(ability);
    });
    return groups;
});

const groupLabels: Record<string, string> = {
    cameras: 'Cameras',
    devices: 'Dispositivos',
    streams: 'Streaming',
    recordings: 'Gravacoes',
    storage: 'Armazenamento',
    users: 'Usuarios',
    profile: 'Perfil',
    mobile: 'Mobile',
    admin: 'Administracao',
};

function toggleAbility(ability: string) {
    const idx = form.abilities.indexOf(ability);
    if (idx >= 0) {
        form.abilities.splice(idx, 1);
    } else {
        form.abilities.push(ability);
    }
}

function selectAll() {
    form.abilities = [...props.availableAbilities];
}

function deselectAll() {
    form.abilities = [];
}

function openCreateModal() {
    form.reset();
    newTokenValue.value = null;
    tokenCopied.value = false;
    showCreateModal.value = true;
}

function createToken() {
    form.post('/settings/api-tokens', {
        preserveScroll: true,
        onSuccess: (resp) => {
            const flash = (resp.props as any).flash;
            if (flash?.newToken) {
                newTokenValue.value = flash.newToken;
            }
            form.reset();
        },
    });
}

function copyToken() {
    if (newTokenValue.value) {
        navigator.clipboard.writeText(newTokenValue.value);
        tokenCopied.value = true;
        setTimeout(() => (tokenCopied.value = false), 3000);
    }
}

function closeCreateModal() {
    showCreateModal.value = false;
    newTokenValue.value = null;
    tokenCopied.value = false;
}

// ─── Delete Token ────────────────────────────────────────
const showDeleteConfirm = ref(false);
const tokenToDelete = ref<ApiToken | null>(null);

function confirmDelete(token: ApiToken) {
    tokenToDelete.value = token;
    showDeleteConfirm.value = true;
}

function deleteToken() {
    if (tokenToDelete.value) {
        router.delete(`/settings/api-tokens/${tokenToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirm.value = false;
                tokenToDelete.value = null;
            },
        });
    }
}

// ─── Table ───────────────────────────────────────────────
const columns = [
    { key: 'name', label: 'Nome', sortable: true },
    { key: 'abilities', label: 'Permissoes' },
    { key: 'last_used_at', label: 'Ultimo uso', sortable: true },
    { key: 'created_at', label: 'Criado em', sortable: true },
];

function formatDate(date: string | null): string {
    if (!date) return 'Nunca';
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatAbilities(abilities: string[]): string {
    if (abilities.includes('*') || abilities.includes('admin:*')) return 'Acesso total';
    if (abilities.length <= 3) return abilities.join(', ');
    return `${abilities.slice(0, 2).join(', ')} +${abilities.length - 2}`;
}

function abilityLabel(ability: string): string {
    const labels: Record<string, string> = {
        'cameras:read': 'Visualizar',
        'cameras:write': 'Criar/Editar',
        'cameras:delete': 'Excluir',
        'devices:read': 'Visualizar',
        'devices:write': 'Criar/Editar',
        'devices:delete': 'Excluir',
        'streams:read': 'Visualizar',
        'streams:write': 'Iniciar/Parar',
        'recordings:read': 'Visualizar',
        'recordings:write': 'Gerenciar',
        'recordings:download': 'Download',
        'recordings:delete': 'Excluir',
        'storage:read': 'Visualizar',
        'storage:write': 'Gerenciar',
        'users:read': 'Visualizar',
        'users:write': 'Gerenciar',
        'profile:read': 'Visualizar',
        'profile:write': 'Editar',
        'mobile:dashboard': 'Dashboard',
        'mobile:sync': 'Sincronizar',
        'admin:*': 'Acesso total',
    };
    return labels[ability] || ability;
}
</script>

<template>
    <Head title="Tokens de API" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Tokens de API"
        description="Gerencie tokens de acesso para integracoes externas e aplicativo mobile."
    >
        <template #actions>
            <ZyButton @click="openCreateModal">
                <PlusIcon class="h-5 w-5 mr-1" />
                Novo Token
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Info Alert -->
    <ZyAlert variant="info" class="mb-6">
        Tokens de API permitem que aplicativos externos acessem sua conta de forma segura.
        Cada token pode ter permissoes especificas. Revogue tokens que nao estao mais em uso.
    </ZyAlert>

    <!-- Tokens Table -->
    <ZyCard v-if="tokens.length > 0">
        <ZyTable :columns="columns" :data="tokens" hoverable>
            <template #cell-name="{ row }">
                <div class="flex items-center gap-2">
                    <KeyIcon class="h-4 w-4 text-[var(--zy-text-muted)]" />
                    <span class="font-medium">{{ row.name }}</span>
                </div>
            </template>

            <template #cell-abilities="{ row }">
                <div class="flex flex-wrap gap-1">
                    <ZyBadge
                        v-if="(row as ApiToken).abilities.includes('admin:*')"
                        variant="warning"
                    >
                        Acesso total
                    </ZyBadge>
                    <template v-else>
                        <ZyBadge
                            v-for="ability in (row as ApiToken).abilities.slice(0, 3)"
                            :key="ability"
                            variant="default"
                        >
                            {{ ability }}
                        </ZyBadge>
                        <ZyBadge
                            v-if="(row as ApiToken).abilities.length > 3"
                            variant="default"
                        >
                            +{{ (row as ApiToken).abilities.length - 3 }}
                        </ZyBadge>
                    </template>
                </div>
            </template>

            <template #cell-last_used_at="{ row }">
                <span class="text-sm text-[var(--zy-text-muted)]">
                    {{ formatDate((row as ApiToken).last_used_at) }}
                </span>
            </template>

            <template #cell-created_at="{ row }">
                <span class="text-sm text-[var(--zy-text-muted)]">
                    {{ formatDate((row as ApiToken).created_at) }}
                </span>
            </template>

            <template #actions="{ row }">
                <ZyButton
                    variant="ghost"
                    size="sm"
                    @click="confirmDelete(row as ApiToken)"
                >
                    <TrashIcon class="h-4 w-4 text-red-500" />
                </ZyButton>
            </template>
        </ZyTable>
    </ZyCard>

    <!-- Empty State -->
    <ZyCard v-else>
        <ZyEmptyState
            title="Nenhum token criado"
            description="Crie um token de API para integrar aplicativos externos ou o app mobile com sua conta."
            :icon="KeyIcon"
        >
            <ZyButton @click="openCreateModal">
                <PlusIcon class="h-5 w-5 mr-1" />
                Criar primeiro token
            </ZyButton>
        </ZyEmptyState>
    </ZyCard>

    <!-- Create Token Modal -->
    <ZyModal :show="showCreateModal" title="Novo Token de API" size="lg" @close="closeCreateModal">
        <!-- Token Created Success -->
        <div v-if="newTokenValue">
            <ZyAlert variant="warning" class="mb-4">
                <strong>Importante:</strong> Copie o token agora. Ele nao sera exibido novamente.
            </ZyAlert>

            <div class="flex items-center gap-2 p-3 bg-[var(--zy-surface-secondary)] rounded-lg font-mono text-sm break-all">
                <code class="flex-1">{{ newTokenValue }}</code>
                <ZyButton variant="ghost" size="sm" @click="copyToken">
                    <ClipboardDocumentIcon class="h-5 w-5" />
                </ZyButton>
            </div>

            <p v-if="tokenCopied" class="mt-2 text-sm text-green-600">
                Token copiado!
            </p>
        </div>

        <!-- Create Form -->
        <form v-else @submit.prevent="createToken" class="space-y-6">
            <ZyInput
                v-model="form.name"
                label="Nome do token"
                placeholder="Ex: App Mobile, Integracao ERP"
                :error="form.errors.name"
                required
            />

            <ZyInput
                v-model="form.expires_at"
                type="datetime-local"
                label="Expira em (opcional)"
                :error="form.errors.expires_at"
                helper="Deixe em branco para token sem expiracao."
            />

            <!-- Abilities -->
            <div>
                <label class="block text-sm font-medium text-[var(--zy-text-primary)] mb-2">
                    Permissoes
                </label>
                <p v-if="form.errors.abilities" class="text-sm text-red-500 mb-2">
                    {{ form.errors.abilities }}
                </p>

                <div class="flex gap-2 mb-3">
                    <ZyButton type="button" variant="secondary" size="sm" @click="selectAll">
                        Selecionar tudo
                    </ZyButton>
                    <ZyButton type="button" variant="secondary" size="sm" @click="deselectAll">
                        Limpar
                    </ZyButton>
                </div>

                <div class="space-y-4 max-h-64 overflow-y-auto pr-2">
                    <div v-for="(abilities, group) in abilityGroups" :key="group">
                        <h4 class="text-sm font-semibold text-[var(--zy-text-primary)] mb-2 flex items-center gap-2">
                            <ShieldCheckIcon class="h-4 w-4" />
                            {{ groupLabels[group as string] || group }}
                        </h4>
                        <div class="grid grid-cols-2 gap-2 ml-6">
                            <ZyCheckbox
                                v-for="ability in abilities"
                                :key="ability"
                                :model-value="form.abilities.includes(ability)"
                                :label="abilityLabel(ability)"
                                @update:model-value="toggleAbility(ability)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <div v-if="newTokenValue" class="flex justify-end">
                <ZyButton @click="closeCreateModal">Fechar</ZyButton>
            </div>
            <div v-else class="flex justify-end gap-3">
                <ZyButton variant="secondary" @click="closeCreateModal">Cancelar</ZyButton>
                <ZyButton
                    :loading="form.processing"
                    :disabled="form.abilities.length === 0"
                    @click="createToken"
                >
                    Criar Token
                </ZyButton>
            </div>
        </template>
    </ZyModal>

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Revogar Token"
        :message="`Tem certeza que deseja revogar o token '${tokenToDelete?.name}'? Aplicativos usando este token perderao acesso imediatamente.`"
        confirm-text="Revogar"
        @confirm="deleteToken"
        @cancel="showDeleteConfirm = false"
    />
</template>
