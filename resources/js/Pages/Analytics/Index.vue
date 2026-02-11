<script setup lang="ts">
/**
 * Analytics Dashboard — Analise completa do sistema de monitoramento.
 * Inspirado em VMS profissionais: Milestone XProtect, Genetec, Hikvision.
 */

import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge,
    ZySelect, ZyTabs, ZyTable, ZyProgressBar,
} from '@design';
import {
    ChartBarIcon, VideoCameraIcon, CircleStackIcon, ServerStackIcon,
    ShieldCheckIcon, ExclamationTriangleIcon, ClockIcon,
    SignalIcon, FilmIcon, ArrowTrendingUpIcon, BellAlertIcon,
    CheckCircleIcon, XCircleIcon, EyeIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface CameraStat {
    id: string;
    name: string;
    is_online: boolean;
    is_recording: boolean;
    continuous_recording: boolean;
    last_seen_at: string | null;
    segment_count: number;
    total_bytes: number;
    total_duration: number;
    recording_percent: number;
    stream_errors: number;
}

interface TrendRow {
    date: string;
    segments: number;
    bytes: number;
    duration_hours: number;
    gb: number;
}

interface StorageTrendRow {
    date: string;
    cumulative_gb: number;
    daily_gb: number;
}

interface EventRow {
    id: string;
    event_type: string;
    severity: string;
    description: string;
    camera_name: string | null;
    occurred_at: string;
    is_resolved: boolean;
}

interface ActivityRow {
    id: number;
    description: string;
    event: string;
    subject_type: string;
    causer_name: string;
    created_at: string;
}

interface Props {
    overview: {
        total_cameras: number;
        online_cameras: number;
        offline_cameras: number;
        recording_cameras: number;
        active_streams: number;
        availability_percent: number;
        total_segments: number;
        total_recording_bytes: number;
        total_recording_hours: number;
        backends_healthy: number;
        backends_total: number;
        unresolved_events: number;
    };
    cameraStats: CameraStat[];
    recordingTrends: TrendRow[];
    storageTrends: StorageTrendRow[];
    recentEvents: EventRow[];
    systemHealth: {
        backends: { name: string; driver: string; health_status: string; is_default: boolean }[];
        recent_segments_1h: number;
        total_streams: number;
        active_streams: number;
        error_streams: number;
    };
    activityTimeline: ActivityRow[];
    tenants?: { id: string; name: string }[];
    isSuperAdmin?: boolean;
    currentTenantId?: string;
    currentTenantName?: string;
    period: string;
}

const props = withDefaults(defineProps<Props>(), {
    tenants: () => [],
    isSuperAdmin: false,
});

const tenantFilter = ref(props.currentTenantId || '');
const periodFilter = ref(props.period);

const tenantOptions = [
    { value: '', label: 'Selecionar empresa' },
    ...(props.tenants || []).map((t) => ({ value: t.id, label: t.name })),
];

const periodOptions = [
    { value: '24h', label: 'Ultimas 24h' },
    { value: '7d', label: 'Ultimos 7 dias' },
    { value: '30d', label: 'Ultimos 30 dias' },
    { value: '90d', label: 'Ultimos 90 dias' },
];

function applyFilters() {
    const params: Record<string, string> = { period: periodFilter.value };
    if (tenantFilter.value) params.tenant_id = tenantFilter.value;
    router.get('/analytics', params, { preserveState: true, replace: true });
}

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Analises' },
];

const activeTab = ref('overview');

const tabs = [
    { key: 'overview', label: 'Visao Geral', icon: ChartBarIcon },
    { key: 'cameras', label: 'Cameras', icon: VideoCameraIcon, badge: props.cameraStats.length },
    { key: 'recordings', label: 'Gravacoes', icon: FilmIcon },
    { key: 'events', label: 'Eventos', icon: BellAlertIcon, badge: props.overview.unresolved_events || undefined },
    { key: 'system', label: 'Sistema', icon: ServerStackIcon },
];

