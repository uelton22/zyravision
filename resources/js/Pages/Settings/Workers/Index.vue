<script setup lang="ts">
/**
 * Settings > Workers — Dashboard de monitoramento de workers
 *
 * Exibe status em tempo real de:
 * - Transcoder Workers (FFmpeg)
 * - Queue Workers (Horizon)
 * - Sistema (CPU, RAM, Disco, Redis)
 * - Alertas do sistema
 */

import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader,
    ZyBreadcrumb,
    ZyCard,
    ZyBadge,
    ZyAlert,
    ZyButton,
} from '@design';
import {
    CpuChipIcon,
    ServerStackIcon,
    FilmIcon,
    QueueListIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    SignalIcon,
    ClockIcon,
    VideoCameraIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

// ─── Types ───────────────────────────────────────────────

interface TranscoderWorker {
    worker_id: string;
    hostname: string;
    active_cameras: number;
    max_cameras: number;
    updated_at: string | null;
    status: 'healthy' | 'stale';
    usage_percent: number;
}

interface TranscodingCamera {
    camera_id: string;
    camera_name: string;
    quality: string;
    status: string;
    worker_id: string;
    pid: number | null;
    started_at: string | null;
    uptime: string | null;
}

interface QueueInfo {
    name: string;
    size: number;
    status: 'healthy' | 'busy' | 'overloaded';
}

interface Supervisor {
    name: string;
    full_name: string;
    status: string;
    processes: number;
    queue: string;
}

interface QueueStatus {
    status: string;
    supervisors: Supervisor[];
    total_processes: number;
    jobs_per_minute: number;
    recent_failed: number;
    queues: QueueInfo[];
}

interface SystemMetrics {
    disk: { total_gb: number; free_gb: number; used_percent: number };
    recordings: { total_gb: number; free_gb: number; used_percent: number };
    memory: { usage_mb: number; peak_mb: number };
    cpu_load: number[];
    php_version: string;
    laravel_version: string;
    uptime: string;
    redis: {
        connected_clients: number;
        used_memory_human: string;
        uptime_in_days: number;
        status: string;
    };
}

interface WorkerAlert {
    type: 'danger' | 'warning' | 'info';
    title: string;
    message: string;
    category: string;
}

interface WorkersData {
    transcoders: TranscoderWorker[];
    cameras_transcoding: TranscodingCamera[];
    queue: QueueStatus;
    system: SystemMetrics;
    alerts: WorkerAlert[];
}

interface Props {
    workers: WorkersData;
}

const props = defineProps<Props>();

// ─── State ───────────────────────────────────────────────

const data = ref<WorkersData>(props.workers);
const isRefreshing = ref(false);
const autoRefresh = ref(true);
let refreshInterval: ReturnType<typeof setInterval> | null = null;

// ─── Computed ────────────────────────────────────────────

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Configuracoes' },
    { label: 'Workers' },
];

const totalTranscoderCapacity = computed(() => {
    const workers = data.value.transcoders;
    if (workers.length === 0) return { used: 0, total: 0, percent: 0 };
    const used = workers.reduce((sum, w) => sum + w.active_cameras, 0);
    const total = workers.reduce((sum, w) => sum + w.max_cameras, 0);
    return { used, total, percent: total > 0 ? Math.round(used / total * 100) : 0 };
});

const healthyTranscoders = computed(() =>
    data.value.transcoders.filter(w => w.status === 'healthy').length
);

const totalQueueJobs = computed(() =>
    data.value.queue.queues.reduce((sum, q) => sum + q.size, 0)
);

const systemHealthStatus = computed(() => {
    const alerts = data.value.alerts;
    if (alerts.some(a => a.type === 'danger')) return 'critical';
    if (alerts.some(a => a.type === 'warning')) return 'warning';
    return 'healthy';
});

const systemHealthLabel = computed(() => {
    switch (systemHealthStatus.value) {
        case 'critical': return 'Critico';
        case 'warning': return 'Atencao';
        default: return 'Saudavel';
    }
});

const systemHealthVariant = computed(() => {
    switch (systemHealthStatus.value) {
        case 'critical': return 'danger';
        case 'warning': return 'warning';
        default: return 'success';
    }
});

