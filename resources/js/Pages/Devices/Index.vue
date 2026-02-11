<script setup lang="ts">
/**
 * Devices Index — Listagem de dispositivos (DVRs, NVRs, Encoders)
 * Super-admin ve dispositivos de todos os tenants com filtro
 */

import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyTable, ZyPagination,
    ZyButton, ZyInput, ZySelect, ZyBadge, ZyConfirmDialog,
} from '@design';
import { PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface DeviceRow {
    id: string;
    name: string;
    slug: string;
    type: string;
    manufacturer: string;
    model: string | null;
    address: string;
    port: number;
    total_channels: number;
    active_channels: number;
    is_active: boolean;
    is_online: boolean;
    location: string | null;
    cameras_count: number;
    tenant?: { id: string; name: string };
}

interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
}

interface Props {
    devices: LaravelPagination<DeviceRow>;
    filters: {
        search?: string;
        type?: string;
        manufacturer?: string;
        is_active?: string;
        tenant_id?: string;
    };
    tenants: { id: string; name: string }[];
    manufacturers: { value: string; label: string }[];
    isSuperAdmin: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    tenants: () => [],
    manufacturers: () => [],
    isSuperAdmin: false,
});

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const manufacturerFilter = ref(props.filters?.manufacturer || '');
const tenantFilter = ref(props.filters?.tenant_id || '');
const showDeleteConfirm = ref(false);
const deviceToDelete = ref<DeviceRow | null>(null);

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Dispositivos' },
];

const columns = [
    { key: 'name', label: 'Dispositivo', sortable: true },
    { key: 'type', label: 'Tipo' },
    { key: 'manufacturer', label: 'Fabricante' },
    { key: 'address', label: 'Endereco' },
    { key: 'channels', label: 'Canais' },
    ...(props.isSuperAdmin ? [{ key: 'tenant', label: 'Empresa' }] : []),
    { key: 'is_active', label: 'Status' },
    { key: 'is_online', label: 'Conexao' },
];

const typeOptions = [
    { value: '', label: 'Todos os tipos' },
    { value: 'dvr', label: 'DVR' },
    { value: 'nvr', label: 'NVR' },
    { value: 'encoder', label: 'Encoder' },
    { value: 'camera', label: 'Camera IP' },
    { value: 'hybrid', label: 'Hibrido' },
];

const manufacturerOptions = [
    { value: '', label: 'Todos os fabricantes' },
    ...props.manufacturers,
];

const tenantOptions = [
    { value: '', label: 'Todas as empresas' },
    ...(props.tenants || []).map((t) => ({ value: t.id, label: t.name })),
];

const typeLabels: Record<string, string> = {
    dvr: 'DVR',
    nvr: 'NVR',
    encoder: 'Encoder',
    camera: 'Camera IP',
    hybrid: 'Hibrido',
};

const typeBadgeVariants: Record<string, string> = {
    dvr: 'primary',
    nvr: 'success',
    encoder: 'warning',
    camera: 'secondary',
    hybrid: 'danger',
};

