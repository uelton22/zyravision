<script setup lang="ts">
/**
 * Cameras Index — Listagem de cameras com filtros e paginacao
 * Super-admin ve cameras de todos os tenants com filtro
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

interface CameraRow {
    id: string;
    name: string;
    slug: string;
    channel_number: number | null;
    channel_type: string;
    address: string | null;
    port: number;
    is_active: boolean;
    is_online: boolean;
    is_recording: boolean;
    location: string | null;
    device?: { id: string; name: string; type: string; manufacturer: string } | null;
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
    cameras: LaravelPagination<CameraRow>;
    filters: {
        search?: string;
        is_active?: string;
        device_id?: string;
        channel_type?: string;
        tenant_id?: string;
    };
    tenants: { id: string; name: string }[];
    isSuperAdmin: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    tenants: () => [],
    isSuperAdmin: false,
});

const search = ref(props.filters?.search || '');
const channelTypeFilter = ref(props.filters?.channel_type || '');
const tenantFilter = ref(props.filters?.tenant_id || '');
const showDeleteConfirm = ref(false);
const cameraToDelete = ref<CameraRow | null>(null);

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Cameras' },
];

const columns = [
    { key: 'name', label: 'Camera', sortable: true },
    { key: 'device', label: 'Dispositivo' },
    { key: 'channel_type', label: 'Tipo' },
    { key: 'address', label: 'Endereco' },
    ...(props.isSuperAdmin ? [{ key: 'tenant', label: 'Empresa' }] : []),
    { key: 'is_active', label: 'Status' },
    { key: 'is_online', label: 'Conexao' },
];

const channelTypeOptions = [
    { value: '', label: 'Todos os tipos' },
    { value: 'direct', label: 'Direta' },
    { value: 'analog', label: 'Analogica' },
    { value: 'ip', label: 'IP' },
];

const tenantOptions = [
    { value: '', label: 'Todas as empresas' },
    ...(props.tenants || []).map((t) => ({ value: t.id, label: t.name })),
];

const channelTypeLabels: Record<string, string> = {
    direct: 'Direta',
    analog: 'Analogica',
    ip: 'IP',
};

const channelTypeBadgeVariants: Record<string, string> = {
    direct: 'primary',
    analog: 'warning',
    ip: 'success',
};

function applyFilters() {
    router.get('/cameras', {
        search: search.value || undefined,
        channel_type: channelTypeFilter.value || undefined,
        tenant_id: tenantFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function confirmDelete(camera: CameraRow) {
    cameraToDelete.value = camera;
    showDeleteConfirm.value = true;
}

function deleteCamera() {
    if (cameraToDelete.value) {
        router.delete(`/cameras/${cameraToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                cameraToDelete.value = null;
            },
        });
    }
}

function changePage(page: number) {
    router.get('/cameras', { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="Cameras" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Cameras" :description="isSuperAdmin ? 'Gerencie cameras de todas as empresas' : 'Gerencie as cameras da sua empresa'">
        <template #actions>
            <ZyButton href="/cameras/create" variant="primary">
                <PlusIcon class="h-5 w-5" />
                Nova Camera
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
                    placeholder="Buscar por nome, endereco ou localizacao..."
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
                    v-model="channelTypeFilter"
                    :options="channelTypeOptions"
                    placeholder="Tipo"
                    @update:model-value="applyFilters"
                />
            </div>
            <ZyButton variant="secondary" @click="applyFilters">
                Filtrar
            </ZyButton>
        </div>
    </ZyCard>

    <!-- Table -->
    <ZyTable :columns="columns" :data="(cameras?.data || []) as any[]" hoverable>
        <template #cell-name="{ row }">
            <div>
                <p class="font-medium text-[var(--zy-text-primary)]">{{ (row as any).name }}</p>
                <p v-if="(row as any).location" class="text-caption text-dark-500">{{ (row as any).location }}</p>
            </div>
        </template>

        <template #cell-device="{ row }">
            <template v-if="(row as any).device">
                <a :href="`/devices/${(row as any).device.id}`" class="text-body-sm text-[var(--zy-text-primary)] hover:underline">
                    {{ (row as any).device.name }}
                </a>
                <span v-if="(row as any).channel_number" class="text-caption text-dark-500 ml-1">
                    (CH {{ (row as any).channel_number }})
                </span>
            </template>
            <span v-else class="text-body-sm text-dark-500">Direta</span>
        </template>

        <template #cell-channel_type="{ row }">
            <ZyBadge :variant="(channelTypeBadgeVariants[(row as any).channel_type] || 'secondary') as any">
                {{ channelTypeLabels[(row as any).channel_type] || (row as any).channel_type }}
            </ZyBadge>
        </template>

        <template #cell-address="{ row }">
            <span v-if="(row as any).address" class="text-body-sm text-[var(--zy-text-primary)] font-mono">
                {{ (row as any).address }}:{{ (row as any).port }}
            </span>
            <span v-else class="text-body-sm text-dark-500">Via dispositivo</span>
        </template>

        <template v-if="isSuperAdmin" #cell-tenant="{ row }">
            <span class="text-body-sm text-[var(--zy-text-primary)]">{{ (row as any).tenant?.name || '—' }}</span>
        </template>

        <template #cell-is_active="{ row }">
            <ZyBadge :variant="(row as any).is_active ? 'success' : 'danger'" dot>
                {{ (row as any).is_active ? 'Ativa' : 'Inativa' }}
            </ZyBadge>
        </template>

        <template #cell-is_online="{ row }">
            <ZyBadge :variant="(row as any).is_online ? 'success' : 'danger'" dot>
                {{ (row as any).is_online ? 'Online' : 'Offline' }}
            </ZyBadge>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center justify-end gap-1">
                <ZyButton variant="ghost" size="sm" :href="`/cameras/${(row as any).id}`" aria-label="Visualizar">
                    <EyeIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="ghost" size="sm" :href="`/cameras/${(row as any).id}/edit`" aria-label="Editar">
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
        v-if="cameras && cameras.last_page > 1"
        :current-page="cameras.current_page"
        :last-page="cameras.last_page"
        :from="cameras.from"
        :to="cameras.to"
        :total="cameras.total"
        class="mt-4"
        @page-change="changePage"
    />

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Camera"
        :message="`Tem certeza que deseja excluir a camera ${cameraToDelete?.name}? Esta acao nao pode ser desfeita.`"
        confirm-text="Excluir"
        @confirm="deleteCamera"
        @cancel="showDeleteConfirm = false"
    />
</template>