// ─── Actions ─────────────────────────────────────────────

async function refreshData() {
    isRefreshing.value = true;
    try {
        const response = await fetch('/settings/workers/api');
        const json = await response.json();
        if (json.success) {
            data.value = json.data;
        }
    } catch (e) {
        // silently fail
    } finally {
        isRefreshing.value = false;
    }
}

function toggleAutoRefresh() {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
}

function startAutoRefresh() {
    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(refreshData, 10000);
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
    }
}

onMounted(() => {
    if (autoRefresh.value) {
        startAutoRefresh();
    }
});

onUnmounted(() => {
    stopAutoRefresh();
});

// ─── Helpers ─────────────────────────────────────────────

function getUsageColor(percent: number): string {
    if (percent >= 90) return 'bg-danger-500';
    if (percent >= 70) return 'bg-warning-500';
    return 'bg-secondary-500';
}

function getUsageTextColor(percent: number): string {
    if (percent >= 90) return 'text-danger-400';
    if (percent >= 70) return 'text-warning-400';
    return 'text-secondary-400';
}

function getQueueBadgeVariant(status: string): 'success' | 'warning' | 'danger' {
    switch (status) {
        case 'overloaded': return 'danger';
        case 'busy': return 'warning';
        default: return 'success';
    }
}

function qualityLabel(quality: string): string {
    const map: Record<string, string> = {
        low: '480p',
        medium: '720p',
        high: '1080p',
        original: 'Original',
    };
    return map[quality] || quality;
}
</script>