// ─── Formatters ───────────────────────
function formatBytes(bytes: number): string {
    if (!bytes || bytes === 0) return '0 B';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function formatDuration(seconds: number): string {
    if (!seconds) return '--';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '--';
    return new Date(dateStr).toLocaleDateString('pt-BR', {
        day: '2-digit', month: '2-digit',
    });
}

function formatDateTime(dateStr: string | null): string {
    if (!dateStr) return '--';
    return new Date(dateStr).toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit',
        hour: '2-digit', minute: '2-digit',
    });
}

function timeAgo(dateStr: string | null): string {
    if (!dateStr) return '--';
    const diff = Date.now() - new Date(dateStr).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 1) return 'agora';
    if (m < 60) return `${m}m atras`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h atras`;
    const d = Math.floor(h / 24);
    return `${d}d atras`;
}

const eventTypeLabels: Record<string, string> = {
    camera_online: 'Camera Online',
    camera_offline: 'Camera Offline',
    recording_gap: 'Falha na Gravacao',
    stream_error: 'Erro de Stream',
    storage_warning: 'Alerta de Armazenamento',
    storage_critical: 'Armazenamento Critico',
    motion_detected: 'Movimento Detectado',
    system_error: 'Erro de Sistema',
};

const severityVariants: Record<string, string> = {
    info: 'success',
    warning: 'warning',
    critical: 'danger',
};

// ─── Chart Calculations ──────────────
const maxTrendSegments = computed(() =>
    Math.max(...props.recordingTrends.map(t => t.segments), 1),
);

const maxTrendGb = computed(() =>
    Math.max(...props.recordingTrends.map(t => t.gb), 0.01),
);

const maxStorageCumulative = computed(() =>
    Math.max(...props.storageTrends.map(t => t.cumulative_gb), 0.01),
);

// Camera table columns
const cameraColumns = [
    { key: 'name', label: 'Camera' },
    { key: 'status', label: 'Status' },
    { key: 'recording', label: 'Gravacao' },
    { key: 'segments', label: 'Segmentos' },
    { key: 'storage', label: 'Armazenamento' },
    { key: 'errors', label: 'Erros' },
];
</script>

<template>
    <Head title="Analises" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Analises"
        :description="isSuperAdmin ? currentTenantName || 'Todas as empresas' : 'Analise completa do sistema de monitoramento'"
    >
        <template #actions>
            <div class="flex items-center gap-3">
                <ZySelect
                    v-model="periodFilter"
                    :options="periodOptions"
                    class="w-44"
                    @update:model-value="applyFilters"
                />
                <ZySelect
                    v-if="isSuperAdmin"
                    v-model="tenantFilter"
                    :options="tenantOptions"
                    placeholder="Empresa"
                    class="w-52"
                    @update:model-value="applyFilters"
                />
            </div>
        </template>
    </ZyPageHeader>

    <div class="mt-6">
        <ZyTabs v-model="activeTab" :tabs="tabs">
            <!-- ═══ Overview Tab ═══ -->
            <template #overview>
                <div class="space-y-6">
                    <!-- KPI Cards -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg" :class="overview.availability_percent >= 90 ? 'bg-green-500/10' : 'bg-red-500/10'">
                                    <ShieldCheckIcon class="h-5 w-5" :class="overview.availability_percent >= 90 ? 'text-green-400' : 'text-red-400'" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Disponibilidade</p>
                                    <p class="text-xl font-bold text-white">{{ overview.availability_percent }}%</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-indigo-500/10">
                                    <VideoCameraIcon class="h-5 w-5 text-indigo-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Cameras Online</p>
                                    <p class="text-xl font-bold text-white">
                                        {{ overview.online_cameras }}
                                        <span class="text-sm text-dark-500 font-normal">/ {{ overview.total_cameras }}</span>
                                    </p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-blue-500/10">
                                    <FilmIcon class="h-5 w-5 text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Horas Gravadas</p>
                                    <p class="text-xl font-bold text-white">{{ overview.total_recording_hours }}h</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-amber-500/10">
                                    <CircleStackIcon class="h-5 w-5 text-amber-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Armazenamento</p>
                                    <p class="text-xl font-bold text-white">{{ formatBytes(overview.total_recording_bytes) }}</p>
                                </div>
                            </div>
                        </ZyCard>
                    </div>

                    <!-- Secondary KPIs -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Segmentos</p>
                                <p class="text-lg font-bold text-white">{{ overview.total_segments.toLocaleString('pt-BR') }}</p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Gravando</p>
                                <p class="text-lg font-bold text-green-400">{{ overview.recording_cameras }}</p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Streams Ativos</p>
                                <p class="text-lg font-bold text-blue-400">{{ overview.active_streams }}</p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Backends</p>
                                <p class="text-lg font-bold" :class="overview.backends_healthy === overview.backends_total ? 'text-green-400' : 'text-yellow-400'">
                                    {{ overview.backends_healthy }}/{{ overview.backends_total }}
                                </p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Alertas</p>
                                <p class="text-lg font-bold" :class="overview.unresolved_events > 0 ? 'text-red-400' : 'text-green-400'">
                                    {{ overview.unresolved_events }}
                                </p>
                            </div>
                        </ZyCard>
                    </div>

                    <!-- Recording Trends Chart (Bar Chart) -->
                    <ZyCard v-if="recordingTrends.length > 0">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <ArrowTrendingUpIcon class="h-5 w-5 text-blue-400" />
                                <h3 class="text-heading-sm font-semibold text-white">Tendencia de Gravacoes</h3>
                            </div>
                        </template>
                        <div class="space-y-3">
                            <!-- Segments per day -->
                            <div class="flex items-end gap-1 h-32">
                                <div
                                    v-for="t in recordingTrends"
                                    :key="t.date"
                                    class="flex-1 flex flex-col items-center group relative"
                                >
                                    <div
                                        class="w-full rounded-t bg-primary-500/70 hover:bg-primary-500 transition-colors cursor-pointer min-h-[2px]"
                                        :style="{ height: Math.max(2, (t.segments / maxTrendSegments) * 100) + '%' }"
                                    />
                                    <!-- Tooltip -->
                                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 hidden group-hover:block z-20 bg-dark-800 border border-dark-600 text-[10px] text-white px-2 py-1 rounded shadow-lg whitespace-nowrap">
                                        {{ formatDate(t.date) }}: {{ t.segments }} seg &middot; {{ t.gb }} GB
                                    </div>
                                </div>
                            </div>
                            <!-- Labels -->
                            <div class="flex gap-1">
                                <div
                                    v-for="(t, i) in recordingTrends"
                                    :key="'l-' + t.date"
                                    class="flex-1 text-center"
                                >
                                    <span v-if="i === 0 || i === recordingTrends.length - 1 || i % Math.max(1, Math.floor(recordingTrends.length / 7)) === 0" class="text-[9px] text-dark-500 font-mono">
                                        {{ formatDate(t.date) }}
                                    </span>
                                </div>
                            </div>
                            <!-- Summary -->
                            <div class="flex items-center gap-6 text-xs text-dark-400 pt-2 border-t border-dark-700">
                                <span>Total: <strong class="text-white">{{ overview.total_segments.toLocaleString('pt-BR') }}</strong> segmentos</span>
                                <span>Volume: <strong class="text-white">{{ formatBytes(overview.total_recording_bytes) }}</strong></span>
                                <span>Duracao: <strong class="text-white">{{ overview.total_recording_hours }}h</strong></span>
                            </div>
                        </div>
                    </ZyCard>

                    <!-- Storage Growth -->
                    <ZyCard v-if="storageTrends.length > 0">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <CircleStackIcon class="h-5 w-5 text-amber-400" />
                                <h3 class="text-heading-sm font-semibold text-white">Crescimento de Armazenamento</h3>
                            </div>
                        </template>
                        <div class="space-y-3">
                            <div class="flex items-end gap-1 h-24">
                                <div
                                    v-for="s in storageTrends"
                                    :key="s.date"
                                    class="flex-1 flex flex-col items-center group relative"
                                >
                                    <div
                                        class="w-full rounded-t bg-amber-500/60 hover:bg-amber-500 transition-colors cursor-pointer min-h-[2px]"
                                        :style="{ height: Math.max(2, (s.cumulative_gb / maxStorageCumulative) * 100) + '%' }"
                                    />
                                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 hidden group-hover:block z-20 bg-dark-800 border border-dark-600 text-[10px] text-white px-2 py-1 rounded shadow-lg whitespace-nowrap">
                                        {{ formatDate(s.date) }}: {{ s.cumulative_gb }} GB (+{{ s.daily_gb }} GB)
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <div v-for="(s, i) in storageTrends" :key="'sl-' + s.date" class="flex-1 text-center">
                                    <span v-if="i === 0 || i === storageTrends.length - 1 || i % Math.max(1, Math.floor(storageTrends.length / 7)) === 0" class="text-[9px] text-dark-500 font-mono">
                                        {{ formatDate(s.date) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </ZyCard>

                    <!-- Recent Events -->
                    <ZyCard v-if="recentEvents.length > 0">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <BellAlertIcon class="h-5 w-5 text-red-400" />
                                <h3 class="text-heading-sm font-semibold text-white">Eventos Recentes</h3>
                            </div>
                        </template>
                        <div class="space-y-2 max-h-64 overflow-y-auto zy-scrollbar">
                            <div
                                v-for="ev in recentEvents.slice(0, 10)"
                                :key="ev.id"
                                class="flex items-start gap-3 py-2 px-2 rounded bg-dark-800/30"
                            >
                                <div class="mt-0.5">
                                    <CheckCircleIcon v-if="ev.severity === 'info'" class="h-4 w-4 text-green-400" />
                                    <ExclamationTriangleIcon v-else-if="ev.severity === 'warning'" class="h-4 w-4 text-yellow-400" />
                                    <XCircleIcon v-else class="h-4 w-4 text-red-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-white">{{ ev.description }}</p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <ZyBadge :variant="(severityVariants[ev.severity] as any) || 'secondary'" class="text-[9px]">
                                            {{ eventTypeLabels[ev.event_type] || ev.event_type }}
                                        </ZyBadge>
                                        <span class="text-[10px] text-dark-500">{{ timeAgo(ev.occurred_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ZyCard>
                </div>
            </template>

            <!-- ═══ Cameras Tab ═══ -->
            <template #cameras>
                <div class="space-y-6">
                    <!-- Camera availability overview -->
                    <div class="grid grid-cols-3 gap-4">
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <div class="flex items-center justify-center gap-2 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full bg-green-500" />
                                    <p class="text-xs text-dark-500">Online</p>
                                </div>
                                <p class="text-2xl font-bold text-green-400">{{ overview.online_cameras }}</p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <div class="flex items-center justify-center gap-2 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full bg-red-500" />
                                    <p class="text-xs text-dark-500">Offline</p>
                                </div>
                                <p class="text-2xl font-bold text-red-400">{{ overview.offline_cameras }}</p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <div class="flex items-center justify-center gap-2 mb-1">
                                    <div class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse" />
                                    <p class="text-xs text-dark-500">Gravando</p>
                                </div>
                                <p class="text-2xl font-bold text-blue-400">{{ overview.recording_cameras }}</p>
                            </div>
                        </ZyCard>
                    </div>

                    <!-- Camera Table -->
                    <ZyCard>
                        <template #header>
                            <h3 class="text-heading-sm font-semibold text-white">Analise por Camera</h3>
                        </template>
                        <ZyTable :columns="cameraColumns" :data="cameraStats" hoverable>
                            <template #cell-name="{ row }">
                                <a :href="`/cameras/${row.id}`" class="text-primary-400 hover:text-primary-300 font-medium">
                                    {{ row.name }}
                                </a>
                            </template>
                            <template #cell-status="{ row }">
                                <div class="flex items-center gap-1.5">
                                    <div :class="['w-2 h-2 rounded-full', row.is_online ? 'bg-green-500' : 'bg-red-500']" />
                                    <span class="text-xs" :class="row.is_online ? 'text-green-400' : 'text-red-400'">
                                        {{ row.is_online ? 'Online' : 'Offline' }}
                                    </span>
                                </div>
                            </template>
                            <template #cell-recording="{ row }">
                                <div class="space-y-1">
                                    <ZyProgressBar
                                        :value="row.recording_percent"
                                        :max="100"
                                        size="sm"
                                        :variant="row.recording_percent >= 80 ? 'success' : row.recording_percent >= 50 ? 'warning' : 'danger'"
                                        class="w-20"
                                    />
                                    <span class="text-[10px] text-dark-500">{{ row.recording_percent }}%</span>
                                </div>
                            </template>
                            <template #cell-segments="{ row }">
                                <span class="text-xs font-mono text-white">{{ row.segment_count }}</span>
                            </template>
                            <template #cell-storage="{ row }">
                                <span class="text-xs text-dark-300">{{ formatBytes(row.total_bytes) }}</span>
                            </template>
                            <template #cell-errors="{ row }">
                                <ZyBadge v-if="row.stream_errors > 0" variant="danger">{{ row.stream_errors }}</ZyBadge>
                                <span v-else class="text-xs text-green-400">0</span>
                            </template>
                        </ZyTable>
                    </ZyCard>

                    <!-- Camera Recording Bars -->
                    <ZyCard v-if="cameraStats.length > 0">
                        <template #header>
                            <h3 class="text-heading-sm font-semibold text-white">Cobertura de Gravacao</h3>
                        </template>
                        <div class="space-y-3">
                            <div v-for="cam in cameraStats" :key="cam.id" class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-dark-300">{{ cam.name }}</span>
                                    <span class="text-xs font-mono" :class="cam.recording_percent >= 80 ? 'text-green-400' : cam.recording_percent >= 50 ? 'text-yellow-400' : 'text-red-400'">
                                        {{ cam.recording_percent }}%
                                    </span>
                                </div>
                                <ZyProgressBar
                                    :value="cam.recording_percent"
                                    :max="100"
                                    size="sm"
                                    :variant="cam.recording_percent >= 80 ? 'success' : cam.recording_percent >= 50 ? 'warning' : 'danger'"
                                />
                            </div>
                        </div>
                    </ZyCard>
                </div>
            </template>

            <!-- ═══ Recordings Tab ═══ -->
            <template #recordings>
                <div class="space-y-6">
                    <!-- Recording summary -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-indigo-500/10">
                                    <FilmIcon class="h-5 w-5 text-indigo-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Segmentos</p>
                                    <p class="text-lg font-bold text-white">{{ overview.total_segments.toLocaleString('pt-BR') }}</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-blue-500/10">
                                    <ClockIcon class="h-5 w-5 text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Horas Gravadas</p>
                                    <p class="text-lg font-bold text-white">{{ overview.total_recording_hours }}h</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-green-500/10">
                                    <CircleStackIcon class="h-5 w-5 text-green-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Volume Total</p>
                                    <p class="text-lg font-bold text-white">{{ formatBytes(overview.total_recording_bytes) }}</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-purple-500/10">
                                    <ArrowTrendingUpIcon class="h-5 w-5 text-purple-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Media/dia</p>
                                    <p class="text-lg font-bold text-white">
                                        {{ recordingTrends.length > 0 ? (overview.total_segments / recordingTrends.length).toFixed(0) : 0 }} seg
                                    </p>
                                </div>
                            </div>
                        </ZyCard>
                    </div>

                    <!-- Daily breakdown -->
                    <ZyCard v-if="recordingTrends.length > 0">
                        <template #header>
                            <h3 class="text-heading-sm font-semibold text-white">Gravacoes por Dia</h3>
                        </template>
                        <div class="space-y-2 max-h-96 overflow-y-auto zy-scrollbar">
                            <div
                                v-for="t in [...recordingTrends].reverse()"
                                :key="t.date"
                                class="flex items-center gap-4 py-2 px-2 rounded bg-dark-800/30"
                            >
                                <span class="text-xs text-dark-300 font-mono w-16">{{ formatDate(t.date) }}</span>
                                <div class="flex-1">
                                    <ZyProgressBar
                                        :value="t.segments"
                                        :max="maxTrendSegments"
                                        size="sm"
                                        variant="primary"
                                    />
                                </div>
                                <div class="flex items-center gap-4 text-xs text-dark-400 shrink-0">
                                    <span class="w-16 text-right font-mono">{{ t.segments }} seg</span>
                                    <span class="w-16 text-right">{{ t.duration_hours }}h</span>
                                    <span class="w-16 text-right">{{ t.gb }} GB</span>
                                </div>
                            </div>
                        </div>
                    </ZyCard>

                    <!-- Storage growth -->
                    <ZyCard v-if="storageTrends.length > 0">
                        <template #header>
                            <h3 class="text-heading-sm font-semibold text-white">Evolucao do Armazenamento</h3>
                        </template>
                        <div class="space-y-2 max-h-64 overflow-y-auto zy-scrollbar">
                            <div
                                v-for="s in [...storageTrends].reverse()"
                                :key="s.date"
                                class="flex items-center gap-4 py-1.5 px-2 rounded bg-dark-800/30"
                            >
                                <span class="text-xs text-dark-300 font-mono w-16">{{ formatDate(s.date) }}</span>
                                <div class="flex-1">
                                    <ZyProgressBar
                                        :value="s.cumulative_gb"
                                        :max="maxStorageCumulative"
                                        size="sm"
                                        variant="warning"
                                    />
                                </div>
                                <span class="text-xs text-dark-400 font-mono w-20 text-right">{{ s.cumulative_gb }} GB</span>
                                <span class="text-[10px] text-dark-500 w-16 text-right">+{{ s.daily_gb }} GB</span>
                            </div>
                        </div>
                    </ZyCard>
                </div>
            </template>

            <!-- ═══ Events Tab ═══ -->
            <template #events>
                <div class="space-y-6">
                    <!-- Events summary -->
                    <div class="grid grid-cols-3 gap-4">
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Total de Eventos</p>
                                <p class="text-2xl font-bold text-white">{{ recentEvents.length }}</p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Nao Resolvidos</p>
                                <p class="text-2xl font-bold text-red-400">
                                    {{ recentEvents.filter(e => !e.is_resolved).length }}
                                </p>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="text-center">
                                <p class="text-xs text-dark-500">Criticos</p>
                                <p class="text-2xl font-bold text-red-400">
                                    {{ recentEvents.filter(e => e.severity === 'critical').length }}
                                </p>
                            </div>
                        </ZyCard>
                    </div>

                    <!-- Events list -->
                    <ZyCard>
                        <template #header>
                            <h3 class="text-heading-sm font-semibold text-white">Todos os Eventos</h3>
                        </template>
                        <div v-if="recentEvents.length > 0" class="space-y-2 max-h-[500px] overflow-y-auto zy-scrollbar">
                            <div
                                v-for="ev in recentEvents"
                                :key="ev.id"
                                class="flex items-start gap-3 py-3 px-3 rounded-lg border"
                                :class="ev.is_resolved ? 'bg-dark-800/20 border-dark-700' : 'bg-dark-800/50 border-dark-600'"
                            >
                                <div class="mt-0.5 shrink-0">
                                    <CheckCircleIcon v-if="ev.severity === 'info'" class="h-5 w-5 text-green-400" />
                                    <ExclamationTriangleIcon v-else-if="ev.severity === 'warning'" class="h-5 w-5 text-yellow-400" />
                                    <XCircleIcon v-else class="h-5 w-5 text-red-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-white">{{ ev.description }}</p>
                                    <div class="flex items-center gap-3 mt-1">
                                        <ZyBadge :variant="(severityVariants[ev.severity] as any) || 'secondary'" class="text-[10px]">
                                            {{ eventTypeLabels[ev.event_type] || ev.event_type }}
                                        </ZyBadge>
                                        <span v-if="ev.camera_name" class="text-[10px] text-dark-500">{{ ev.camera_name }}</span>
                                        <span class="text-[10px] text-dark-500">{{ formatDateTime(ev.occurred_at) }}</span>
                                        <ZyBadge v-if="ev.is_resolved" variant="success" class="text-[10px]">Resolvido</ZyBadge>
                                        <ZyBadge v-else variant="danger" class="text-[10px]">Pendente</ZyBadge>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8">
                            <CheckCircleIcon class="h-10 w-10 text-green-500 mx-auto mb-3" />
                            <p class="text-body text-dark-400">Nenhum evento registrado.</p>
                            <p class="text-caption text-dark-500 mt-1">O sistema esta operando normalmente.</p>
                        </div>
                    </ZyCard>

                    <!-- Activity timeline -->
                    <ZyCard v-if="activityTimeline.length > 0">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <ClockIcon class="h-5 w-5 text-indigo-400" />
                                <h3 class="text-heading-sm font-semibold text-white">Atividade Recente</h3>
                            </div>
                        </template>
                        <div class="space-y-1 max-h-64 overflow-y-auto zy-scrollbar">
                            <div
                                v-for="act in activityTimeline"
                                :key="act.id"
                                class="flex items-center gap-3 py-1.5 px-2 rounded bg-dark-800/20"
                            >
                                <div class="w-1.5 h-1.5 rounded-full bg-dark-500 shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs text-dark-300">{{ act.description }}</span>
                                </div>
                                <span class="text-[10px] text-dark-500 shrink-0">{{ act.causer_name }}</span>
                                <span class="text-[10px] text-dark-500 shrink-0">{{ timeAgo(act.created_at) }}</span>
                            </div>
                        </div>
                    </ZyCard>
                </div>
            </template>

            <!-- ═══ System Tab ═══ -->
            <template #system>
                <div class="space-y-6">
                    <!-- System overview -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-green-500/10">
                                    <SignalIcon class="h-5 w-5 text-green-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Streams Ativos</p>
                                    <p class="text-lg font-bold text-white">
                                        {{ systemHealth.active_streams }}
                                        <span class="text-sm text-dark-500 font-normal">/ {{ systemHealth.total_streams }}</span>
                                    </p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg" :class="systemHealth.error_streams === 0 ? 'bg-green-500/10' : 'bg-red-500/10'">
                                    <ExclamationTriangleIcon class="h-5 w-5" :class="systemHealth.error_streams === 0 ? 'text-green-400' : 'text-red-400'" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Streams com Erro</p>
                                    <p class="text-lg font-bold" :class="systemHealth.error_streams === 0 ? 'text-green-400' : 'text-red-400'">
                                        {{ systemHealth.error_streams }}
                                    </p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-blue-500/10">
                                    <FilmIcon class="h-5 w-5 text-blue-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Seg. ultima hora</p>
                                    <p class="text-lg font-bold text-white">{{ systemHealth.recent_segments_1h }}</p>
                                </div>
                            </div>
                        </ZyCard>
                        <ZyCard padding="sm">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-amber-500/10">
                                    <ServerStackIcon class="h-5 w-5 text-amber-400" />
                                </div>
                                <div>
                                    <p class="text-xs text-dark-500">Backends</p>
                                    <p class="text-lg font-bold text-white">{{ systemHealth.backends.length }}</p>
                                </div>
                            </div>
                        </ZyCard>
                    </div>

                    <!-- Backends health -->
                    <ZyCard>
                        <template #header>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <ServerStackIcon class="h-5 w-5 text-blue-400" />
                                    <h3 class="text-heading-sm font-semibold text-white">Saude dos Backends</h3>
                                </div>
                                <ZyButton variant="secondary" size="sm" href="/settings/storage">
                                    Configurar
                                </ZyButton>
                            </div>
                        </template>
                        <div v-if="systemHealth.backends.length > 0" class="space-y-3">
                            <div
                                v-for="b in systemHealth.backends"
                                :key="b.name"
                                class="flex items-center justify-between p-3 rounded-lg bg-dark-800/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="['w-3 h-3 rounded-full', b.health_status === 'healthy' ? 'bg-green-500' : b.health_status === 'degraded' ? 'bg-yellow-500' : 'bg-red-500']" />
                                    <div>
                                        <p class="text-body font-medium text-white">{{ b.name }}</p>
                                        <p class="text-xs text-dark-500">{{ b.driver }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <ZyBadge v-if="b.is_default" variant="primary">Padrao</ZyBadge>
                                    <ZyBadge
                                        :variant="b.health_status === 'healthy' ? 'success' : b.health_status === 'degraded' ? 'warning' : 'danger'"
                                    >
                                        {{ b.health_status === 'healthy' ? 'Saudavel' : b.health_status === 'degraded' ? 'Degradado' : 'Com Problemas' }}
                                    </ZyBadge>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-6">
                            <p class="text-sm text-dark-400">Nenhum backend configurado.</p>
                        </div>
                    </ZyCard>

                    <!-- System info card -->
                    <ZyCard>
                        <template #header>
                            <h3 class="text-heading-sm font-semibold text-white">Informacoes do Sistema</h3>
                        </template>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div class="flex justify-between py-1.5 border-b border-dark-700">
                                    <span class="text-xs text-dark-400">Cameras configuradas</span>
                                    <span class="text-xs text-white font-mono">{{ overview.total_cameras }}</span>
                                </div>
                                <div class="flex justify-between py-1.5 border-b border-dark-700">
                                    <span class="text-xs text-dark-400">Cameras online</span>
                                    <span class="text-xs text-green-400 font-mono">{{ overview.online_cameras }}</span>
                                </div>
                                <div class="flex justify-between py-1.5 border-b border-dark-700">
                                    <span class="text-xs text-dark-400">Cameras gravando</span>
                                    <span class="text-xs text-blue-400 font-mono">{{ overview.recording_cameras }}</span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between py-1.5 border-b border-dark-700">
                                    <span class="text-xs text-dark-400">Backends ativos</span>
                                    <span class="text-xs text-white font-mono">{{ overview.backends_total }}</span>
                                </div>
                                <div class="flex justify-between py-1.5 border-b border-dark-700">
                                    <span class="text-xs text-dark-400">Backends saudaveis</span>
                                    <span class="text-xs text-green-400 font-mono">{{ overview.backends_healthy }}</span>
                                </div>
                                <div class="flex justify-between py-1.5 border-b border-dark-700">
                                    <span class="text-xs text-dark-400">Alertas pendentes</span>
                                    <span class="text-xs font-mono" :class="overview.unresolved_events > 0 ? 'text-red-400' : 'text-green-400'">
                                        {{ overview.unresolved_events }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </ZyCard>
                </div>
            </template>
        </ZyTabs>
    </div>
</template>
