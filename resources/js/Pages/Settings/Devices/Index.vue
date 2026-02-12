<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader,
    ZyBreadcrumb,
    ZyCard,
    ZyTable,
    ZyButton,
    ZyBadge,
    ZyConfirmDialog,
    ZyAlert,
    ZyEmptyState,
} from '@design';
import {
    DevicePhoneMobileIcon,
    TrashIcon,
    NoSymbolIcon,
    ComputerDesktopIcon,
    DeviceTabletIcon,
    SignalIcon,
    SignalSlashIcon,
    BellIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const { addToast } = useToast();

interface DeviceUser {
    name: string;
    email: string;
}

interface UserDevice {
    id: string;
    device_id: string;
    device_name: string;
    platform: 'ios' | 'android' | 'web';
    app_version: string | null;
    os_version: string | null;
    is_active: boolean;
    has_push_token: boolean;
    push_provider: 'fcm' | 'apns' | null;
    last_active_at: string | null;
    revoked_at: string | null;
    created_at: string;
    user: DeviceUser | null;
}

interface Stats {
    total: number;
    active: number;
    revoked: number;
    ios: number;
    android: number;
    web: number;
}

interface Props {
    devices: UserDevice[];
    stats: Stats;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Configuracoes' },
    { label: 'Dispositivos Conectados' },
];

// ─── Filter ──────────────────────────────────────────────
const filter = ref<'all' | 'active' | 'revoked'>('all');

const filteredDevices = computed(() => {
    if (filter.value === 'active') {
        return props.devices.filter((d) => d.is_active);
    }
    if (filter.value === 'revoked') {
        return props.devices.filter((d) => !d.is_active || d.revoked_at);
    }
    return props.devices;
});

// ─── Revoke ──────────────────────────────────────────────
const showRevokeConfirm = ref(false);
const deviceToRevoke = ref<UserDevice | null>(null);

function confirmRevoke(device: UserDevice) {
    deviceToRevoke.value = device;
    showRevokeConfirm.value = true;
}

function revokeDevice() {
    if (deviceToRevoke.value) {
        router.put(`/settings/devices/${deviceToRevoke.value.id}/revoke`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                showRevokeConfirm.value = false;
                deviceToRevoke.value = null;
                addToast({
                    type: 'success',
                    title: 'Sucesso',
                    message: 'Dispositivo desconectado com sucesso.',
                });
            },
            onError: () => {
                addToast({
                    type: 'error',
                    title: 'Erro',
                    message: 'Nao foi possivel desconectar o dispositivo.',
                });
            },
        });
    }
}

// ─── Delete ──────────────────────────────────────────────
const showDeleteConfirm = ref(false);
const deviceToDelete = ref<UserDevice | null>(null);

function confirmDelete(device: UserDevice) {
    deviceToDelete.value = device;
    showDeleteConfirm.value = true;
}