function applyFilters() {
    router.get('/devices', {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
        manufacturer: manufacturerFilter.value || undefined,
        tenant_id: tenantFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function confirmDelete(device: DeviceRow) {
    deviceToDelete.value = device;
    showDeleteConfirm.value = true;
}

function deleteDevice() {
    if (deviceToDelete.value) {
        router.delete(`/devices/${deviceToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                deviceToDelete.value = null;
            },
        });
    }
}

function changePage(page: number) {
    router.get('/devices', { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="Dispositivos" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Dispositivos" :description="isSuperAdmin ? 'Gerencie DVRs, NVRs e dispositivos de todas as empresas' : 'Gerencie DVRs, NVRs e dispositivos da sua empresa'">
        <template #actions>
            <ZyButton href="/devices/create" variant="primary">
                <PlusIcon class="h-5 w-5" />
                Novo Dispositivo
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Filters -->
    <ZyCard class="mb-6" padding="md">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <ZyInput
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nome, endereco, modelo..."
                    icon
                    @keyup.enter="applyFilters"
                >
                    <template #icon>
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </template>
                </ZyInput>
            </div>
            <div v-if="isSuperAdmin" class="w-full sm:w-48">
                <ZySelect
                    v-model="tenantFilter"
                    :options="tenantOptions"
                    placeholder="Empresa"
                    @update:model-value="applyFilters"
                />
            </div>
            <div class="w-full sm:w-40">
                <ZySelect
                    v-model="typeFilter"
                    :options="typeOptions"
                    placeholder="Tipo"
                    @update:model-value="applyFilters"
                />
            </div>
            <div class="w-full sm:w-48">
                <ZySelect
                    v-model="manufacturerFilter"
                    :options="manufacturerOptions"
                    placeholder="Fabricante"
                    @update:model-value="applyFilters"
                />
            </div>
            <ZyButton variant="secondary" @click="applyFilters">
                Filtrar
            </ZyButton>
        </div>
    </ZyCard>

    <!-- Table -->
    <ZyTable :columns="columns" :data="(devices?.data || []) as any[]" hoverable>
        <template #cell-name="{ row }">
            <div>
                <p class="font-medium text-[var(--zy-text-primary)]">{{ (row as any).name }}</p>
                <p v-if="(row as any).model" class="text-caption text-dark-500">{{ (row as any).model }}</p>
            </div>
        </template>

        <template #cell-type="{ row }">
            <ZyBadge :variant="(typeBadgeVariants[(row as any).type] || 'secondary') as any">
                {{ typeLabels[(row as any).type] || (row as any).type }}
            </ZyBadge>
        </template>

        <template #cell-manufacturer="{ row }">
            <span class="text-body-sm text-[var(--zy-text-primary)] capitalize">{{ (row as any).manufacturer }}</span>
        </template>

        <template #cell-address="{ row }">
            <span class="text-body-sm text-[var(--zy-text-primary)] font-mono">
                {{ (row as any).address }}:{{ (row as any).port }}
            </span>
        </template>

        <template #cell-channels="{ row }">
            <span class="text-body-sm text-[var(--zy-text-primary)]">
                {{ (row as any).cameras_count || (row as any).active_channels }}/{{ (row as any).total_channels }}
            </span>
        </template>

        <template v-if="isSuperAdmin" #cell-tenant="{ row }">
            <span class="text-body-sm text-[var(--zy-text-primary)]">{{ (row as any).tenant?.name || '—' }}</span>
        </template>

        <template #cell-is_active="{ row }">
            <ZyBadge :variant="(row as any).is_active ? 'success' : 'danger'" dot>
                {{ (row as any).is_active ? 'Ativo' : 'Inativo' }}
            </ZyBadge>
        </template>

        <template #cell-is_online="{ row }">
            <ZyBadge :variant="(row as any).is_online ? 'success' : 'secondary'" dot>
                {{ (row as any).is_online ? 'Online' : 'Offline' }}
            </ZyBadge>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center justify-end gap-1">
                <ZyButton variant="ghost" size="sm" :href="`/devices/${(row as any).id}`" aria-label="Visualizar">
                    <EyeIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="ghost" size="sm" :href="`/devices/${(row as any).id}/edit`" aria-label="Editar">
                    <PencilIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="ghost" size="sm" aria-label="Excluir" @click="confirmDelete(row as any)">
                    <TrashIcon class="h-4 w-4 text-danger-500" />
                </ZyButton>
            </div>
        </template>
    </ZyTable>

    <!-- Pagination -->
    <ZyPagination
        v-if="devices && devices.last_page > 1"
        :current-page="devices.current_page"
        :last-page="devices.last_page"
        :from="devices.from"
        :to="devices.to"
        :total="devices.total"
        class="mt-4"
        @page-change="changePage"
    />

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Dispositivo"
        :message="`Tem certeza que deseja excluir o dispositivo ${deviceToDelete?.name}? Esta acao nao pode ser desfeita.`"
        confirm-text="Excluir"
        @confirm="deleteDevice"
        @cancel="showDeleteConfirm = false"
    />
</template>
