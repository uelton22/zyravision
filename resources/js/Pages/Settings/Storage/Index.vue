<script setup lang="ts">
/**
 * Settings > Storage Backends — Gerenciamento de backends de armazenamento
 */

import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge, ZyProgressBar,
    ZyConfirmDialog, ZyModal, ZyEmptyState, ZyAlert,
} from '@design';
import BackendForm from './BackendForm.vue';
import {
    PlusIcon, TrashIcon, PencilSquareIcon, StarIcon, BoltIcon,
    ServerStackIcon, CloudIcon, FolderIcon, SignalIcon,
    ExclamationTriangleIcon, CheckCircleIcon, XCircleIcon,
    ArrowPathIcon, Cog6ToothIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid';

defineOptions({ layout: AuthenticatedLayout });

interface StorageBackendItem {
    id: string;
    tenant_id: string | null;
    tenant_name: string;
    name: string;
    driver: string;
    driver_label: string;
    driver_category: string;
    is_active: boolean;
    is_default: boolean;
    priority: number;
    health_status: string;
    last_health_check_at: string | null;
    health_check_latency_ms: number | null;
    health_check_message: string | null;
    total_capacity_gb: number | null;
    used_capacity_gb: number | null;
    free_capacity_gb: number | null;
    usage_percent: number | null;
    total_files: number;
    safe_config: Record<string, any>;
    created_at: string | null;
}

interface DriverDef {
    value: string;
    label: string;
    category: string;
    config_fields: ConfigField[];
}

interface ConfigField {
    key: string;
    label: string;
    type: string;
    required: boolean;
    placeholder?: string;
    options?: string[];
    description?: string;
}

interface TenantOption {
    id: string;
    name: string;
}

interface Props {
    backends: StorageBackendItem[];
    drivers: DriverDef[];
    driverLabels: Record<string, string>;
    tenants?: TenantOption[];
    isSuperAdmin?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    tenants: () => [],
    isSuperAdmin: false,
});

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Configuracoes' },
    { label: 'Armazenamento' },
];

const showCreateModal = ref(false);
const editingBackend = ref<StorageBackendItem | null>(null);
const showDeleteConfirm = ref(false);
const deletingBackend = ref<StorageBackendItem | null>(null);
const testingId = ref<string | null>(null);
const testResult = ref<{ success: boolean; message: string } | null>(null);

// ─── Grouped Backends ───────────────────────────────────
const groupedBackends = computed(() => {
    const groups: Record<string, { label: string; icon: any; backends: StorageBackendItem[] }> = {
        local: { label: 'Armazenamento Local', icon: FolderIcon, backends: [] },
        network: { label: 'Armazenamento em Rede', icon: ServerStackIcon, backends: [] },
        cloud: { label: 'Armazenamento em Nuvem', icon: CloudIcon, backends: [] },
        other: { label: 'Outros', icon: Cog6ToothIcon, backends: [] },
    };

    props.backends.forEach((b) => {
        const cat = b.driver_category || 'other';
        if (groups[cat]) {
            groups[cat].backends.push(b);
        } else {
            groups.other.backends.push(b);
        }
    });

    return Object.entries(groups).filter(([, g]) => g.backends.length > 0);
});

const hasAnyBackend = computed(() => props.backends.length > 0);

// ─── Health Badge ──────────────────────────────────────
function healthVariant(status: string): 'success' | 'warning' | 'danger' | 'secondary' {
    switch (status) {
        case 'healthy': return 'success';
        case 'degraded': return 'warning';
        case 'offline': return 'danger';
        default: return 'secondary';
    }
}

function healthLabel(status: string): string {
    switch (status) {
        case 'healthy': return 'Saudavel';
        case 'degraded': return 'Degradado';
        case 'offline': return 'Offline';
        default: return 'Desconhecido';
    }
}

function healthIcon(status: string) {
    switch (status) {
        case 'healthy': return CheckCircleIcon;
        case 'degraded': return ExclamationTriangleIcon;
        case 'offline': return XCircleIcon;
        default: return SignalIcon;
    }
}

