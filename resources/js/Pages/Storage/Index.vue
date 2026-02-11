<script setup lang="ts">
/**
 * Storage Index — Dashboard de armazenamento com dados reais de segmentos e backends.
 */

import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyTable, ZyBadge,
    ZyButton, ZyInput, ZySelect, ZyProgressBar, ZyTabs,
} from '@design';
import { useToast } from '@/Composables/useToast';
import {
    CircleStackIcon, ClockIcon, FilmIcon, ServerStackIcon,
    CloudIcon, VideoCameraIcon, SignalIcon, ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

const { addToast } = useToast();

interface BackendUsageRow {
    backend_id: string | null;
    backend_name: string;
    backend_driver: string;
    backend_active: boolean;
    segment_count: number;
    total_bytes: number;
    total_gb: number;
}

interface BackendRow {
    id: string;
    name: string;
    driver: string;
    is_default: boolean;
    priority: number;
}

interface CameraStorageRow {
    id: string;
    name: string;
    is_recording: boolean;
    continuous_recording: boolean;
    storage_bytes: number;
    storage_gb: number;
    segment_count: number;
    total_duration: number;
}

interface Props {
    storage: {
        total_bytes: number;
        total_gb: number;
        limit_gb: number;
        percentage: number;
        total_segments: number;
        total_duration: number;
        oldest_segment: string | null;
        newest_segment: string | null;
    };
    backendUsage: BackendUsageRow[];
    backends: BackendRow[];
    cameraStorage: CameraStorageRow[];
    retentionDays: number;
    planLimits: {
        max_storage_gb: number;
        max_retention_days: number;
    };
    tenants?: { id: string; name: string }[];
    isSuperAdmin?: boolean;
    currentTenantId?: string;
    currentTenantName?: string;
}

const props = withDefaults(defineProps<Props>(), {
    tenants: () => [],
    isSuperAdmin: false,
});

const tenantFilter = ref(props.currentTenantId || '');

const tenantOptions = [
    { value: '', label: 'Selecionar empresa' },
    ...(props.tenants || []).map((t) => ({ value: t.id, label: t.name })),
];

function changeTenant() {
    if (tenantFilter.value) {
        router.get('/storage', { tenant_id: tenantFilter.value }, { preserveState: true, replace: true });
    }
}

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Armazenamento' },
];

const activeTab = ref('overview');

const tabs = [
    { key: 'overview', label: 'Visao Geral', icon: CircleStackIcon },
    { key: 'backends', label: 'Backends', icon: CloudIcon, badge: props.backends.length },
    { key: 'cameras', label: 'Por Camera', icon: VideoCameraIcon, badge: props.cameraStorage.length },
    { key: 'retention', label: 'Retencao', icon: ClockIcon },
];

const retentionForm = useForm({
    retention_days: props.retentionDays,
    tenant_id: props.currentTenantId || '',
});

function saveRetention() {
    retentionForm.put('/storage/retention', {
        preserveScroll: true,
        onSuccess: () => {
            addToast({ type: 'success', title: 'Sucesso', message: 'Politica de retencao atualizada.' });
        },
        onError: () => {
            addToast({ type: 'error', title: 'Erro', message: 'Erro ao atualizar retencao.', duration: 8000 });
        },
    });
}

const cameraColumns = [
    { key: 'name', label: 'Camera' },
    { key: 'storage', label: 'Armazenamento' },
    { key: 'segments', label: 'Segmentos' },
    { key: 'duration', label: 'Duracao' },
    { key: 'status', label: 'Status' },
];

