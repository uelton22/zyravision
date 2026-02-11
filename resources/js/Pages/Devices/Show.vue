<script setup lang="ts">
/**
 * Devices Show — Detalhes do dispositivo
 */

import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge, ZyTable, ZyConfirmDialog } from '@design';
import { PencilIcon, TrashIcon, PlusIcon } from '@heroicons/vue/24/outline';
import type { Device } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface CameraRow {
    id: string;
    name: string;
    channel_number: number | null;
    channel_type: string;
    is_active: boolean;
    is_online: boolean;
    location: string | null;
}

interface Props {
    device: Device;
    cameras: CameraRow[];
}

const props = defineProps<Props>();

const showDeleteConfirm = ref(false);

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Dispositivos', href: '/devices' },
    { label: props.device.name },
];

const typeLabels: Record<string, string> = {
    dvr: 'DVR',
    nvr: 'NVR',
    encoder: 'Encoder',
    camera: 'Camera IP',
    hybrid: 'Hibrido',
};

const channelTypeLabels: Record<string, string> = {
    analog: 'Analogico',
    ip: 'IP',
    direct: 'Direto',
};

const cameraColumns = [
    { key: 'channel_number', label: 'Canal' },
    { key: 'name', label: 'Nome' },
    { key: 'channel_type', label: 'Tipo' },
    { key: 'location', label: 'Local' },
    { key: 'is_active', label: 'Status' },
    { key: 'is_online', label: 'Conexao' },
];