function driverIcon(category: string) {
    switch (category) {
        case 'local': return FolderIcon;
        case 'network': return ServerStackIcon;
        case 'cloud': return CloudIcon;
        default: return Cog6ToothIcon;
    }
}

// ─── Actions ───────────────────────────────────────────
function openCreate() {
    editingBackend.value = null;
    showCreateModal.value = true;
}

function openEdit(backend: StorageBackendItem) {
    editingBackend.value = backend;
    showCreateModal.value = true;
}

function closeModal() {
    showCreateModal.value = false;
    editingBackend.value = null;
}

function confirmDelete(backend: StorageBackendItem) {
    deletingBackend.value = backend;
    showDeleteConfirm.value = true;
}

function executeDelete() {
    if (! deletingBackend.value) return;
    router.delete(`/settings/storage/${deletingBackend.value.id}`, {
        onFinish: () => {
            showDeleteConfirm.value = false;
            deletingBackend.value = null;
        },
    });
}

function setAsDefault(backend: StorageBackendItem) {
    router.post(`/settings/storage/${backend.id}/set-default`);
}

async function testConnection(backend: StorageBackendItem) {
    testingId.value = backend.id;
    testResult.value = null;

    try {
        const response = await fetch(`/settings/storage/${backend.id}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='))?.split('=')[1] || ''),
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        testResult.value = { success: data.success, message: data.message };

        // Reload page to reflect updated health
        setTimeout(() => {
            router.reload({ only: ['backends'] });
        }, 1500);
    } catch {
        testResult.value = { success: false, message: 'Erro ao testar conexao' };
    } finally {
        setTimeout(() => {
            testingId.value = null;
            testResult.value = null;
        }, 4000);
    }
}

function formatDate(date: string | null): string {
    if (! date) return 'Nunca';
    return new Date(date).toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function formatSize(gb: number | null): string {
    if (gb === null || gb === undefined) return '-';
    if (gb >= 1024) return `${(gb / 1024).toFixed(1)} TB`;
    return `${Number(gb).toFixed(1)} GB`;
}
</script>

<template>
    <Head title="Configuracoes - Armazenamento" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Backends de Armazenamento" description="Gerencie onde as gravacoes sao armazenadas. Configure multiplos backends com failover automatico.">
        <template #actions>
            <ZyButton @click="openCreate">
                <PlusIcon class="h-4 w-4 mr-1.5" />
                Adicionar Backend
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Test Result Alert -->
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
    >
        <ZyAlert v-if="testResult" :variant="testResult.success ? 'success' : 'danger'" class="mb-4" dismissible @dismiss="testResult = null">
            {{ testResult.message }}
        </ZyAlert>
    </Transition>

    <!-- Empty State -->
    <ZyEmptyState
        v-if="!hasAnyBackend"
        title="Nenhum backend configurado"
        description="Configure um backend de armazenamento para comecar a salvar gravacoes. Suporte a armazenamento local, NAS, nuvem e mais."
        class="mt-8"
    >
        <template #action>
            <ZyButton @click="openCreate">
                <PlusIcon class="h-4 w-4 mr-1.5" />
                Configurar Primeiro Backend
            </ZyButton>
        </template>
    </ZyEmptyState>

    <!-- Backend Groups -->
    <div v-if="hasAnyBackend" class="space-y-8 mt-6">
        <div v-for="[category, group] in groupedBackends" :key="category">
            <!-- Category Header -->
            <div class="flex items-center gap-2 mb-3">
                <component :is="group.icon" class="h-5 w-5 text-dark-400" />
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">{{ group.label }}</h3>
                <ZyBadge variant="secondary" size="sm">{{ group.backends.length }}</ZyBadge>
            </div>

            <!-- Backend Cards Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <ZyCard
                    v-for="backend in group.backends"
                    :key="backend.id"
                    class="relative group hover:ring-1 hover:ring-primary-300 dark:hover:ring-primary-700 transition-all"
                >
                    <!-- Default Badge -->
                    <div v-if="backend.is_default" class="absolute top-3 right-3">
                        <StarIconSolid class="h-5 w-5 text-warning-500" title="Backend padrao" />
                    </div>

                    <div class="space-y-3">
                        <!-- Header -->
                        <div class="flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-dark-100 dark:bg-dark-800">
                                <component :is="driverIcon(backend.driver_category)" class="h-5 w-5 text-primary-500" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-body font-semibold text-[var(--zy-text-primary)] truncate">{{ backend.name }}</h4>
                                <p class="text-caption text-dark-500">{{ backend.driver_label }}</p>
                            </div>
                        </div>

                        <!-- Health Status -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <component :is="healthIcon(backend.health_status)" class="h-4 w-4" :class="{
                                    'text-success-500': backend.health_status === 'healthy',
                                    'text-warning-500': backend.health_status === 'degraded',
                                    'text-danger-500': backend.health_status === 'offline',
                                    'text-dark-400': backend.health_status === 'unknown',
                                }" />
                                <ZyBadge :variant="healthVariant(backend.health_status)" size="sm">
                                    {{ healthLabel(backend.health_status) }}
                                </ZyBadge>
                            </div>
                            <span v-if="backend.health_check_latency_ms" class="text-[11px] text-dark-400">
                                {{ backend.health_check_latency_ms }}ms
                            </span>
                        </div>

                        <!-- Capacity Bar -->
                        <div v-if="backend.total_capacity_gb" class="space-y-1">
                            <div class="flex justify-between text-[11px] text-dark-500">
                                <span>{{ formatSize(backend.used_capacity_gb) }} / {{ formatSize(backend.total_capacity_gb) }}</span>
                                <span v-if="backend.usage_percent !== null">{{ backend.usage_percent }}%</span>
                            </div>
                            <ZyProgressBar
                                :value="backend.usage_percent ?? 0"
                                :max="100"
                                :variant="(backend.usage_percent ?? 0) > 90 ? 'danger' : (backend.usage_percent ?? 0) > 75 ? 'warning' : 'primary'"
                                size="sm"
                            />
                        </div>
                        <p v-else class="text-[11px] text-dark-400 italic">Capacidade nao disponivel</p>

                        <!-- Last Check -->
                        <p class="text-[11px] text-dark-400">
                            Ultima verificacao: {{ formatDate(backend.last_health_check_at) }}
                        </p>

                        <!-- Actions -->
                        <div class="flex items-center gap-1.5 pt-2 border-t border-dark-200 dark:border-dark-700">
                            <ZyButton variant="ghost" size="sm" @click="testConnection(backend)" :loading="testingId === backend.id" :disabled="testingId !== null">
                                <BoltIcon class="h-3.5 w-3.5 mr-1" />
                                Testar
                            </ZyButton>
                            <ZyButton variant="ghost" size="sm" @click="openEdit(backend)">
                                <PencilSquareIcon class="h-3.5 w-3.5 mr-1" />
                                Editar
                            </ZyButton>
                            <ZyButton v-if="!backend.is_default" variant="ghost" size="sm" @click="setAsDefault(backend)">
                                <StarIcon class="h-3.5 w-3.5 mr-1" />
                                Padrao
                            </ZyButton>
                            <div class="flex-1" />
                            <ZyButton variant="ghost" size="sm" class="text-danger-500 hover:text-danger-600" @click="confirmDelete(backend)">
                                <TrashIcon class="h-3.5 w-3.5" />
                            </ZyButton>
                        </div>
                    </div>
                </ZyCard>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <ZyModal :show="showCreateModal" @close="closeModal" size="xl" :title="editingBackend ? `Editar ${editingBackend.name}` : 'Novo Backend de Armazenamento'">
        <BackendForm
            :backend="editingBackend"
            :drivers="drivers"
            :tenants="tenants"
            :is-super-admin="isSuperAdmin"
            @saved="closeModal"
            @cancel="closeModal"
        />
    </ZyModal>

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Backend"
        :message="`Tem certeza que deseja excluir o backend &quot;${deletingBackend?.name}&quot;? Esta acao nao pode ser desfeita.`"
        confirm-label="Excluir"
        variant="danger"
        @confirm="executeDelete"
        @cancel="showDeleteConfirm = false"
    />
</template>
