<script setup lang="ts">
/**
 * Cameras Show — Detalhes da camera com preview, streaming e pack
 */

import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge, ZyConfirmDialog, ZyVideoPlayer } from '@design';
import { PencilIcon, TrashIcon, ClipboardDocumentIcon, CheckIcon, PlayIcon, StopIcon, ArrowPathIcon, SignalIcon, EyeIcon, FilmIcon } from '@heroicons/vue/24/outline';
import type { Camera, StreamUrls } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface StreamInfo {
    active: boolean;
    reconnecting?: boolean;
    stream: {
        stream_id: string;
        status: string;
        hls_url: string | null;
        webrtc_url: string | null;
        started_at: string | null;
        uptime_seconds: number;
        error_message?: string | null;
        hard_reset_count?: number;
        mediamtx?: {
            ready: boolean;
            readers: number;
            bytesReceived: number;
        };
    } | null;
}

interface RecentSegment {
    id: string;
    starts_at: string;
    ends_at: string | null;
    duration_seconds: number;
    file_size_bytes: number;
    status: string;
}

interface Props {
    camera: Camera;
    streamUrls?: StreamUrls;
    recentSegments?: RecentSegment[];
    segmentStats?: { total_count: number; total_size: number };
    canManage?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    streamUrls: () => ({
        rtsp_url: null,
        rtmp_url: null,
        hls_url: null,
        hls_shift_url: null,
        mp4_export_url: null,
    }),
    recentSegments: () => [],
    segmentStats: () => ({ total_count: 0, total_size: 0 }),
    canManage: false,
});

const showDeleteConfirm = ref(false);
const copiedField = ref<string | null>(null);
const streamInfo = ref<StreamInfo | null>(null);
const streamLoading = ref(false);
const recordingLoading = ref(false);
const cameraOnline = ref<boolean>(props.camera.is_online ?? false);
const isRecording = ref<boolean>(props.camera.is_recording ?? false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Cameras', href: '/cameras' },
    { label: props.camera.name },
];

const channelTypeLabels: Record<string, string> = {
    direct: 'Direta',
    analog: 'Analogica',
    ip: 'IP',
};

const streamTypeLabels: Record<string, string> = {
    main: 'Principal',
    sub: 'Secundario',
    mobile: 'Mobile',
};

const isStreamActive = computed(() => {
    return streamInfo.value?.active === true && streamInfo.value?.stream?.status === 'active';
});

const isStreamReconnecting = computed(() => {
    return streamInfo.value?.reconnecting === true
        || streamInfo.value?.stream?.status === 'reconnecting'
        || streamInfo.value?.stream?.status === 'starting';
});

const isStreamError = computed(() => {
    return streamInfo.value?.stream?.status === 'error';
});

const hasAnyStream = computed(() => {
    return streamInfo.value?.active === true
        || (streamInfo.value?.reconnecting === true && !isStreamError.value);
});

const isAutoRecovering = computed(() => {
    return streamInfo.value?.reconnecting === true && isStreamError.value;
});

const liveHlsUrl = computed(() => {
    // Only provide HLS URL when stream is actually active (ready in MediaMTX)
    if (!isStreamActive.value) return null;
    return streamInfo.value?.stream?.hls_url ?? null;
});

const streamUptime = computed(() => {
    const seconds = streamInfo.value?.stream?.uptime_seconds ?? 0;
    if (seconds === 0) return null;
    const totalSecs = Math.floor(seconds);
    const h = Math.floor(totalSecs / 3600);
    const m = Math.floor((totalSecs % 3600) / 60);
    const s = totalSecs % 60;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
});

function fetchStreamStatus() {
    fetch(`/cameras/${props.camera.id}/stream`, {
        headers: { 'Accept': 'application/json' },
    })
        .then(res => res.json())
        .then(data => {
            streamInfo.value = data;
            // Update camera online status from polling response
            if (data.camera_online !== undefined) {
                cameraOnline.value = data.camera_online;
            }
        })
        .catch(() => {});
}

