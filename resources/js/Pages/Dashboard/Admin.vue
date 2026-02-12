<script setup lang="ts">
/**
 * Dashboard Admin — Painel de monitoramento do sistema (super-admin)
 */

import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyCard, ZyPageHeader, ZyBreadcrumb, ZyBadge, ZyVideoPlayer } from '@design';
import {
    VideoCameraIcon,
    ServerStackIcon,
    BuildingOfficeIcon,
    UsersIcon,
    SignalIcon,
    SignalSlashIcon,
    CpuChipIcon,
    CircleStackIcon,
    ClockIcon,
    CommandLineIcon,
    FilmIcon,
    QueueListIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface LiveCamera {
    id: string;
    name: string;
    slug: string;
    is_online: boolean;
    is_recording: boolean;
    snapshot_url: string | null;
    hls_path: string | null;
    location: string | null;
    tenant?: { id: string; name: string } | null;
    latest_stream?: {
        id: string;
        status: string;
        hls_url: string | null;
        error_message?: string | null;
    } | null;
}

interface LogEntry {
    id: number;
    description: string;
    event: string;
    subject_type: string;
    causer_name: string;
    created_at: string;
    created_at_diff: string;
}

interface TopTenant {
    id: string;
    name: string;
    is_active: boolean;
    cameras_count: number;
}

interface WorkersSummary {
    transcoder_count: number;
    transcoder_healthy: number;
    transcoding_cameras: number;
    transcoder_capacity_percent: number;
    queue_status: string;
    queue_processes: number;
    queue_jobs_pending: number;
    queue_failed: number;
    alerts_count: number;
    alerts_critical: number;
}

interface Props {
    stats: Record<string, number>;
    systemHealth: {
        disk_total_gb: number;
        disk_free_gb: number;
        disk_used_percent: number;
        memory_usage_mb: number;
        memory_peak_mb: number;
        cpu_load: number[];
        php_version: string;
        laravel_version: string;
        uptime: string;
    };
    recentLogs: LogEntry[];
    topTenants: TopTenant[];
    liveCameras: LiveCamera[];
    workersSummary?: WorkersSummary;
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({}),
    systemHealth: () => ({
        disk_total_gb: 0,
        disk_free_gb: 0,
        disk_used_percent: 0,
        memory_usage_mb: 0,
        memory_peak_mb: 0,
        cpu_load: [0, 0, 0],
        php_version: '',
        laravel_version: '',
        uptime: 'N/A',
    }),
    recentLogs: () => [],
    topTenants: () => [],
    liveCameras: () => [],
    workersSummary: () => ({
        transcoder_count: 0,
        transcoder_healthy: 0,
        transcoding_cameras: 0,
        transcoder_capacity_percent: 0,
        queue_status: 'unknown',
        queue_processes: 0,
        queue_jobs_pending: 0,
        queue_failed: 0,
        alerts_count: 0,
        alerts_critical: 0,
    }),
});

const ws = computed(() => props.workersSummary ?? {
    transcoder_count: 0,
    transcoder_healthy: 0,
    transcoding_cameras: 0,
    transcoder_capacity_percent: 0,
    queue_status: 'unknown',
    queue_processes: 0,
    queue_jobs_pending: 0,
    queue_failed: 0,
    alerts_count: 0,
    alerts_critical: 0,
});

function getWorkerHealthVariant(): 'success' | 'warning' | 'danger' {
    if (ws.value.alerts_critical > 0) return 'danger';
    if (ws.value.alerts_count > 0) return 'warning';
    return 'success';
}

function getWorkerHealthLabel(): string {
    if (ws.value.alerts_critical > 0) return 'Critico';
    if (ws.value.alerts_count > 0) return 'Atencao';
    return 'Saudavel';
}

function getCapacityColor(percent: number): string {
    if (percent >= 90) return 'bg-danger-500';
    if (percent >= 70) return 'bg-warning-500';
    return 'bg-secondary-500';
}

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Admin Dashboard' },
];

const s = computed(() => props.stats);