<template>
    <Head title="Workers - Monitoramento" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Workers do Sistema"
        description="Monitoramento em tempo real de transcoders, filas e recursos do sistema."
    >
        <template #actions>
            <div class="flex items-center gap-3">
                <ZyBadge :variant="systemHealthVariant" dot>
                    {{ systemHealthLabel }}
                </ZyBadge>
                <ZyButton
                    variant="ghost"
                    size="sm"
                    :class="{ 'text-primary-400': autoRefresh }"
                    @click="toggleAutoRefresh"
                >
                    <ClockIcon class="mr-1 h-4 w-4" />
                    Auto {{ autoRefresh ? 'ON' : 'OFF' }}
                </ZyButton>
                <ZyButton
                    variant="secondary"
                    size="sm"
                    :disabled="isRefreshing"
                    @click="refreshData"
                >
                    <ArrowPathIcon
                        class="mr-1 h-4 w-4"
                        :class="{ 'animate-spin': isRefreshing }"
                    />
                    Atualizar
                </ZyButton>
            </div>
        </template>
    </ZyPageHeader>

    <div class="mt-6 space-y-6">
        <!-- Alerts -->
        <div v-if="data.alerts.length > 0" class="space-y-2">
            <ZyAlert
                v-for="(alert, idx) in data.alerts"
                :key="idx"
                :variant="alert.type === 'danger' ? 'error' : alert.type"
            >
                <div class="flex items-center gap-2">
                    <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                    <span>
                        <strong>{{ alert.title }}:</strong> {{ alert.message }}
                    </span>
                </div>
            </ZyAlert>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Transcoders -->
            <ZyCard padding="md">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg flex items-center justify-center bg-indigo-900/30 shrink-0">
                        <FilmIcon class="h-6 w-6 text-indigo-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-[var(--zy-text-muted)] font-medium">Transcoders</p>
                        <p class="text-2xl font-bold text-white">
                            {{ healthyTranscoders }}<span class="text-sm text-[var(--zy-text-muted)]">/{{ data.transcoders.length }}</span>
                        </p>
                        <p class="text-xs" :class="getUsageTextColor(totalTranscoderCapacity.percent)">
                            {{ totalTranscoderCapacity.used }}/{{ totalTranscoderCapacity.total }} cameras
                        </p>
                    </div>
                </div>
            </ZyCard>

            <!-- Queue Workers -->
            <ZyCard padding="md">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg flex items-center justify-center bg-blue-900/30 shrink-0">
                        <QueueListIcon class="h-6 w-6 text-blue-400" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-[var(--zy-text-muted)] font-medium">Queue (Horizon)</p>
                        <p class="text-2xl font-bold text-white">
                            {{ data.queue.total_processes }}
                            <span class="text-sm text-[var(--zy-text-muted)]">processos</span>
                        </p>
                        <p class="text-xs text-blue-400">
                            {{ data.queue.jobs_per_minute }} jobs/min
                        </p>
                    </div>
                </div>
            </ZyCard>

            <!-- Pending Jobs -->
            <ZyCard padding="md">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg flex items-center justify-center shrink-0"
                        :class="totalQueueJobs > 50 ? 'bg-danger-900/30' : totalQueueJobs > 10 ? 'bg-warning-900/30' : 'bg-secondary-900/30'"
                    >
                        <ServerStackIcon class="h-6 w-6"
                            :class="totalQueueJobs > 50 ? 'text-danger-400' : totalQueueJobs > 10 ? 'text-warning-400' : 'text-secondary-400'"
                        />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-[var(--zy-text-muted)] font-medium">Jobs Pendentes</p>
                        <p class="text-2xl font-bold text-white">{{ totalQueueJobs }}</p>
                        <p class="text-xs text-[var(--zy-text-muted)]">
                            {{ data.queue.recent_failed }} falhas recentes
                        </p>
                    </div>
                </div>
            </ZyCard>

            <!-- System Health -->
            <ZyCard padding="md">
                <div class="flex items-center gap-4">
                    <div class="h-12 w-12 rounded-lg flex items-center justify-center shrink-0"
                        :class="systemHealthStatus === 'critical' ? 'bg-danger-900/30' : systemHealthStatus === 'warning' ? 'bg-warning-900/30' : 'bg-secondary-900/30'"
                    >
                        <CpuChipIcon class="h-6 w-6"
                            :class="systemHealthStatus === 'critical' ? 'text-danger-400' : systemHealthStatus === 'warning' ? 'text-warning-400' : 'text-secondary-400'"
                        />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs text-[var(--zy-text-muted)] font-medium">Sistema</p>
                        <p class="text-2xl font-bold text-white">{{ data.system.uptime }}</p>
                        <p class="text-xs text-[var(--zy-text-muted)]">
                            CPU: {{ data.system.cpu_load[0]?.toFixed(2) || '0.00' }}
                        </p>
                    </div>
                </div>
            </ZyCard>
        </div>

        <!-- Transcoder Workers -->
        <ZyCard>
            <template #header>
                <div class="flex items-center gap-2">
                    <FilmIcon class="h-5 w-5 text-indigo-400" />
                    <h3 class="text-lg font-semibold text-white">Transcoder Workers (FFmpeg)</h3>
                    <ZyBadge variant="secondary" class="ml-auto text-xs">
                        {{ data.transcoders.length }} worker(s)
                    </ZyBadge>
                </div>
            </template>

            <div v-if="data.transcoders.length > 0" class="space-y-3">
                <div
                    v-for="worker in data.transcoders"
                    :key="worker.worker_id"
                    class="flex items-center gap-4 rounded-lg bg-[var(--zy-surface-tertiary)] p-4"
                >
                    <div class="shrink-0">
                        <div class="h-3 w-3 rounded-full"
                            :class="worker.status === 'healthy' ? 'bg-secondary-500' : 'bg-danger-500'"
                        />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="text-sm font-medium text-white truncate">{{ worker.worker_id }}</p>
                            <ZyBadge :variant="worker.status === 'healthy' ? 'success' : 'danger'" class="text-[10px]">
                                {{ worker.status === 'healthy' ? 'Ativo' : 'Inativo' }}
                            </ZyBadge>
                        </div>
                        <p class="text-xs text-[var(--zy-text-muted)]">
                            Host: {{ worker.hostname }} &middot;
                            {{ worker.active_cameras }}/{{ worker.max_cameras }} cameras
                        </p>
                    </div>
                    <div class="shrink-0 w-32">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-[var(--zy-text-muted)]">Capacidade</span>
                            <span :class="getUsageTextColor(worker.usage_percent)" class="font-medium">
                                {{ worker.usage_percent }}%
                            </span>
                        </div>
                        <div class="h-2 bg-dark-700 rounded-full overflow-hidden">
                            <div
                                :class="['h-full rounded-full transition-all duration-500', getUsageColor(worker.usage_percent)]"
                                :style="{ width: Math.min(100, worker.usage_percent) + '%' }"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-8">
                <FilmIcon class="h-10 w-10 text-dark-500 mx-auto mb-2" />
                <p class="text-sm text-[var(--zy-text-muted)]">Nenhum transcoder worker ativo</p>
                <p class="text-xs text-dark-600 mt-1">Configure TRANSCODER_REPLICAS no .env</p>
            </div>
        </ZyCard>

        <!-- Cameras em Transcodificacao -->
        <ZyCard v-if="data.cameras_transcoding.length > 0">
            <template #header>
                <div class="flex items-center gap-2">
                    <VideoCameraIcon class="h-5 w-5 text-indigo-400" />
                    <h3 class="text-lg font-semibold text-white">Cameras em Transcodificacao</h3>
                    <ZyBadge variant="primary" class="ml-auto text-xs">
                        {{ data.cameras_transcoding.length }} ativa(s)
                    </ZyBadge>
                </div>
            </template>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-[var(--zy-text-muted)] uppercase tracking-wider border-b border-dark-700">
                            <th class="pb-3 pr-4">Camera</th>
                            <th class="pb-3 pr-4">Qualidade</th>
                            <th class="pb-3 pr-4">Status</th>
                            <th class="pb-3 pr-4">Worker</th>
                            <th class="pb-3 pr-4">PID</th>
                            <th class="pb-3">Uptime</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="cam in data.cameras_transcoding"
                            :key="cam.camera_id"
                            class="border-b border-dark-800 last:border-0"
                        >
                            <td class="py-3 pr-4">
                                <a :href="`/cameras/${cam.camera_id}`" class="text-primary-400 hover:text-primary-300 font-medium">
                                    {{ cam.camera_name }}
                                </a>
                            </td>
                            <td class="py-3 pr-4">
                                <ZyBadge variant="secondary" class="text-[10px]">
                                    {{ qualityLabel(cam.quality) }}
                                </ZyBadge>
                            </td>
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full"
                                        :class="cam.status === 'running' ? 'bg-secondary-500 animate-pulse' : cam.status === 'pending' ? 'bg-warning-500' : 'bg-dark-500'"
                                    />
                                    <span class="text-xs">{{ cam.status === 'running' ? 'Gravando' : cam.status === 'pending' ? 'Aguardando' : cam.status }}</span>
                                </div>
                            </td>
                            <td class="py-3 pr-4 text-xs text-[var(--zy-text-muted)] font-mono">
                                {{ cam.worker_id.substring(0, 12) }}...
                            </td>
                            <td class="py-3 pr-4 text-xs font-mono text-[var(--zy-text-muted)]">
                                {{ cam.pid || '-' }}
                            </td>
                            <td class="py-3 text-xs text-[var(--zy-text-muted)]">
                                {{ cam.uptime || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </ZyCard>

        <!-- Queue Status + System Info side by side -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- Queue Workers (Horizon) -->
            <ZyCard>
                <template #header>
                    <div class="flex items-center gap-2">
                        <QueueListIcon class="h-5 w-5 text-blue-400" />
                        <h3 class="text-lg font-semibold text-white">Queue Workers (Horizon)</h3>
                        <ZyBadge
                            :variant="data.queue.status === 'running' ? 'success' : 'danger'"
                            dot
                            class="ml-auto text-xs"
                        >
                            {{ data.queue.status === 'running' ? 'Ativo' : 'Inativo' }}
                        </ZyBadge>
                    </div>
                </template>

                <!-- Supervisors -->
                <div class="space-y-3 mb-4">
                    <p class="text-xs font-medium text-[var(--zy-text-muted)] uppercase tracking-wider">Supervisors</p>
                    <div v-if="data.queue.supervisors.length > 0" class="space-y-2">
                        <div
                            v-for="sup in data.queue.supervisors"
                            :key="sup.full_name"
                            class="flex items-center justify-between rounded-lg bg-[var(--zy-surface-tertiary)] p-3"
                        >
                            <div>
                                <p class="text-sm font-medium text-white">{{ sup.name }}</p>
                                <p class="text-xs text-[var(--zy-text-muted)]">Fila: {{ sup.queue }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-white">{{ sup.processes }}</p>
                                <p class="text-[10px] text-[var(--zy-text-muted)]">processos</p>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-dark-500">Nenhum supervisor encontrado.</p>
                </div>

                <!-- Queue Sizes -->
                <div class="space-y-3">
                    <p class="text-xs font-medium text-[var(--zy-text-muted)] uppercase tracking-wider">Filas</p>
                    <div class="space-y-2">
                        <div
                            v-for="queue in data.queue.queues"
                            :key="queue.name"
                            class="flex items-center justify-between rounded bg-[var(--zy-surface-tertiary)] px-3 py-2"
                        >
                            <span class="text-sm text-white">{{ queue.name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold"
                                    :class="queue.size > 50 ? 'text-danger-400' : queue.size > 10 ? 'text-warning-400' : 'text-[var(--zy-text-muted)]'"
                                >
                                    {{ queue.size }}
                                </span>
                                <ZyBadge :variant="getQueueBadgeVariant(queue.status)" class="text-[10px]">
                                    {{ queue.status === 'overloaded' ? 'Lotada' : queue.status === 'busy' ? 'Ocupada' : 'OK' }}
                                </ZyBadge>
                            </div>
                        </div>
                    </div>
                </div>
            </ZyCard>

            <!-- System Metrics -->
            <ZyCard>
                <template #header>
                    <div class="flex items-center gap-2">
                        <CpuChipIcon class="h-5 w-5 text-amber-400" />
                        <h3 class="text-lg font-semibold text-white">Sistema</h3>
                    </div>
                </template>

                <div class="space-y-4">
                    <!-- CPU Load -->
                    <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-3">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-white">CPU Load</p>
                            <p class="text-sm font-mono text-[var(--zy-text-muted)]">
                                {{ data.system.cpu_load.map(v => v?.toFixed(2) || '0.00').join(' / ') }}
                            </p>
                        </div>
                        <p class="text-[10px] text-dark-500">1 min / 5 min / 15 min</p>
                    </div>

                    <!-- Disk -->
                    <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-3">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-medium text-white">Disco</p>
                            <p class="text-xs" :class="getUsageTextColor(data.system.disk.used_percent)">
                                {{ data.system.disk.used_percent }}% usado
                            </p>
                        </div>
                        <div class="h-2 bg-dark-700 rounded-full overflow-hidden mb-1">
                            <div
                                :class="['h-full rounded-full', getUsageColor(data.system.disk.used_percent)]"
                                :style="{ width: data.system.disk.used_percent + '%' }"
                            />
                        </div>
                        <p class="text-[10px] text-dark-500">
                            {{ data.system.disk.free_gb }}GB livre de {{ data.system.disk.total_gb }}GB
                        </p>
                    </div>

                    <!-- Redis -->
                    <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-3">
                        <div class="flex items-center justify-between mb-1">
                            <p class="text-sm font-medium text-white">Redis</p>
                            <ZyBadge :variant="data.system.redis.status === 'connected' ? 'success' : 'danger'" class="text-[10px]">
                                {{ data.system.redis.status === 'connected' ? 'Conectado' : 'Desconectado' }}
                            </ZyBadge>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs text-[var(--zy-text-muted)]">
                            <div>
                                <p class="font-medium text-white">{{ data.system.redis.connected_clients }}</p>
                                <p>Clientes</p>
                            </div>
                            <div>
                                <p class="font-medium text-white">{{ data.system.redis.used_memory_human }}</p>
                                <p>Memoria</p>
                            </div>
                            <div>
                                <p class="font-medium text-white">{{ data.system.redis.uptime_in_days }}d</p>
                                <p>Uptime</p>
                            </div>
                        </div>
                    </div>

                    <!-- Versions -->
                    <div class="flex items-center justify-between text-xs text-[var(--zy-text-muted)] pt-2 border-t border-dark-700">
                        <span>PHP {{ data.system.php_version }}</span>
                        <span>Laravel {{ data.system.laravel_version }}</span>
                        <span>Uptime: {{ data.system.uptime }}</span>
                    </div>
                </div>
            </ZyCard>
        </div>
    </div>
</template>