function fetchRecordingStatus() {
    fetch(`/cameras/${props.camera.id}/recording`, {
        headers: { 'Accept': 'application/json' },
    })
        .then(res => res.json())
        .then(data => {
            isRecording.value = data.is_recording ?? false;
        })
        .catch(() => {});
}

function startStream() {
    streamLoading.value = true;
    router.post(`/cameras/${props.camera.id}/stream/start`, {}, {
        preserveScroll: true,
        onFinish: () => {
            streamLoading.value = false;
            // Start polling after a short delay to give the job time to process
            setTimeout(fetchStreamStatus, 2000);
        },
    });
}

function stopStream() {
    streamLoading.value = true;
    router.post(`/cameras/${props.camera.id}/stream/stop`, {}, {
        preserveScroll: true,
        onFinish: () => {
            streamLoading.value = false;
            setTimeout(fetchStreamStatus, 1000);
        },
    });
}

function forceRestart() {
    streamLoading.value = true;
    // Delete the error stream and start fresh
    fetch(`/cameras/${props.camera.id}/stream/stop`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
        },
    }).finally(() => {
        // Small delay then start a new stream
        setTimeout(() => {
            router.post(`/cameras/${props.camera.id}/stream/start`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    streamLoading.value = false;
                    setTimeout(fetchStreamStatus, 2000);
                },
            });
        }, 1000);
    });
}

function startRecording() {
    recordingLoading.value = true;
    router.post(`/cameras/${props.camera.id}/recording/start`, {}, {
        preserveScroll: true,
        onFinish: () => {
            recordingLoading.value = false;
            // Poll recording status since the Job is async
            pollRecordingStatusUntilChanged(true);
        },
    });
}

function stopRecording() {
    recordingLoading.value = true;
    router.post(`/cameras/${props.camera.id}/recording/stop`, {}, {
        preserveScroll: true,
        onFinish: () => {
            recordingLoading.value = false;
            // Poll recording status since the Job is async
            pollRecordingStatusUntilChanged(false);
        },
    });
}

/**
 * Poll recording status repeatedly until it matches the expected state,
 * or until we hit the max attempts (gives up after ~15s).
 */
function pollRecordingStatusUntilChanged(expectedState: boolean, attempt = 0) {
    const maxAttempts = 10;
    const delay = attempt < 3 ? 1000 : 2000; // faster at first, then slower

    if (attempt >= maxAttempts) {
        // Force a page refresh as last resort to sync state
        router.reload({ only: ['camera'], preserveScroll: true });
        return;
    }

    setTimeout(() => {
        fetch(`/cameras/${props.camera.id}/recording`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(res => res.json())
            .then(data => {
                isRecording.value = data.is_recording ?? false;
                if (isRecording.value !== expectedState) {
                    // Not yet — try again
                    pollRecordingStatusUntilChanged(expectedState, attempt + 1);
                }
            })
            .catch(() => {
                pollRecordingStatusUntilChanged(expectedState, attempt + 1);
            });
    }, delay);
}

function formatDuration(seconds: number): string {
    if (!seconds) return '—';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return [h, m, s].map((v) => String(v).padStart(2, '0')).join(':');
}

function formatFileSize(bytes: number): string {
    if (!bytes) return '—';
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit',
        hour: '2-digit', minute: '2-digit',
    });
}

const qualityLabels: Record<string, string> = {
    original: 'Original (passthrough)',
    high: 'Alta (1080p)',
    medium: 'Media (720p)',
    low: 'Baixa (480p)',
    custom: 'Personalizado',
};

function getRecordingQualityLabel(): string {
    const q = (props.camera as any).recording_quality;
    if (!q) return 'Padrao global';
    return qualityLabels[q] || q;
}

function getRecordingMethod(): string {
    const q = (props.camera as any).recording_quality;
    if (!q || q === 'original') return 'MediaMTX (passthrough)';
    return 'FFmpeg (transcodificacao)';
}