const statCards = computed(() => [
    {
        label: 'Cameras Online',
        value: s.value.cameras_online ?? 0,
        icon: SignalIcon,
        color: 'text-secondary-500',
        bg: 'bg-secondary-100 dark:bg-secondary-900/30',
    },
    {
        label: 'Cameras Offline',
        value: s.value.cameras_offline ?? 0,
        icon: SignalSlashIcon,
        color: 'text-danger-500',
        bg: 'bg-danger-100 dark:bg-danger-900/30',
    },
    {
        label: 'Total Cameras',
        value: s.value.total_cameras ?? 0,
        icon: VideoCameraIcon,
        color: 'text-primary-500',
        bg: 'bg-primary-100 dark:bg-primary-900/30',
    },
    {
        label: 'Dispositivos',
        value: s.value.total_devices ?? 0,
        icon: ServerStackIcon,
        color: 'text-accent-500',
        bg: 'bg-accent-100 dark:bg-accent-900/30',
    },
    {
        label: 'Empresas',
        value: s.value.total_tenants ?? 0,
        icon: BuildingOfficeIcon,
        color: 'text-warning-500',
        bg: 'bg-warning-100 dark:bg-warning-900/30',
    },
    {
        label: 'Usuarios',
        value: s.value.total_users ?? 0,
        icon: UsersIcon,
        color: 'text-primary-500',
        bg: 'bg-primary-100 dark:bg-primary-900/30',
    },
]);

const h = computed(() => props.systemHealth);

function getCpuColor(load: number): string {
    if (load >= 4) return 'text-danger-500';
    if (load >= 2) return 'text-warning-500';
    return 'text-secondary-500';
}

function getDiskColor(percent: number): string {
    if (percent >= 90) return 'bg-danger-500';
    if (percent >= 70) return 'bg-warning-500';
    return 'bg-secondary-500';
}

const maxCamerasInTopTenants = computed(() => {
    if (props.topTenants.length === 0) return 1;
    return Math.max(...props.topTenants.map(t => t.cameras_count), 1);
});

function eventBadgeVariant(event: string): 'success' | 'primary' | 'danger' | 'warning' | 'secondary' {
    switch (event) {
        case 'created': return 'success';
        case 'updated': return 'primary';
        case 'deleted': return 'danger';
        default: return 'secondary';
    }
}