function deleteDevice() {
    if (deviceToDelete.value) {
        router.delete(`/settings/devices/${deviceToDelete.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteConfirm.value = false;
                deviceToDelete.value = null;
                addToast({
                    type: 'success',
                    title: 'Sucesso',
                    message: 'Dispositivo removido com sucesso.',
                });
            },
            onError: () => {
                addToast({
                    type: 'error',
                    title: 'Erro',
                    message: 'Nao foi possivel remover o dispositivo.',
                });
            },
        });
    }
}

// ─── Table ───────────────────────────────────────────────
const columns = [
    { key: 'device_name', label: 'Dispositivo', sortable: true },
    { key: 'platform', label: 'Plataforma', sortable: true },
    { key: 'user', label: 'Usuario' },
    { key: 'status', label: 'Status' },
    { key: 'last_active_at', label: 'Ultima atividade', sortable: true },
    { key: 'created_at', label: 'Registrado em', sortable: true },
];

// ─── Helpers ─────────────────────────────────────────────
function platformIcon(platform: string) {
    switch (platform) {
        case 'ios': return DevicePhoneMobileIcon;
        case 'android': return DevicePhoneMobileIcon;
        case 'web': return ComputerDesktopIcon;
        default: return DeviceTabletIcon;
    }
}

function platformLabel(platform: string): string {
    switch (platform) {
        case 'ios': return 'iOS';
        case 'android': return 'Android';
        case 'web': return 'Web';
        default: return platform;
    }
}

function platformBadgeVariant(platform: string): string {
    switch (platform) {
        case 'ios': return 'info';
        case 'android': return 'success';
        case 'web': return 'default';
        default: return 'default';
    }
}

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

function timeAgo(date: string | null): string {
    if (!date) return 'Nunca';
    const now = new Date();
    const d = new Date(date);
    const diffMs = now.getTime() - d.getTime();
    const diffMin = Math.floor(diffMs / 60000);

    if (diffMin < 1) return 'Agora';
    if (diffMin < 60) return `${diffMin}min atras`;
    const diffHours = Math.floor(diffMin / 60);
    if (diffHours < 24) return `${diffHours}h atras`;
    const diffDays = Math.floor(diffHours / 24);
    if (diffDays < 30) return `${diffDays}d atras`;
    return formatDate(date);
}
</script>

<template>
    <Head title="Dispositivos Conectados" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Dispositivos Conectados"
        description="Gerencie os dispositivos mobile e web conectados a sua conta via API."
    />

    <!-- Stats Cards -->
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
            <p class="text-2xl font-bold text-white">{{ stats.total }}</p>
            <p class="text-xs text-[var(--zy-text-muted)]">Total</p>
        </div>
        <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
            <p class="text-2xl font-bold text-green-400">{{ stats.active }}</p>
            <p class="text-xs text-[var(--zy-text-muted)]">Ativos</p>
        </div>
        <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
            <p class="text-2xl font-bold text-red-400">{{ stats.revoked }}</p>
            <p class="text-xs text-[var(--zy-text-muted)]">Revogados</p>
        </div>
        <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
            <p class="text-2xl font-bold text-blue-400">{{ stats.ios }}</p>
            <p class="text-xs text-[var(--zy-text-muted)]">iOS</p>
        </div>
        <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
            <p class="text-2xl font-bold text-emerald-400">{{ stats.android }}</p>
            <p class="text-xs text-[var(--zy-text-muted)]">Android</p>
        </div>
        <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
            <p class="text-2xl font-bold text-slate-400">{{ stats.web }}</p>
            <p class="text-xs text-[var(--zy-text-muted)]">Web</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="mt-6 flex gap-2">
        <ZyButton
            :variant="filter === 'all' ? 'primary' : 'secondary'"
            size="sm"
            @click="filter = 'all'"
        >
            Todos ({{ stats.total }})
        </ZyButton>
        <ZyButton
            :variant="filter === 'active' ? 'primary' : 'secondary'"
            size="sm"
            @click="filter = 'active'"
        >
            Ativos ({{ stats.active }})
        </ZyButton>
        <ZyButton
            :variant="filter === 'revoked' ? 'primary' : 'secondary'"
            size="sm"
            @click="filter = 'revoked'"
        >
            Revogados ({{ stats.revoked }})
        </ZyButton>
    </div>

    <!-- Info Alert -->
    <ZyAlert variant="info" class="mt-4">
        Dispositivos sao registrados automaticamente quando um usuario faz login via API (app mobile ou integracao externa).
        Voce pode desconectar dispositivos suspeitos ou que nao estao mais em uso.
    </ZyAlert>

    <!-- Devices Table -->
    <ZyCard v-if="filteredDevices.length > 0" class="mt-4">
        <ZyTable :columns="columns" :data="filteredDevices" hoverable>
            <template #cell-device_name="{ row }">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700">
                        <component
                            :is="platformIcon((row as UserDevice).platform)"
                            class="h-4 w-4 text-slate-300"
                        />
                    </div>
                    <div>
                        <p class="font-medium text-white">{{ (row as UserDevice).device_name }}</p>
                        <p v-if="(row as UserDevice).os_version" class="text-xs text-[var(--zy-text-muted)]">
                            {{ (row as UserDevice).os_version }}
                        </p>
                    </div>
                </div>
            </template>

            <template #cell-platform="{ row }">
                <div class="flex items-center gap-2">
                    <ZyBadge :variant="platformBadgeVariant((row as UserDevice).platform)">
                        {{ platformLabel((row as UserDevice).platform) }}
                    </ZyBadge>
                    <span v-if="(row as UserDevice).app_version" class="text-xs text-[var(--zy-text-muted)]">
                        v{{ (row as UserDevice).app_version }}
                    </span>
                </div>
            </template>

            <template #cell-user="{ row }">
                <div v-if="(row as UserDevice).user">
                    <p class="text-sm text-white">{{ (row as UserDevice).user!.name }}</p>
                    <p class="text-xs text-[var(--zy-text-muted)]">{{ (row as UserDevice).user!.email }}</p>
                </div>
                <span v-else class="text-sm text-[var(--zy-text-muted)]">—</span>
            </template>

            <template #cell-status="{ row }">
                <div class="flex items-center gap-2">
                    <template v-if="(row as UserDevice).revoked_at">
                        <ZyBadge variant="danger">
                            <NoSymbolIcon class="mr-1 h-3 w-3" />
                            Revogado
                        </ZyBadge>
                    </template>
                    <template v-else-if="(row as UserDevice).is_active">
                        <ZyBadge variant="success">
                            <SignalIcon class="mr-1 h-3 w-3" />
                            Ativo
                        </ZyBadge>
                    </template>
                    <template v-else>
                        <ZyBadge variant="warning">
                            <SignalSlashIcon class="mr-1 h-3 w-3" />
                            Inativo
                        </ZyBadge>
                    </template>

                    <BellIcon
                        v-if="(row as UserDevice).has_push_token && (row as UserDevice).is_active"
                        class="h-4 w-4 text-amber-400"
                        title="Push notifications ativo"
                    />
                </div>
            </template>

            <template #cell-last_active_at="{ row }">
                <span class="text-sm text-[var(--zy-text-muted)]" :title="formatDate((row as UserDevice).last_active_at)">
                    {{ timeAgo((row as UserDevice).last_active_at) }}
                </span>
            </template>

            <template #cell-created_at="{ row }">
                <span class="text-sm text-[var(--zy-text-muted)]">
                    {{ formatDate((row as UserDevice).created_at) }}
                </span>
            </template>

            <template #actions="{ row }">
                <div class="flex gap-1">
                    <ZyButton
                        v-if="(row as UserDevice).is_active && !(row as UserDevice).revoked_at"
                        variant="ghost"
                        size="sm"
                        title="Desconectar dispositivo"
                        @click="confirmRevoke(row as UserDevice)"
                    >
                        <NoSymbolIcon class="h-4 w-4 text-amber-500" />
                    </ZyButton>
                    <ZyButton
                        variant="ghost"
                        size="sm"
                        title="Remover registro"
                        @click="confirmDelete(row as UserDevice)"
                    >
                        <TrashIcon class="h-4 w-4 text-red-500" />
                    </ZyButton>
                </div>
            </template>
        </ZyTable>
    </ZyCard>

    <!-- Empty State -->
    <ZyCard v-else class="mt-4">
        <ZyEmptyState
            title="Nenhum dispositivo encontrado"
            :description="filter === 'all'
                ? 'Nenhum dispositivo foi registrado ainda. Dispositivos aparecerao aqui quando usuarios acessarem via app mobile ou API.'
                : 'Nenhum dispositivo encontrado com este filtro.'"
            :icon="DevicePhoneMobileIcon"
        />
    </ZyCard>

    <!-- Revoke Confirmation -->
    <ZyConfirmDialog
        :show="showRevokeConfirm"
        title="Desconectar Dispositivo"
        :message="`Deseja desconectar o dispositivo '${deviceToRevoke?.device_name}'? O usuario precisara fazer login novamente neste dispositivo. Push notifications serao desativadas.`"
        confirm-text="Desconectar"
        @confirm="revokeDevice"
        @cancel="showRevokeConfirm = false"
    />

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Remover Dispositivo"
        :message="`Deseja remover permanentemente o registro do dispositivo '${deviceToDelete?.device_name}'? Esta acao nao pode ser desfeita.`"
        confirm-text="Remover"
        @confirm="deleteDevice"
        @cancel="showDeleteConfirm = false"
    />
</template>