const segmentStatusLabels: Record<string, string> = {
    ready: 'Pronto',
    uploaded: 'Na Nuvem',
    uploading: 'Enviando',
    writing: 'Gravando',
};

const segmentStatusVariants: Record<string, string> = {
    ready: 'success',
    uploaded: 'primary',
    uploading: 'warning',
    writing: 'danger',
};

function deleteCamera() {
    router.delete(`/cameras/${props.camera.id}`, {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}

function copyToClipboard(text: string, field: string) {
    navigator.clipboard.writeText(text);
    copiedField.value = field;
    setTimeout(() => { copiedField.value = null; }, 2000);
}

const streamUrlRows = [
    { key: 'rtsp_url', label: 'RTSP URL' },
    { key: 'rtmp_url', label: 'RTMP URL' },
    { key: 'hls_url', label: 'HLS URL' },
    { key: 'hls_shift_url', label: 'HLS Shift (DVR)' },
    { key: 'mp4_export_url', label: 'MP4 Export' },
] as const;

onMounted(() => {
    fetchStreamStatus();
    fetchRecordingStatus();
    pollInterval = setInterval(() => {
        fetchStreamStatus();
        fetchRecordingStatus();
    }, 5000);
});

onBeforeUnmount(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
});
</script>

<template>
    <Head :title="camera.name" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="camera.name" description="Detalhes da camera">
        <template v-if="canManage" #actions>
            <ZyButton variant="secondary" :href="`/cameras/${camera.id}/edit`">
                <PencilIcon class="h-5 w-5" />
                Editar
            </ZyButton>
            <ZyButton variant="danger" @click="showDeleteConfirm = true">
                <TrashIcon class="h-5 w-5" />
                Excluir
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Video Preview + Status Sidebar -->
    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <ZyCard>
                <template #header>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                                {{ isStreamActive ? 'Stream ao Vivo' : isStreamReconnecting ? 'Reconectando...' : 'Preview' }}
                            </h3>
                            <ZyBadge v-if="isStreamReconnecting" variant="warning" dot>
                                Reconectando
                            </ZyBadge>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Recording controls -->
                            <ZyButton
                                v-if="isStreamActive && !isRecording"
                                variant="danger"
                                size="sm"
                                :disabled="recordingLoading"
                                @click="startRecording"
                            >
                                <span class="relative flex h-3 w-3 mr-1">
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                                </span>
                                Gravar
                            </ZyButton>
                            <ZyButton
                                v-if="isRecording"
                                variant="secondary"
                                size="sm"
                                :disabled="recordingLoading"
                                @click="stopRecording"
                            >
                                <StopIcon class="h-4 w-4" />
                                Parar Gravacao
                            </ZyButton>
                            <!-- Stream controls -->
                            <ZyButton
                                v-if="!hasAnyStream && !isAutoRecovering"
                                variant="primary"
                                size="sm"
                                :disabled="streamLoading || !camera.is_active"
                                @click="startStream"
                            >
                                <PlayIcon class="h-4 w-4" />
                                Iniciar Stream
                            </ZyButton>
                            <!-- Force restart when auto-recovering or error -->
                            <ZyButton
                                v-if="isAutoRecovering"
                                variant="warning"
                                size="sm"
                                :disabled="streamLoading"
                                @click="forceRestart"
                            >
                                <ArrowPathIcon class="h-4 w-4" />
                                Forcar Reinicio
                            </ZyButton>
                            <ZyButton
                                v-if="isStreamActive"
                                variant="danger"
                                size="sm"
                                :disabled="streamLoading"
                                @click="stopStream"
                            >
                                <StopIcon class="h-4 w-4" />
                                Parar Stream
                            </ZyButton>
                        </div>
                    </div>
                </template>

                <!-- Error/Auto-recovery overlay -->
                <div v-if="isAutoRecovering" class="relative">
                    <ZyVideoPlayer
                        :hls-url="null"
                        :snapshot-url="camera.snapshot_url"
                        :is-live="false"
                        :is-recording="false"
                        :autoplay="false"
                        :reconnect-on-error="false"
                    />
                    <div class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center gap-3 rounded-lg">
                        <div class="flex items-center gap-2 bg-danger-600/90 px-3 py-1.5 rounded-full">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                            </span>
                            <span class="text-white text-xs font-bold tracking-wider uppercase">Camera com problema</span>
                        </div>
                        <p class="text-white/90 text-sm font-medium">Reconexao automatica em andamento</p>
                        <p v-if="streamInfo?.stream?.error_message" class="text-yellow-300/80 text-xs max-w-sm text-center">
                            {{ streamInfo.stream.error_message }}
                        </p>
                        <p v-if="streamInfo?.stream?.hard_reset_count" class="text-white/50 text-xs">
                            Hard reset #{{ streamInfo.stream.hard_reset_count }}
                        </p>
                        <ZyButton variant="secondary" size="sm" class="mt-2" :disabled="streamLoading" @click="forceRestart">
                            <ArrowPathIcon class="h-4 w-4" />
                            Forcar Reinicio
                        </ZyButton>
                    </div>
                </div>

                <!-- Reconnecting overlay (starting/reconnecting but not error) -->
                <div v-else-if="isStreamReconnecting && !isStreamActive" class="relative">
                    <ZyVideoPlayer
                        :hls-url="null"
                        :snapshot-url="camera.snapshot_url"
                        :is-live="false"
                        :is-recording="false"
                        :autoplay="false"
                        :reconnect-on-error="false"
                    />
                    <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center gap-3 rounded-lg">
                        <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span class="text-white text-sm font-medium">Conectando a camera...</span>
                    </div>
                </div>

                <!-- Normal player (only when stream is active or no stream at all) -->
                <ZyVideoPlayer
                    v-else
                    :hls-url="liveHlsUrl"
                    :snapshot-url="camera.snapshot_url"
                    :is-live="isStreamActive"
                    :is-recording="isRecording"
                    :autoplay="isStreamActive"
                    :reconnect-on-error="isStreamActive"
                />
            </ZyCard>
        </div>

        <div class="space-y-6">
            <!-- Stream Info (when active, reconnecting or error) -->
            <ZyCard v-if="(hasAnyStream || isAutoRecovering) && streamInfo?.stream">
                <template #header>
                    <div class="flex items-center gap-2">
                        <SignalIcon :class="['h-5 w-5', isStreamActive ? 'text-green-500' : isStreamError ? 'text-red-500' : 'text-yellow-500']" />
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                            {{ isStreamActive ? 'Stream Ativo' : isAutoRecovering ? 'Stream em Recuperacao' : 'Stream' }}
                        </h3>
                    </div>
                </template>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Status</span>
                        <ZyBadge
                            :variant="streamInfo.stream.status === 'active' ? 'success' : streamInfo.stream.status === 'error' ? 'danger' : 'warning'"
                            dot
                        >
                            {{ streamInfo.stream.status === 'active' ? 'Ativo'
                                : streamInfo.stream.status === 'starting' ? 'Iniciando'
                                : streamInfo.stream.status === 'reconnecting' ? 'Reconectando'
                                : streamInfo.stream.status === 'error' ? 'Erro — Recuperando'
                                : streamInfo.stream.status }}
                        </ZyBadge>
                    </div>
                    <div v-if="streamInfo.stream.error_message" class="p-2 rounded-zy bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800">
                        <p class="text-body-sm text-danger-600 dark:text-danger-400">{{ streamInfo.stream.error_message }}</p>
                    </div>
                    <div v-if="streamInfo.stream.hard_reset_count" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Hard resets realizados</span>
                        <span class="text-body-sm font-mono text-warning-500">#{{ streamInfo.stream.hard_reset_count }}</span>
                    </div>
                    <div v-if="streamUptime" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Tempo ativo</span>
                        <span class="text-body-sm font-mono text-[var(--zy-text-primary)]">{{ streamUptime }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Viewers</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ streamInfo.stream.mediamtx?.readers ?? 0 }}</span>
                    </div>
                    <div v-if="streamInfo.stream.mediamtx?.bytesReceived" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Dados recebidos</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">
                            {{ (streamInfo.stream.mediamtx.bytesReceived / (1024 * 1024)).toFixed(1) }} MB
                        </span>
                    </div>
                </div>
            </ZyCard>

            <!-- Status -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Status</h3>
                </template>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Camera</span>
                        <ZyBadge :variant="camera.is_active ? 'success' : 'danger'" dot>
                            {{ camera.is_active ? 'Ativa' : 'Inativa' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Conexao</span>
                        <ZyBadge :variant="cameraOnline || isStreamActive ? 'success' : 'danger'" dot>
                            {{ cameraOnline || isStreamActive ? 'Online' : 'Offline' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Gravando</span>
                        <ZyBadge :variant="isRecording ? 'danger' : 'secondary'" dot>
                            {{ isRecording ? 'Gravando' : 'Parado' }}
                        </ZyBadge>
                    </div>
                    <hr class="border-dark-200 dark:border-dark-700" />
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Qualidade gravacao</span>
                        <ZyBadge :variant="(camera as any).recording_quality && (camera as any).recording_quality !== 'original' ? 'info' : 'secondary'">
                            {{ getRecordingQualityLabel() }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Metodo</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ getRecordingMethod() }}</span>
                    </div>
                </div>
            </ZyCard>

            <!-- Device -->
            <ZyCard v-if="camera.device">
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Dispositivo</h3>
                </template>
                <div class="space-y-3">
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Nome</p>
                        <a :href="`/devices/${camera.device.id}`" class="text-body text-[var(--zy-text-primary)] hover:underline">
                            {{ camera.device.name }}
                        </a>
                    </div>
                    <div v-if="camera.channel_number">
                        <p class="text-caption font-medium text-dark-500 mb-1">Canal</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono">CH {{ camera.channel_number }}</p>
                    </div>
                    <div v-if="camera.device.manufacturer">
                        <p class="text-caption font-medium text-dark-500 mb-1">Fabricante</p>
                        <p class="text-body text-[var(--zy-text-primary)] capitalize">{{ camera.device.manufacturer }}</p>
                    </div>
                </div>
            </ZyCard>

            <!-- Tenant -->
            <ZyCard v-if="camera.tenant">
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Empresa</h3>
                </template>
                <p class="text-body text-[var(--zy-text-primary)]">{{ camera.tenant.name }}</p>
            </ZyCard>
        </div>
    </div>

    <!-- Stream URLs -->
    <ZyCard class="mb-6">
        <template #header>
            <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Stream URLs</h3>
        </template>
        <div class="space-y-3">
            <div
                v-for="row in streamUrlRows"
                :key="row.key"
                class="flex items-start gap-3"
            >
                <span class="text-body-sm text-dark-500 whitespace-nowrap min-w-[120px] pt-1">{{ row.label }}</span>
                <div v-if="streamUrls[row.key]" class="flex-1 flex items-start gap-2 min-w-0">
                    <code class="text-body-sm font-mono text-[var(--zy-text-primary)] bg-dark-100 dark:bg-dark-800 px-2 py-1 rounded break-all flex-1">
                        {{ streamUrls[row.key] }}
                    </code>
                    <button
                        class="flex-shrink-0 p-1 text-dark-400 hover:text-primary-500 transition-colors"
                        :title="`Copiar ${row.label}`"
                        @click="copyToClipboard(streamUrls[row.key]!, row.key)"
                    >
                        <CheckIcon v-if="copiedField === row.key" class="h-4 w-4 text-success-500" />
                        <ClipboardDocumentIcon v-else class="h-4 w-4" />
                    </button>
                </div>
                <span v-else class="text-body-sm text-dark-400 italic pt-1">Nao disponivel</span>
            </div>
        </div>
    </ZyCard>

    <!-- Camera Info + Connection + Pack -->
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Camera Info -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Informacoes da Camera</h3>
                </template>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Nome</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ camera.name }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Tipo de canal</p>
                        <ZyBadge variant="primary">{{ channelTypeLabels[camera.channel_type] || camera.channel_type }}</ZyBadge>
                    </div>
                    <div v-if="camera.manufacturer">
                        <p class="text-caption font-medium text-dark-500 mb-1">Fabricante</p>
                        <p class="text-body text-[var(--zy-text-primary)] capitalize">{{ camera.manufacturer }}</p>
                    </div>
                    <div v-if="camera.model">
                        <p class="text-caption font-medium text-dark-500 mb-1">Modelo</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ camera.model }}</p>
                    </div>
                    <div v-if="camera.location">
                        <p class="text-caption font-medium text-dark-500 mb-1">Localizacao</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ camera.location }}</p>
                    </div>
                    <div v-if="camera.description">
                        <p class="text-caption font-medium text-dark-500 mb-1">Descricao</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ camera.description }}</p>
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
                        <p v-if="camera.address" class="text-body text-[var(--zy-text-primary)] font-mono">{{ camera.address }}:{{ camera.port }}</p>
                        <p v-else class="text-body text-dark-500">Via dispositivo</p>
                    </div>
                    <div v-if="camera.address">
                        <p class="text-caption font-medium text-dark-500 mb-1">Tipo de endereco</p>
                        <p class="text-body text-[var(--zy-text-primary)] uppercase">{{ camera.address_type }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Transporte</p>
                        <p class="text-body text-[var(--zy-text-primary)] uppercase">{{ camera.transport }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Tipo de stream</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ streamTypeLabels[camera.stream_type] || camera.stream_type }}</p>
                    </div>
                    <div v-if="camera.rtsp_path">
                        <p class="text-caption font-medium text-dark-500 mb-1">Path RTSP</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono text-sm break-all">{{ camera.rtsp_path }}</p>
                    </div>
                    <div v-if="camera.rtmp_path">
                        <p class="text-caption font-medium text-dark-500 mb-1">Path RTMP</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono text-sm break-all">{{ camera.rtmp_path }}</p>
                    </div>
                    <div v-if="camera.hls_path">
                        <p class="text-caption font-medium text-dark-500 mb-1">Path HLS</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono text-sm break-all">{{ camera.hls_path }}</p>
                    </div>
                    <div v-if="camera.codec">
                        <p class="text-caption font-medium text-dark-500 mb-1">Codec</p>
                        <p class="text-body text-[var(--zy-text-primary)] uppercase">{{ camera.codec }}</p>
                    </div>
                    <div v-if="camera.resolution">
                        <p class="text-caption font-medium text-dark-500 mb-1">Resolucao</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ camera.resolution }}</p>
                    </div>
                    <div v-if="camera.fps">
                        <p class="text-caption font-medium text-dark-500 mb-1">FPS</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ camera.fps }}</p>
                    </div>
                </div>
            </ZyCard>
        </div>

        <!-- Right sidebar: Pack + Storage -->
        <div class="space-y-6">
            <!-- Streaming / Recursos -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Streaming / Recursos</h3>
                </template>
                <div class="space-y-3">
                    <p class="text-caption font-medium text-dark-500 mb-1">Streaming (via MediaMTX)</p>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">HLS</span>
                        <ZyBadge :variant="isStreamActive ? 'success' : 'secondary'">
                            {{ isStreamActive ? 'Disponivel' : 'Requer stream' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">HLS fMP4</span>
                        <ZyBadge :variant="isStreamActive ? 'success' : 'secondary'">
                            {{ isStreamActive ? 'Disponivel' : 'Requer stream' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Low Latency HLS</span>
                        <ZyBadge :variant="isStreamActive ? 'success' : 'secondary'">
                            {{ isStreamActive ? 'Disponivel' : 'Requer stream' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">WebRTC</span>
                        <ZyBadge :variant="isStreamActive ? 'success' : 'secondary'">
                            {{ isStreamActive ? 'Disponivel' : 'Requer stream' }}
                        </ZyBadge>
                    </div>
                    <hr class="border-dark-200 dark:border-dark-700" />
                    <p class="text-caption font-medium text-dark-500 mb-1">Recursos da Camera</p>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">PTZ (Pan-Tilt-Zoom)</span>
                        <ZyBadge :variant="camera.has_ptz ? 'success' : 'secondary'">
                            {{ camera.has_ptz ? 'Sim' : 'Nao' }}
                        </ZyBadge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Audio</span>
                        <ZyBadge :variant="camera.has_audio ? 'success' : 'secondary'">
                            {{ camera.has_audio ? 'Sim' : 'Nao' }}
                        </ZyBadge>
                    </div>
                </div>
            </ZyCard>

            <!-- Storage -->
            <ZyCard v-if="camera.device">
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Armazenamento</h3>
                </template>
                <div v-if="camera.device.storage_used_gb !== null" class="space-y-2">
                    <p class="text-heading-md font-bold text-[var(--zy-text-primary)]">
                        {{ camera.device.storage_used_gb }} GB
                    </p>
                    <p v-if="camera.device.storage_updated_at" class="text-caption text-dark-500">
                        Atualizado em {{ new Date(camera.device.storage_updated_at).toLocaleDateString('pt-BR') }}
                    </p>
                </div>
                <div v-else class="text-center py-4">
                    <p class="text-body-sm text-dark-400 italic">Dados de armazenamento nao disponiveis</p>
                    <p class="text-caption text-dark-400 mt-1">Sera preenchido via integracao</p>
                </div>
            </ZyCard>
        </div>
    </div>

    <!-- Recording Segments -->
    <ZyCard class="mt-6">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <FilmIcon class="h-5 w-5 text-indigo-400" />
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                        Gravacoes
                        <span v-if="segmentStats.total_count > 0" class="text-xs text-dark-500 font-normal ml-1">
                            ({{ segmentStats.total_count }} segmentos &middot; {{ formatFileSize(segmentStats.total_size) }})
                        </span>
                    </h3>
                </div>
                <ZyButton variant="primary" size="sm" :href="`/cameras/${camera.id}/recordings`">
                    <EyeIcon class="h-4 w-4" />
                    Ver gravacoes
                </ZyButton>
            </div>
        </template>

        <div v-if="recentSegments.length > 0" class="divide-y divide-dark-200 dark:divide-dark-700">
            <div
                v-for="seg in recentSegments"
                :key="seg.id"
                class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <ZyBadge
                        :variant="(segmentStatusVariants[seg.status] || 'secondary') as any"
                        :dot="seg.status === 'writing'"
                    >
                        {{ segmentStatusLabels[seg.status] || seg.status }}
                    </ZyBadge>
                    <span class="text-body-sm text-[var(--zy-text-primary)]">
                        {{ formatDate(seg.starts_at) }}
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-body-sm text-dark-500 font-mono">{{ formatDuration(seg.duration_seconds) }}</span>
                    <span class="text-body-sm text-dark-500">{{ formatFileSize(seg.file_size_bytes) }}</span>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-6">
            <p class="text-body-sm text-dark-400">Nenhuma gravacao encontrada.</p>
            <p class="text-caption text-dark-500 mt-1">Inicie o stream para comecar a gravacao continua.</p>
        </div>
    </ZyCard>

    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Camera"
        :message="`Tem certeza que deseja excluir ${camera.name}?`"
        confirm-text="Excluir"
        @confirm="deleteCamera"
        @cancel="showDeleteConfirm = false"
    />
</template>