function eventLabel(event: string): string {
    switch (event) {
        case 'created': return 'Criado';
        case 'updated': return 'Atualizado';
        case 'deleted': return 'Excluido';
        default: return event;
    }
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Admin Dashboard" description="Monitoramento geral do sistema">
        <template #actions>
            <ZyBadge variant="danger" class="text-body">Super Admin</ZyBadge>
        </template>
    </ZyPageHeader>

    <!-- Platform Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <ZyCard v-for="stat in statCards" :key="stat.label" padding="md">
            <div class="flex flex-col items-center text-center gap-2">
                <div :class="['h-10 w-10 rounded-zy-md flex items-center justify-center shrink-0', stat.bg]">
                    <component :is="stat.icon" :class="['h-5 w-5', stat.color]" />
                </div>
                <div>
                    <p class="text-heading-lg font-bold text-[var(--zy-text-primary)]">{{ stat.value }}</p>
                    <p class="text-caption text-[var(--zy-text-secondary)] font-medium">{{ stat.label }}</p>
                </div>
            </div>
        </ZyCard>
    </div>

    <!-- System Health -->
    <ZyCard class="mb-8">
        <template #header>
            <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Saude do Sistema</h3>
        </template>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- CPU -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <CpuChipIcon class="h-5 w-5 text-dark-500" />
                    <span class="text-body-sm font-medium text-dark-500">CPU Load</span>
                </div>
                <p :class="['text-heading-md font-bold', getCpuColor(h.cpu_load?.[0] ?? 0)]">
                    {{ (h.cpu_load?.[0] ?? 0).toFixed(2) }}
                </p>
                <p class="text-caption text-dark-400">
                    5m: {{ (h.cpu_load?.[1] ?? 0).toFixed(2) }} / 15m: {{ (h.cpu_load?.[2] ?? 0).toFixed(2) }}
                </p>
            </div>

            <!-- Memory -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <ServerStackIcon class="h-5 w-5 text-dark-500" />
                    <span class="text-body-sm font-medium text-dark-500">Memoria (PHP)</span>
                </div>
                <p class="text-heading-md font-bold text-[var(--zy-text-primary)]">
                    {{ h.memory_usage_mb }} MB
                </p>
                <p class="text-caption text-dark-400">Pico: {{ h.memory_peak_mb }} MB</p>
            </div>

            <!-- Disk -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <CircleStackIcon class="h-5 w-5 text-dark-500" />
                    <span class="text-body-sm font-medium text-dark-500">Disco</span>
                </div>
                <p class="text-heading-md font-bold text-[var(--zy-text-primary)]">
                    {{ h.disk_used_percent }}%
                </p>
                <div class="h-2 bg-dark-200 dark:bg-dark-700 rounded-full overflow-hidden">
                    <div
                        :class="['h-full rounded-full transition-all duration-500', getDiskColor(h.disk_used_percent)]"
                        :style="{ width: Math.min(100, h.disk_used_percent) + '%' }"
                    />
                </div>
                <p class="text-caption text-dark-400">
                    {{ h.disk_free_gb }} GB livre de {{ h.disk_total_gb }} GB
                </p>
            </div>

            <!-- Uptime -->
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <ClockIcon class="h-5 w-5 text-dark-500" />
                    <span class="text-body-sm font-medium text-dark-500">Uptime</span>
                </div>
                <p class="text-heading-md font-bold text-[var(--zy-text-primary)]">
                    {{ h.uptime }}
                </p>
                <p class="text-caption text-dark-400">
                    PHP {{ h.php_version }} / Laravel {{ h.laravel_version }}
                </p>
            </div>
        </div>
    </ZyCard>

    <!-- Workers do Sistema -->
    <ZyCard class="mb-8">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <CpuChipIcon class="h-5 w-5 text-indigo-400" />
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Workers do Sistema</h3>
                </div>
                <div class="flex items-center gap-2">
                    <ZyBadge :variant="getWorkerHealthVariant()" dot>
                        {{ getWorkerHealthLabel() }}
                    </ZyBadge>
                    <a href="/settings/workers" class="text-xs text-primary-500 hover:text-primary-400">
                        Ver detalhes →
                    </a>
                </div>
            </div>
        </template>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Transcoders -->
            <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4">
                <div class="flex items-center gap-2 mb-2">
                    <FilmIcon class="h-4 w-4 text-indigo-400" />
                    <span class="text-xs text-[var(--zy-text-muted)] font-medium">Transcoders</span>
                </div>
                <p class="text-xl font-bold text-white mb-1">
                    {{ ws.transcoder_healthy }}<span class="text-sm text-[var(--zy-text-muted)]">/{{ ws.transcoder_count }}</span>
                </p>
                <div v-if="ws.transcoder_count > 0" class="space-y-1">
                    <div class="h-1.5 bg-dark-700 rounded-full overflow-hidden">
                        <div
                            :class="['h-full rounded-full', getCapacityColor(ws.transcoder_capacity_percent)]"
                            :style="{ width: Math.min(100, ws.transcoder_capacity_percent) + '%' }"
                        />
                    </div>
                    <p class="text-[10px] text-[var(--zy-text-muted)]">
                        {{ ws.transcoding_cameras }} camera(s) &middot; {{ ws.transcoder_capacity_percent }}% cap.
                    </p>
                </div>
                <p v-else class="text-[10px] text-dark-600">Nenhum ativo</p>
            </div>

            <!-- Queue -->
            <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4">
                <div class="flex items-center gap-2 mb-2">
                    <QueueListIcon class="h-4 w-4 text-blue-400" />
                    <span class="text-xs text-[var(--zy-text-muted)] font-medium">Queue (Horizon)</span>
                </div>
                <p class="text-xl font-bold text-white mb-1">
                    {{ ws.queue_processes }} <span class="text-sm text-[var(--zy-text-muted)]">proc.</span>
                </p>
                <ZyBadge
                    :variant="ws.queue_status === 'running' ? 'success' : 'danger'"
                    dot
                    class="text-[10px]"
                >
                    {{ ws.queue_status === 'running' ? 'Ativo' : 'Inativo' }}
                </ZyBadge>
            </div>

            <!-- Pending Jobs -->
            <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4">
                <div class="flex items-center gap-2 mb-2">
                    <ServerStackIcon class="h-4 w-4 text-amber-400" />
                    <span class="text-xs text-[var(--zy-text-muted)] font-medium">Jobs Pendentes</span>
                </div>
                <p class="text-xl font-bold"
                    :class="ws.queue_jobs_pending > 50 ? 'text-danger-400' : ws.queue_jobs_pending > 10 ? 'text-warning-400' : 'text-white'"
                >
                    {{ ws.queue_jobs_pending }}
                </p>
                <p v-if="ws.queue_failed > 0" class="text-[10px] text-danger-400 mt-1">
                    {{ ws.queue_failed }} falhas recentes
                </p>
                <p v-else class="text-[10px] text-secondary-500 mt-1">Sem falhas</p>
            </div>

            <!-- Alerts -->
            <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4">
                <div class="flex items-center gap-2 mb-2">
                    <ExclamationTriangleIcon class="h-4 w-4"
                        :class="ws.alerts_critical > 0 ? 'text-danger-400' : ws.alerts_count > 0 ? 'text-warning-400' : 'text-secondary-400'"
                    />
                    <span class="text-xs text-[var(--zy-text-muted)] font-medium">Alertas</span>
                </div>
                <p class="text-xl font-bold"
                    :class="ws.alerts_critical > 0 ? 'text-danger-400' : ws.alerts_count > 0 ? 'text-warning-400' : 'text-white'"
                >
                    {{ ws.alerts_count }}
                </p>
                <p v-if="ws.alerts_critical > 0" class="text-[10px] text-danger-400 mt-1">
                    {{ ws.alerts_critical }} critico(s)
                </p>
                <p v-else class="text-[10px] text-secondary-500 mt-1">Tudo normal</p>
            </div>
        </div>
    </ZyCard>

    <!-- Main Content: Cameras + Sidebar -->
    <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <!-- Live Cameras (2/3) -->
        <div class="lg:col-span-2">
            <ZyCard>
                <template #header>
                    <div class="flex items-center justify-between">
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                            Cameras ao Vivo
                        </h3>
                        <span class="text-caption text-dark-500">
                            {{ liveCameras.length }} camera(s) ativa(s)
                        </span>
                    </div>
                </template>

                <div v-if="liveCameras.length > 0" class="grid grid-cols-2 lg:grid-cols-3 gap-3">
                    <a
                        v-for="cam in liveCameras"
                        :key="cam.id"
                        :href="`/cameras/${cam.id}`"
                        class="group relative block rounded-zy overflow-hidden"
                    >
                        <ZyVideoPlayer
                            :hls-url="cam.latest_stream?.status === 'active' ? cam.latest_stream?.hls_url : null"
                            :snapshot-url="cam.snapshot_url"
                            :is-live="cam.latest_stream?.status === 'active'"
                            :autoplay="cam.latest_stream?.status === 'active'"
                            :reconnect-on-error="true"
                        />
                        <!-- Stream error/reconnecting overlay -->
                        <div
                            v-if="cam.latest_stream && cam.latest_stream.status !== 'active'"
                            class="absolute inset-0 bg-dark-900/70 flex flex-col items-center justify-center gap-1.5 pointer-events-none"
                        >
                            <div
                                :class="[
                                    'flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold text-white uppercase tracking-wider',
                                    cam.latest_stream.status === 'error' ? 'bg-danger-600' : 'bg-yellow-600',
                                ]"
                            >
                                <span class="relative flex h-1.5 w-1.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-white"></span>
                                </span>
                                {{ cam.latest_stream.status === 'error' ? 'Erro' : cam.latest_stream.status === 'starting' ? 'Iniciando' : 'Reconectando' }}
                            </div>
                            <p v-if="cam.latest_stream.error_message" class="text-[9px] text-white/50 max-w-[90%] text-center truncate">
                                {{ cam.latest_stream.error_message }}
                            </p>
                            <span class="text-[10px] text-white/60">Clique para detalhes</span>
                        </div>
                        <!-- Bottom info bar -->
                        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-dark-900/80 to-transparent p-2">
                            <div class="flex items-center justify-between">
                                <p class="text-caption text-white font-medium truncate">{{ cam.name }}</p>
                                <ZyBadge
                                    :variant="cam.latest_stream?.status === 'active' ? 'success' : cam.latest_stream ? (cam.latest_stream.status === 'error' ? 'danger' : 'warning') : cam.is_online ? 'success' : 'secondary'"
                                    dot
                                    class="text-[10px]"
                                >
                                    {{ cam.latest_stream?.status === 'active' ? 'ON'
                                        : cam.latest_stream ? cam.latest_stream.status === 'error' ? 'ERRO' : 'RECON'
                                        : cam.is_online ? 'ON' : 'OFF' }}
                                </ZyBadge>
                            </div>
                            <p v-if="cam.tenant" class="text-[10px] text-dark-300 truncate">
                                {{ cam.tenant.name }}
                            </p>
                            <p v-if="cam.location" class="text-[10px] text-dark-400 truncate">
                                {{ cam.location }}
                            </p>
                        </div>
                        <div v-if="cam.is_recording" class="absolute top-2 right-2 flex items-center gap-1">
                            <span class="h-2 w-2 bg-danger-500 rounded-full animate-pulse" />
                            <span class="text-[10px] text-danger-400 font-medium">REC</span>
                        </div>
                    </a>
                </div>

                <div v-else class="text-center py-12">
                    <VideoCameraIcon class="h-12 w-12 text-dark-500 mx-auto mb-3" />
                    <p class="text-body text-dark-500">Nenhuma camera ativa no sistema</p>
                </div>
            </ZyCard>
        </div>

        <!-- Right Sidebar (1/3) -->
        <div class="space-y-6">
            <!-- Recent Activity -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Atividade Recente</h3>
                </template>
                <div v-if="recentLogs.length > 0" class="space-y-3 max-h-80 overflow-y-auto zy-scrollbar">
                    <div
                        v-for="log in recentLogs"
                        :key="log.id"
                        class="flex items-start gap-3 text-body-sm"
                    >
                        <ZyBadge :variant="eventBadgeVariant(log.event)" class="text-[10px] shrink-0 mt-0.5">
                            {{ eventLabel(log.event) }}
                        </ZyBadge>
                        <div class="min-w-0 flex-1">
                            <p class="text-[var(--zy-text-primary)] truncate">{{ log.description }}</p>
                            <p class="text-caption text-dark-400">
                                {{ log.causer_name }} &middot; {{ log.created_at_diff }}
                            </p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-6">
                    <p class="text-body-sm text-dark-500">Nenhuma atividade recente</p>
                </div>
                <template #footer>
                    <a href="/audit" class="text-body-sm text-primary-500 hover:text-primary-400">
                        Ver todas as atividades
                    </a>
                </template>
            </ZyCard>

            <!-- Top Tenants -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Top Empresas</h3>
                </template>
                <div v-if="topTenants.length > 0" class="space-y-3">
                    <div v-for="tenant in topTenants" :key="tenant.id" class="space-y-1">
                        <div class="flex items-center justify-between text-body-sm">
                            <a
                                :href="`/tenants`"
                                class="text-[var(--zy-text-primary)] font-medium truncate hover:underline"
                            >
                                {{ tenant.name }}
                            </a>
                            <span class="text-dark-500 shrink-0 ml-2">
                                {{ tenant.cameras_count }} cam
                            </span>
                        </div>
                        <div class="h-1.5 bg-dark-200 dark:bg-dark-700 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full bg-primary-500 transition-all duration-500"
                                :style="{ width: (tenant.cameras_count / maxCamerasInTopTenants * 100) + '%' }"
                            />
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-6">
                    <p class="text-body-sm text-dark-500">Nenhuma empresa cadastrada</p>
                </div>
            </ZyCard>

            <!-- System Info -->
            <ZyCard>
                <template #header>
                    <div class="flex items-center gap-2">
                        <CommandLineIcon class="h-5 w-5 text-dark-500" />
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Sistema</h3>
                    </div>
                </template>
                <div class="space-y-2 text-body-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-dark-500">PHP</span>
                        <span class="text-[var(--zy-text-primary)] font-mono">{{ h.php_version }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-dark-500">Laravel</span>
                        <span class="text-[var(--zy-text-primary)] font-mono">{{ h.laravel_version }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-dark-500">Planos</span>
                        <span class="text-[var(--zy-text-primary)]">{{ s.total_plans ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-dark-500">Tenants ativos</span>
                        <span class="text-[var(--zy-text-primary)]">{{ s.tenants_active ?? 0 }} / {{ s.total_tenants ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-dark-500">Devices online</span>
                        <span class="text-[var(--zy-text-primary)]">{{ s.devices_online ?? 0 }} / {{ s.total_devices ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-dark-500">Streams ativos</span>
                        <span class="text-[var(--zy-text-primary)] font-semibold">{{ s.active_streams ?? 0 }}</span>
                    </div>
                </div>
            </ZyCard>
        </div>
    </div>
</template>