function deleteDevice() {
    router.delete(`/devices/${props.device.id}`, {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}
</script>

<template>
    <Head :title="device.name" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="device.name" description="Detalhes do dispositivo">
        <template #actions>
            <ZyButton variant="secondary" :href="`/devices/${device.id}/edit`">
                <PencilIcon class="h-5 w-5" />
                Editar
            </ZyButton>
            <ZyButton variant="danger" @click="showDeleteConfirm = true">
                <TrashIcon class="h-5 w-5" />
                Excluir
            </ZyButton>
        </template>
    </ZyPageHeader>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Device Info -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Informacoes do Dispositivo</h3>
                </template>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Nome</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ device.name }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Tipo</p>
                        <ZyBadge variant="primary">{{ typeLabels[device.type] || device.type }}</ZyBadge>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Fabricante</p>
                        <p class="text-body text-[var(--zy-text-primary)] capitalize">{{ device.manufacturer }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Modelo</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ device.model || 'Nao informado' }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Firmware</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ device.firmware_version || 'Nao informado' }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Numero de serie</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ device.serial_number || 'Nao informado' }}</p>
                    </div>
                </div>
            </ZyCard>

            <!-- Connection -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Conexao</h3>
                </template>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Endereco</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono">{{ device.address }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Tipo de endereco</p>
                        <p class="text-body text-[var(--zy-text-primary)] uppercase">{{ device.address_type }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Porta RTSP</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono">{{ device.port }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Porta HTTP</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono">{{ device.http_port }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Porta RTMP</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono">{{ device.rtmp_port }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Transporte</p>
                        <p class="text-body text-[var(--zy-text-primary)] uppercase">{{ device.transport }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">ONVIF</p>
                        <ZyBadge :variant="device.onvif_supported ? 'success' : 'secondary'">
                            {{ device.onvif_supported ? `Sim (${device.onvif_profile || 'Profile S'})` : 'Nao' }}
                        </ZyBadge>
                    </div>
                </div>
            </ZyCard>

            <!-- Cameras -->
            <ZyCard>
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                            Cameras ({{ cameras.length }}/{{ device.total_channels }})
                        </h3>
                        <ZyButton variant="secondary" size="sm" :href="`/cameras/create?device_id=${device.id}`">
                            <PlusIcon class="h-4 w-4" />
                            Adicionar Camera
                        </ZyButton>
                    </div>
                </template>

                <ZyTable v-if="cameras.length > 0" :columns="cameraColumns" :data="cameras as any[]" hoverable>
                    <template #cell-channel_number="{ row }">
                        <span class="font-mono text-body-sm">CH {{ (row as any).channel_number || '—' }}</span>
                    </template>
                    <template #cell-name="{ row }">
                        <a :href="`/cameras/${(row as any).id}`" class="text-body-sm font-medium text-[var(--zy-text-primary)] hover:underline">
                            {{ (row as any).name }}
                        </a>
                    </template>
                    <template #cell-channel_type="{ row }">
                        <ZyBadge variant="secondary">{{ channelTypeLabels[(row as any).channel_type] || (row as any).channel_type }}</ZyBadge>
                    </template>
                    <template #cell-location="{ row }">
                        <span class="text-body-sm text-dark-500">{{ (row as any).location || '—' }}</span>
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
                </ZyTable>

                <div v-else class="text-center py-8 text-dark-500">
                    <p class="text-body">Nenhuma camera vinculada a este dispositivo.</p>
                    <ZyButton variant="primary" size="sm" :href="`/cameras/create?device_id=${device.id}`" class="mt-3">
                        Adicionar primeira camera
                    </ZyButton>
                </div>
            </ZyCard>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Status</h3>
                </template>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Dispositivo</span>
                        <ZyBadge :variant="device.is_active ? 'success' : 'danger'" dot>
                            {{ device.is_active ? 'Ativo' : 'Inativo' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Conexao</span>
                        <ZyBadge :variant="device.is_online ? 'success' : 'secondary'" dot>
                            {{ device.is_online ? 'Online' : 'Offline' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Canais</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ device.active_channels }}/{{ device.total_channels }}</span>
                    </div>
                </div>
            </ZyCard>

            <!-- Storage -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Armazenamento</h3>
                </template>
                <div v-if="device.storage_used_gb !== null" class="space-y-2">
                    <p class="text-heading-md font-bold text-[var(--zy-text-primary)]">
                        {{ device.storage_used_gb }} GB
                    </p>
                    <p v-if="device.storage_updated_at" class="text-caption text-dark-500">
                        Atualizado em {{ new Date(device.storage_updated_at).toLocaleDateString('pt-BR') }}
                    </p>
                </div>
                <div v-else class="text-center py-4">
                    <p class="text-body-sm text-dark-400 italic">Dados nao disponiveis</p>
                    <p class="text-caption text-dark-400 mt-1">Sera preenchido via integracao</p>
                </div>
            </ZyCard>

            <!-- Pack -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Pack</h3>
                </template>
                <div class="space-y-3">
                    <div v-if="device.pack_model" class="mb-4">
                        <p class="text-caption font-medium text-dark-500 mb-1">Modelo</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-semibold">{{ device.pack_model }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">HLS</span>
                        <ZyBadge :variant="device.hls_enabled ? 'success' : 'secondary'">
                            {{ device.hls_enabled ? 'Ativo' : 'Inativo' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">HLS fMP4</span>
                        <ZyBadge :variant="device.hls_fmp4_enabled ? 'success' : 'secondary'">
                            {{ device.hls_fmp4_enabled ? 'Ativo' : 'Inativo' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Low Latency HLS</span>
                        <ZyBadge :variant="device.low_latency_hls_enabled ? 'success' : 'secondary'">
                            {{ device.low_latency_hls_enabled ? 'Ativo' : 'Inativo' }}
                        </ZyBadge>
                    </div>
                </div>
            </ZyCard>

            <!-- Location -->
            <ZyCard v-if="device.location">
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Localizacao</h3>
                </template>
                <p class="text-body text-[var(--zy-text-primary)]">{{ device.location }}</p>
            </ZyCard>

            <!-- Tenant -->
            <ZyCard v-if="device.tenant">
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Empresa</h3>
                </template>
                <p class="text-body text-[var(--zy-text-primary)]">{{ device.tenant.name }}</p>
            </ZyCard>
        </div>
    </div>

    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Dispositivo"
        :message="`Tem certeza que deseja excluir ${device.name}?`"
        confirm-text="Excluir"
        @confirm="deleteDevice"
        @cancel="showDeleteConfirm = false"
    />
</template>