// ─── Formatters ───────────────────────
function formatBytes(bytes: number): string {
    if (!bytes || bytes === 0) return '0 B';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function formatGb(gb: number): string {
    if (gb < 0.01) return '0 MB';
    if (gb < 1) return `${Math.round(gb * 1024)} MB`;
    return `${gb.toFixed(2)} GB`;
}

function formatDuration(seconds: number): string {
    if (!seconds) return '--';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return d.toLocaleDateString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

const driverLabels: Record<string, string> = {
    local: 'Local',
    minio: 'MinIO (S3)',
    s3: 'Amazon S3',
    ftp: 'FTP',
    sftp: 'SFTP',
    nfs: 'NFS',
};

const driverColors: Record<string, string> = {
    local: 'bg-slate-500',
    minio: 'bg-orange-500',
    s3: 'bg-amber-500',
    ftp: 'bg-blue-500',
    sftp: 'bg-indigo-500',
    nfs: 'bg-green-500',
};

// Total from backends that have data
const totalBackendBytes = props.backendUsage.reduce((sum, b) => sum + b.total_bytes, 0);
</script>

<template>
    <Head title="Armazenamento" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Armazenamento"
        :description="isSuperAdmin ? `${currentTenantName || 'Todas as empresas'}` : 'Gerencie o armazenamento de gravacoes'"
    >
        <template v-if="isSuperAdmin" #actions>
            <div class="w-56">
                <ZySelect
                    v-model="tenantFilter"
                    :options="tenantOptions"
                    placeholder="Empresa"
                    @update:model-value="changeTenant"
                />
            </div>
        </template>
    </ZyPageHeader>

    <div class="mt-6">
        <ZyTabs v-model="activeTab" :tabs="tabs">
            <!-- ═══ Overview Tab ═══ -->
            <template #overview>
                <div class="space-y-6">
                    <!-- Main storage bar -->
                    <ZyCard>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <CircleStackIcon class="h-5 w-5 text-indigo-400" />
                                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Uso Total de Armazenamento</h3>
                            </div>
                        </template>
                        <div class="space-y-4">
                            <ZyProgressBar
                                :value="storage.total_gb"
                                :max="storage.limit_gb"
                                variant="auto"
                                size="lg"
                                show-label
                                :label="`${formatGb(storage.total_gb)} de ${formatGb(storage.limit_gb)}`"
                            />
                            <p class="text-body-sm text-dark-500">
                                Limite definido pelo plano: {{ storage.limit_gb }} GB
                                &middot; {{ storage.percentage }}% utilizado
                            </p>
                        </div>
                    </ZyCard>

                    <!-- Stats cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-indigo-500/10">
                                    <FilmIcon class="h-5 w-5 text-indigo-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Segmentos</p>
                                    <p class="text-lg font-bold text-white">{{ storage.total_segments.toLocaleString('pt-BR') }}</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-blue-500/10">
                                    <ClockIcon class="h-5 w-5 text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Duracao Total</p>
                                    <p class="text-lg font-bold text-white">{{ formatDuration(storage.total_duration) }}</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-green-500/10">
                                    <ArrowTrendingUpIcon class="h-5 w-5 text-green-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Retencao</p>
                                    <p class="text-lg font-bold text-white">{{ retentionDays }} dias</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-amber-500/10">
                                    <ServerStackIcon class="h-5 w-5 text-amber-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Backends Ativos</p>
                                    <p class="text-lg font-bold text-white">{{ backends.length }}</p>
                                </div>
                            </div>
                        </ZyCard>
                    </div>

                    <!-- Storage per backend -->
                    <ZyCard v-if="backendUsage.length > 0">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <CloudIcon class="h-5 w-5 text-blue-400" />
                                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Uso por Backend de Armazenamento</h3>
                            </div>
                        </template>
                        <div class="space-y-4">
                            <div
                                v-for="bu in backendUsage"
                                :key="bu.backend_id ?? 'local'"
                                class="flex items-center gap-4 p-3 rounded-lg bg-dark-800/50"
                            >
                                <div :class="['w-3 h-3 rounded-full shrink-0', driverColors[bu.backend_driver] || 'bg-gray-500']" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-body font-medium text-white truncate">{{ bu.backend_name }}</p>
                                        <ZyBadge variant="secondary" class="text-[10px]">{{ driverLabels[bu.backend_driver] || bu.backend_driver }}</ZyBadge>
                                        <ZyBadge v-if="bu.backend_active" variant="success" class="text-[10px]">Ativo</ZyBadge>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-dark-400">
                                        <span>{{ bu.segment_count.toLocaleString('pt-BR') }} segmentos</span>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-body font-semibold text-white">{{ formatBytes(bu.total_bytes) }}</p>
                                    <p v-if="totalBackendBytes > 0" class="text-xs text-dark-500">
                                        {{ Math.round((bu.total_bytes / totalBackendBytes) * 100) }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </ZyCard>

                    <!-- Top cameras -->
                    <ZyCard v-if="cameraStorage.length > 0">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <VideoCameraIcon class="h-5 w-5 text-green-400" />
                                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Cameras com Maior Uso</h3>
                            </div>
                        </template>
                        <div class="space-y-3">
                            <div
                                v-for="cam in cameraStorage.slice(0, 5)"
                                :key="cam.id"
                                class="flex items-center gap-4"
                            >
                                <div class="flex-1 min-w-0">
                                    <a :href="`/cameras/${cam.id}`" class="text-body font-medium text-white hover:text-primary-400 truncate block">
                                        {{ cam.name }}
                                    </a>
                                    <p class="text-body-sm text-dark-500">
                                        {{ cam.segment_count }} segmentos &middot; {{ formatDuration(cam.total_duration) }}
                                    </p>
                                </div>
                                <div class="w-32 flex-shrink-0">
                                    <ZyProgressBar
                                        :value="cam.storage_gb"
                                        :max="storage.limit_gb"
                                        size="sm"
                                        variant="primary"
                                    />
                                </div>
                                <span class="text-body-sm font-medium text-white w-20 text-right">
                                    {{ formatBytes(cam.storage_bytes) }}
                                </span>
                            </div>
                        </div>
                    </ZyCard>

                    <!-- Dates -->
                    <div class="grid sm:grid-cols-2 gap-4">
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Gravacao Mais Antiga</p>
                                <p class="text-body font-bold text-white mt-1">
                                    {{ formatDate(storage.oldest_segment) }}
                                </p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Gravacao Mais Recente</p>
                                <p class="text-body font-bold text-white mt-1">
                                    {{ formatDate(storage.newest_segment) }}
                                </p>
                            </div>
                        </ZyCard>
                    </div>
                </div>
            </template>

            <!-- ═══ Backends Tab ═══ -->
            <template #backends>
                <div class="space-y-6">
                    <!-- Configured backends -->
                    <ZyCard>
                        <template #header>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <CloudIcon class="h-5 w-5 text-blue-400" />
                                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Backends Configurados</h3>
                                </div>
                                <ZyButton variant="secondary" size="sm" href="/settings/storage">
                                    Configurar Backends
                                </ZyButton>
                            </div>
                        </template>
                        <div v-if="backends.length > 0" class="space-y-3">
                            <div
                                v-for="b in backends"
                                :key="b.id"
                                class="flex items-center justify-between p-3 rounded-lg bg-dark-800/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="['w-3 h-3 rounded-full', driverColors[b.driver] || 'bg-gray-500']" />
                                    <div>
                                        <p class="text-body font-medium text-white">{{ b.name }}</p>
                                        <p class="text-xs text-dark-500">{{ driverLabels[b.driver] || b.driver }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <ZyBadge v-if="b.is_default" variant="primary">Padrao</ZyBadge>
                                    <ZyBadge variant="success">Ativo</ZyBadge>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <CloudIcon class="h-10 w-10 text-dark-600 mx-auto mb-3" />
                            <p class="text-body text-dark-400">Nenhum backend ativo configurado.</p>
                            <ZyButton variant="primary" size="sm" href="/settings/storage" class="mt-3">
                                Configurar
                            </ZyButton>
                        </div>
                    </ZyCard>

                    <!-- Usage per backend -->
                    <ZyCard v-if="backendUsage.length > 0">
                        <template #header>
                            <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Uso de Armazenamento por Backend</h3>
                        </template>
                        <div class="space-y-4">
                            <div
                                v-for="bu in backendUsage"
                                :key="bu.backend_id ?? 'local'"
                                class="space-y-2"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div :class="['w-2.5 h-2.5 rounded-full', driverColors[bu.backend_driver] || 'bg-gray-500']" />
                                        <span class="text-body-sm font-medium text-white">{{ bu.backend_name }}</span>
                                        <span class="text-xs text-dark-500">({{ driverLabels[bu.backend_driver] || bu.backend_driver }})</span>
                                    </div>
                                    <span class="text-body-sm font-semibold text-white">{{ formatBytes(bu.total_bytes) }}</span>
                                </div>
                                <ZyProgressBar
                                    :value="bu.total_bytes"
                                    :max="totalBackendBytes || 1"
                                    size="sm"
                                    variant="primary"
                                />
                                <p class="text-xs text-dark-500">
                                    {{ bu.segment_count.toLocaleString('pt-BR') }} segmentos
                                </p>
                            </div>
                        </div>
                    </ZyCard>
                </div>
            </template>

            <!-- ═══ Per Camera Tab ═══ -->
            <template #cameras>
                <ZyCard>
                    <ZyTable :columns="cameraColumns" :data="cameraStorage" hoverable>
                        <template #cell-name="{ row }">
                            <div class="flex items-center gap-2">
                                <a :href="`/cameras/${row.id}`" class="text-primary-400 hover:text-primary-300 font-medium">
                                    {{ row.name }}
                                </a>
                                <ZyButton
                                    variant="ghost"
                                    size="sm"
                                    :href="`/cameras/${row.id}/recordings`"
                                    title="Ver gravacoes"
                                    class="opacity-60 hover:opacity-100"
                                >
                                    <FilmIcon class="h-3.5 w-3.5" />
                                </ZyButton>
                            </div>
                        </template>
                        <template #cell-storage="{ row }">
                            <div class="flex items-center gap-3">
                                <ZyProgressBar
                                    :value="row.storage_gb"
                                    :max="storage.limit_gb"
                                    size="sm"
                                    variant="primary"
                                    class="w-24"
                                />
                                <span class="text-body-sm text-white">{{ formatBytes(row.storage_bytes) }}</span>
                            </div>
                        </template>
                        <template #cell-segments="{ row }">
                            <span class="text-body-sm text-white font-mono">{{ row.segment_count }}</span>
                        </template>
                        <template #cell-duration="{ row }">
                            <span class="text-body-sm text-dark-400 font-mono">{{ formatDuration(row.total_duration) }}</span>
                        </template>
                        <template #cell-status="{ row }">
                            <div class="flex items-center gap-1.5">
                                <ZyBadge v-if="row.is_recording" variant="danger" dot>Gravando</ZyBadge>
                                <ZyBadge v-else-if="row.continuous_recording" variant="success">Continua</ZyBadge>
                                <ZyBadge v-else variant="secondary">Inativo</ZyBadge>
                            </div>
                        </template>
                    </ZyTable>
                </ZyCard>
            </template>

            <!-- ═══ Retention Tab ═══ -->
            <template #retention>
                <div class="max-w-lg space-y-6">
                    <ZyCard>
                        <template #header>
                            <div class="flex items-center gap-2">
                                <ClockIcon class="h-5 w-5 text-amber-400" />
                                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Politica de Retencao</h3>
                            </div>
                        </template>
                        <div class="space-y-4">
                            <p class="text-body text-dark-400">
                                Segmentos de gravacao mais antigos que o periodo de retencao serao removidos automaticamente.
                                O limite maximo do plano e <strong class="text-white">{{ planLimits.max_retention_days }} dias</strong>.
                            </p>

                            <div class="rounded-lg border border-dark-700 bg-dark-800/30 p-3">
                                <p class="text-xs text-dark-400">
                                    <strong class="text-dark-300">Como funciona:</strong>
                                    A retencao e configurada por empresa (tenant). O super admin pode definir valores diferentes
                                    para cada empresa. O plano define o limite maximo de dias. O job de limpeza roda diariamente
                                    e remove segmentos expirados do storage remoto e do banco de dados.
                                </p>
                            </div>

                            <ZyInput
                                v-model.number="retentionForm.retention_days"
                                type="number"
                                label="Dias de retencao"
                                hint="Segmentos mais antigos serao excluidos automaticamente."
                                :error="retentionForm.errors.retention_days"
                            />

                            <div class="rounded-lg border border-dark-700 bg-dark-800/30 p-3">
                                <p class="text-xs text-dark-400">
                                    <strong class="text-dark-300">Estimativa:</strong>
                                    Com 1 camera gravando 24h/dia (~500MB/hora),
                                    {{ retentionForm.retention_days }} dias requerem aproximadamente
                                    <strong class="text-white">
                                        {{ Math.round((500 * 24 * retentionForm.retention_days) / 1024) }} GB
                                    </strong> de armazenamento.
                                </p>
                            </div>

                            <ZyButton
                                :loading="retentionForm.processing"
                                @click="saveRetention"
                            >
                                Salvar Retencao
                            </ZyButton>
                        </div>
                    </ZyCard>
                </div>
            </template>
        </ZyTabs>
    </div>
</template>
