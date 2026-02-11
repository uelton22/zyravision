<script setup lang="ts">
/**
 * Recordings Show — Playback profissional com timeline multi-segmento.
 * Reproduz sequencialmente multiplos segmentos MP4 na timeline.
 */

import { ref, computed, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge, ZyConfirmDialog } from '@design';
import {
    TrashIcon,
    ArrowDownTrayIcon,
    ArrowLeftIcon,
    ScissorsIcon,
    PlayIcon,
    PauseIcon,
    SpeakerWaveIcon,
    SpeakerXMarkIcon,
    ArrowsPointingOutIcon,
    ForwardIcon,
    BackwardIcon,
} from '@heroicons/vue/24/outline';
import type { Recording } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface LegacySegment {
    filename: string;
    size_bytes: number;
    size_human: string;
}

interface TimelineSegment {
    id: string;
    starts_at: string;
    ends_at: string | null;
    duration_seconds: number;
    file_size_bytes: number;
    status: string;
    stream_url: string;
}

interface Props {
    recording: Recording & {
        camera?: { id: string; name: string; slug: string } | null;
        tenant?: { id: string; name: string } | null;
        stream?: { id: string; mediamtx_path: string } | null;
    };
    videoUrl: string | null;
    segments: LegacySegment[];
    timelineSegments: TimelineSegment[];
    downloadUrl: string;
}

const props = defineProps<Props>();
const showDeleteConfirm = ref(false);
const showClipModal = ref(false);
const isClipping = ref(false);

// ─── Segment-based playback ──────────
const hasTimelineSegments = computed(() => props.timelineSegments.length > 0);
const currentSegmentIndex = ref(0);
const currentSegment = computed(() =>
    hasTimelineSegments.value ? props.timelineSegments[currentSegmentIndex.value] : null
);

// Total timeline duration (sum of all segments)
const totalTimelineDuration = computed(() => {
    if (!hasTimelineSegments.value) return 0;
    return props.timelineSegments.reduce((sum, s) => sum + (s.duration_seconds || 60), 0);
});

// Cumulative start offsets for each segment in the total timeline
const segmentOffsets = computed(() => {
    const offsets: number[] = [];
    let offset = 0;
    for (const seg of props.timelineSegments) {
        offsets.push(offset);
        offset += (seg.duration_seconds || 60);
    }
    return offsets;
});

// The URL to play (either segment or legacy)
const activeVideoUrl = computed(() => {
    if (hasTimelineSegments.value && currentSegment.value) {
        return currentSegment.value.stream_url;
    }
    return props.videoUrl;
});

// ─── Video Player ─────────────────────
const videoRef = ref<HTMLVideoElement | null>(null);
const isPlaying = ref(false);
const isMuted = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const buffered = ref(0);
const volume = ref(1);
const playbackRate = ref(1);
const isVideoReady = ref(false);
const videoError = ref(false);
const shouldAutoPlay = ref(false); // Flag para auto-play ao trocar segmento
const pendingSeekTime = ref<number | null>(null); // Seek pendente apos load

// Global timeline position (across all segments)
const globalCurrentTime = computed(() => {
    if (!hasTimelineSegments.value) return currentTime.value;
    return (segmentOffsets.value[currentSegmentIndex.value] || 0) + currentTime.value;
});

const globalDuration = computed(() => {
    if (!hasTimelineSegments.value) return duration.value;
    return totalTimelineDuration.value;
});

// ─── Timeline / Clip ──────────────────
const timelineRef = ref<HTMLDivElement | null>(null);
const isDragging = ref(false);
const clipStart = ref<number | null>(null);
const clipEnd = ref<number | null>(null);
const isSelectingClip = ref(false);
const clipDragType = ref<'start' | 'end' | null>(null);

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Gravacoes', href: '/recordings' },
    { label: props.recording.camera?.name || 'Gravacao' },
];

const statusLabels: Record<string, string> = {
    recording: 'Gravando',
    completed: 'Completa',
    failed: 'Falhou',
    deleted: 'Removida',
};

const statusBadgeVariants: Record<string, string> = {
    recording: 'danger',
    completed: 'success',
    failed: 'danger',
    deleted: 'secondary',
};

// ─── Formatters ───────────────────────
function formatTime(seconds: number): string {
    if (!seconds || isNaN(seconds)) return '00:00:00';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    return [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
}

function formatTimeShort(seconds: number): string {
    if (!seconds || isNaN(seconds)) return '00:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function formatFileSize(bytes: number): string {
    if (!bytes) return '--';
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
}

function formatDateShort(dateStr: string | null): string {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR', {
        hour: '2-digit', minute: '2-digit',
    });
}

// ─── Video Controls ───────────────────
function togglePlay() {
    if (!videoRef.value) return;
    if (isPlaying.value) {
        videoRef.value.pause();
    } else {
        videoRef.value.play();
    }
}

function toggleMute() {
    if (!videoRef.value) return;
    isMuted.value = !isMuted.value;
    videoRef.value.muted = isMuted.value;
}

function setVolume(v: number) {
    if (!videoRef.value) return;
    volume.value = v;
    videoRef.value.volume = v;
    isMuted.value = v === 0;
}

function setPlaybackRate(rate: number) {
    if (!videoRef.value) return;
    playbackRate.value = rate;
    videoRef.value.playbackRate = rate;
}

function seekRelative(delta: number) {
    if (!hasTimelineSegments.value) {
        if (!videoRef.value) return;
        const newTime = Math.max(0, Math.min(duration.value, videoRef.value.currentTime + delta));
        videoRef.value.currentTime = newTime;
        return;
    }

    // Global seek across segments
    const target = Math.max(0, Math.min(globalDuration.value, globalCurrentTime.value + delta));
    seekToGlobalTime(target);
}

function seekToGlobalTime(globalTime: number) {
    if (!hasTimelineSegments.value) {
        if (videoRef.value) videoRef.value.currentTime = Math.max(0, Math.min(duration.value, globalTime));
        return;
    }

    // Find which segment contains this time
    for (let i = 0; i < props.timelineSegments.length; i++) {
        const offset = segmentOffsets.value[i];
        const segDuration = props.timelineSegments[i].duration_seconds || 60;

        if (globalTime >= offset && globalTime < offset + segDuration) {
            const localTime = globalTime - offset;
            if (i !== currentSegmentIndex.value) {
                // Mudar de segmento: agendar seek e auto-play apos load
                pendingSeekTime.value = localTime;
                shouldAutoPlay.value = true;
                currentSegmentIndex.value = i;
                // O watch(activeVideoUrl) chamara .load() e onLoadedMetadata fara seek + play
            } else {
                // Mesmo segmento: seek direto
                if (videoRef.value) videoRef.value.currentTime = localTime;
            }
            return;
        }
    }

    // Past the end — go to last segment end
    if (props.timelineSegments.length > 0) {
        currentSegmentIndex.value = props.timelineSegments.length - 1;
    }
}

function toggleFullscreen() {
    const container = videoRef.value?.closest('.video-container');
    if (!container) return;
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else {
        (container as HTMLElement).requestFullscreen?.();
    }
}

// ─── Video Events ─────────────────────
function onLoadedMetadata() {
    if (!videoRef.value) return;
    duration.value = videoRef.value.duration;
    isVideoReady.value = true;
    videoError.value = false;

    // Aplicar seek pendente (quando seekToGlobalTime muda de segmento)
    if (pendingSeekTime.value !== null) {
        videoRef.value.currentTime = pendingSeekTime.value;
        pendingSeekTime.value = null;
    }

    // Auto-play quando trocou de segmento automaticamente
    if (shouldAutoPlay.value) {
        shouldAutoPlay.value = false;
        videoRef.value.play().catch(() => {});
    }
}

function onTimeUpdate() {
    if (!videoRef.value || isDragging.value) return;
    currentTime.value = videoRef.value.currentTime;
}

function onProgress() {
    if (!videoRef.value) return;
    if (videoRef.value.buffered.length > 0) {
        buffered.value = videoRef.value.buffered.end(videoRef.value.buffered.length - 1);
    }
}

function onPlay() { isPlaying.value = true; }
function onPause() { isPlaying.value = false; }

function onEnded() {
    isPlaying.value = false;

    // Auto-advance to next segment
    if (hasTimelineSegments.value && currentSegmentIndex.value < props.timelineSegments.length - 1) {
        shouldAutoPlay.value = true;
        currentSegmentIndex.value++;
        // O watch(activeVideoUrl) chamara .load() e onLoadedMetadata fara o .play()
    }
}

function onError() { videoError.value = true; isVideoReady.value = false; }

// Watch for segment changes — reload video
watch(activeVideoUrl, (newUrl, oldUrl) => {
    if (videoRef.value && newUrl && newUrl !== oldUrl) {
        videoError.value = false;
        isVideoReady.value = false;
        currentTime.value = 0;
        buffered.value = 0;
        videoRef.value.load();
        // onLoadedMetadata sera chamado quando o novo video estiver pronto
        // e ira aplicar pendingSeekTime + shouldAutoPlay se necessario
    }
});

// ─── Timeline ─────────────────────────
const progressPercent = computed(() => {
    if (!globalDuration.value) return 0;
    return (globalCurrentTime.value / globalDuration.value) * 100;
});

const bufferedPercent = computed(() => {
    if (!globalDuration.value) return 0;
    if (!hasTimelineSegments.value) return (buffered.value / (duration.value || 1)) * 100;
    // Buffer only for current segment
    const offset = segmentOffsets.value[currentSegmentIndex.value] || 0;
    return ((offset + buffered.value) / globalDuration.value) * 100;
});

const clipStartPercent = computed(() => {
    if (clipStart.value === null || !globalDuration.value) return null;
    return (clipStart.value / globalDuration.value) * 100;
});

const clipEndPercent = computed(() => {
    if (clipEnd.value === null || !globalDuration.value) return null;
    return (clipEnd.value / globalDuration.value) * 100;
});

const clipDuration = computed(() => {
    if (clipStart.value === null || clipEnd.value === null) return 0;
    return clipEnd.value - clipStart.value;
});

// Timeline segment bars (visual indicator of each segment in timeline)
const segmentBars = computed(() => {
    if (!hasTimelineSegments.value || !globalDuration.value) return [];
    return props.timelineSegments.map((seg, i) => ({
        left: (segmentOffsets.value[i] / globalDuration.value) * 100,
        width: ((seg.duration_seconds || 60) / globalDuration.value) * 100,
        active: i === currentSegmentIndex.value,
        label: formatDateShort(seg.starts_at),
    }));
});

// Timeline time markers
const timeMarkers = computed(() => {
    const total = globalDuration.value;
    if (!total) return [];
    const markers: { time: number; label: string; percent: number }[] = [];
    let interval = 60;
    if (total <= 120) interval = 10;
    else if (total <= 600) interval = 30;
    else if (total <= 3600) interval = 300;
    else interval = 600;

    for (let t = 0; t <= total; t += interval) {
        markers.push({
            time: t,
            label: formatTimeShort(t),
            percent: (t / total) * 100,
        });
    }
    return markers;
});

function getTimeFromMouseEvent(e: MouseEvent): number {
    if (!timelineRef.value || !globalDuration.value) return 0;
    const rect = timelineRef.value.getBoundingClientRect();
    const x = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
    return (x / rect.width) * globalDuration.value;
}

function onTimelineMouseDown(e: MouseEvent) {
    if (isSelectingClip.value) {
        clipStart.value = getTimeFromMouseEvent(e);
        clipEnd.value = clipStart.value;
        clipDragType.value = 'end';
        isDragging.value = true;
    } else {
        isDragging.value = true;
        seekToGlobalTime(getTimeFromMouseEvent(e));
    }
    document.addEventListener('mousemove', onTimelineMouseMove);
    document.addEventListener('mouseup', onTimelineMouseUp);
}

function onTimelineMouseMove(e: MouseEvent) {
    if (!isDragging.value) return;
    const time = getTimeFromMouseEvent(e);

    if (isSelectingClip.value && clipDragType.value) {
        if (clipDragType.value === 'end') {
            clipEnd.value = Math.max(clipStart.value ?? 0, time);
        } else if (clipDragType.value === 'start') {
            clipStart.value = Math.min(clipEnd.value ?? globalDuration.value, time);
        }
    } else {
        seekToGlobalTime(time);
    }
}

function onTimelineMouseUp() {
    isDragging.value = false;
    clipDragType.value = null;
    document.removeEventListener('mousemove', onTimelineMouseMove);
    document.removeEventListener('mouseup', onTimelineMouseUp);

    if (isSelectingClip.value && clipStart.value !== null && clipEnd.value !== null) {
        if (clipStart.value > clipEnd.value) {
            const tmp = clipStart.value;
            clipStart.value = clipEnd.value;
            clipEnd.value = tmp;
        }
        if (clipEnd.value - clipStart.value < 1) {
            clipStart.value = null;
            clipEnd.value = null;
        }
    }
}

function onClipHandleMouseDown(type: 'start' | 'end', e: MouseEvent) {
    e.stopPropagation();
    isDragging.value = true;
    clipDragType.value = type;
    document.addEventListener('mousemove', onTimelineMouseMove);
    document.addEventListener('mouseup', onTimelineMouseUp);
}

// ─── Clip Actions ─────────────────────
function startClipMode() {
    isSelectingClip.value = true;
    clipStart.value = null;
    clipEnd.value = null;
}

function cancelClipMode() {
    isSelectingClip.value = false;
    clipStart.value = null;
    clipEnd.value = null;
}

function setClipFromCurrent() {
    if (clipStart.value === null) {
        clipStart.value = globalCurrentTime.value;
    } else if (clipEnd.value === null || clipEnd.value <= clipStart.value) {
        clipEnd.value = Math.max(clipStart.value + 1, globalCurrentTime.value);
    }
}

async function downloadClip() {
    if (clipStart.value === null || clipEnd.value === null) return;
    isClipping.value = true;

    try {
        const response = await fetch(`/recordings/${props.recording.id}/clip`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/octet-stream',
            },
            body: JSON.stringify({
                start: clipStart.value,
                end: clipEnd.value,
            }),
        });

        if (!response.ok) {
            const err = await response.json().catch(() => null);
            alert(err?.error || 'Erro ao gerar recorte.');
            return;
        }

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `recorte_${props.recording.camera?.name ?? 'camera'}_${Math.round(clipStart.value)}-${Math.round(clipEnd.value)}s.mp4`;
        a.click();
        URL.revokeObjectURL(url);

        cancelClipMode();
    } catch (err) {
        alert('Erro ao gerar recorte. Tente novamente.');
    } finally {
        isClipping.value = false;
    }
}

function previewClip() {
    if (clipStart.value !== null) {
        seekToGlobalTime(clipStart.value);
        videoRef.value?.play();
    }
}

// ─── Delete ───────────────────────────
function deleteRecording() {
    router.delete(`/recordings/${props.recording.id}`, {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}

// ─── Keyboard shortcuts ───────────────
function onKeyDown(e: KeyboardEvent) {
    if (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement) return;

    switch (e.key) {
        case ' ':
        case 'k':
            e.preventDefault();
            togglePlay();
            break;
        case 'ArrowLeft':
            e.preventDefault();
            seekRelative(-10);
            break;
        case 'ArrowRight':
            e.preventDefault();
            seekRelative(10);
            break;
        case 'j':
            seekRelative(-10);
            break;
        case 'l':
            seekRelative(10);
            break;
        case 'f':
            toggleFullscreen();
            break;
        case 'm':
            toggleMute();
            break;
        case 'i':
            if (isSelectingClip.value) setClipFromCurrent();
            break;
        case 'Escape':
            if (isSelectingClip.value) cancelClipMode();
            break;
    }
}

// ─── Hover time on timeline ───────────
const hoverTime = ref<number | null>(null);
const hoverX = ref(0);

function onTimelineMouseMoveHover(e: MouseEvent) {
    if (isDragging.value) return;
    const time = getTimeFromMouseEvent(e);
    hoverTime.value = time;
    if (timelineRef.value) {
        const rect = timelineRef.value.getBoundingClientRect();
        hoverX.value = e.clientX - rect.left;
    }
}

function onTimelineMouseLeave() {
    hoverTime.value = null;
}

// Absolute time display
function absoluteTimeAt(globalSeconds: number): string {
    if (!hasTimelineSegments.value || !props.timelineSegments[0]?.starts_at) {
        if (!props.recording.started_at) return formatTime(globalSeconds);
        const d = new Date(new Date(props.recording.started_at).getTime() + globalSeconds * 1000);
        return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    const baseTime = new Date(props.timelineSegments[0].starts_at);
    const d = new Date(baseTime.getTime() + globalSeconds * 1000);
    return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

// ─── Lifecycle ────────────────────────
onMounted(() => {
    document.addEventListener('keydown', onKeyDown);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeyDown);
    document.removeEventListener('mousemove', onTimelineMouseMove);
    document.removeEventListener('mouseup', onTimelineMouseUp);
});

// Speed options
const speeds = [0.25, 0.5, 1, 1.5, 2];
</script>

<template>
    <Head :title="`Gravacao - ${recording.camera?.name || 'Gravacao'}`" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        :title="`Gravacao de ${recording.camera?.name || 'Camera'}`"
        :description="formatDate(recording.started_at)"
    >
        <template #actions>
            <ZyButton variant="secondary" size="sm" href="/recordings">
                <ArrowLeftIcon class="h-4 w-4" />
                Voltar
            </ZyButton>
            <ZyButton
                v-if="activeVideoUrl"
                variant="secondary"
                size="sm"
                :href="downloadUrl"
                target="_blank"
            >
                <ArrowDownTrayIcon class="h-4 w-4" />
                Download
            </ZyButton>
            <ZyButton
                v-if="recording.status !== 'recording'"
                variant="danger"
                size="sm"
                @click="showDeleteConfirm = true"
            >
                <TrashIcon class="h-4 w-4" />
                Excluir
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Main Content -->
    <div class="space-y-4">
        <!-- Video Player Area -->
        <div class="video-container rounded-xl overflow-hidden bg-black relative max-w-4xl mx-auto">
            <!-- Video Element -->
            <div class="relative w-full" style="aspect-ratio: 16 / 9;">
                <video
                    v-if="activeVideoUrl"
                    ref="videoRef"
                    :src="activeVideoUrl"
                    class="w-full h-full object-contain bg-black"
                    preload="metadata"
                    playsinline
                    @loadedmetadata="onLoadedMetadata"
                    @timeupdate="onTimeUpdate"
                    @progress="onProgress"
                    @play="onPlay"
                    @pause="onPause"
                    @ended="onEnded"
                    @error="onError"
                    @dblclick="toggleFullscreen"
                    @click="togglePlay"
                />

                <!-- No Video Available -->
                <div
                    v-if="!activeVideoUrl"
                    class="absolute inset-0 flex items-center justify-center bg-dark-900"
                >
                    <div class="flex flex-col items-center gap-4 text-center">
                        <svg class="h-12 w-12 text-dark-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                        <div>
                            <p class="text-body text-dark-400">Arquivo de video nao encontrado</p>
                            <p class="text-body-sm text-dark-500 mt-1">Os segmentos podem ter sido removidos.</p>
                        </div>
                    </div>
                </div>

                <!-- Video Error -->
                <div
                    v-if="videoError && activeVideoUrl"
                    class="absolute inset-0 flex items-center justify-center bg-dark-900/90"
                >
                    <div class="flex flex-col items-center gap-3 text-center">
                        <svg class="h-10 w-10 text-danger-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <p class="text-body text-dark-300">Erro ao carregar o video</p>
                        <button class="text-primary-400 hover:text-primary-300 text-sm underline" @click="videoRef?.load()">
                            Tentar novamente
                        </button>
                    </div>
                </div>

                <!-- Clip Selection Overlay -->
                <div
                    v-if="isSelectingClip"
                    class="absolute top-3 left-1/2 -translate-x-1/2 z-20 bg-dark-800/90 backdrop-blur-sm rounded-lg px-4 py-2 flex items-center gap-3"
                >
                    <ScissorsIcon class="h-4 w-4 text-primary-400" />
                    <span class="text-sm text-white font-medium">Modo Recorte</span>
                    <span v-if="clipStart !== null && clipEnd !== null" class="text-xs text-dark-300">
                        {{ formatTime(clipStart) }} - {{ formatTime(clipEnd) }} ({{ formatTimeShort(clipDuration) }})
                    </span>
                    <span v-else class="text-xs text-dark-400">Arraste na timeline para selecionar</span>
                </div>

                <!-- Status Badge -->
                <div class="absolute top-3 right-3 z-10">
                    <ZyBadge
                        :variant="(statusBadgeVariants[recording.status] || 'secondary') as any"
                        :dot="recording.status === 'recording'"
                    >
                        {{ statusLabels[recording.status] || recording.status }}
                    </ZyBadge>
                </div>

                <!-- Segment indicator -->
                <div v-if="hasTimelineSegments && currentSegment" class="absolute top-3 left-3 z-10">
                    <span class="bg-dark-800/80 text-xs text-dark-300 font-mono px-2 py-1 rounded backdrop-blur-sm">
                        Seg {{ currentSegmentIndex + 1 }}/{{ timelineSegments.length }}
                        &middot; {{ formatDateShort(currentSegment.starts_at) }}
                    </span>
                </div>
            </div>

            <!-- Controls Bar -->
            <div v-if="activeVideoUrl" class="bg-dark-900 border-t border-dark-700">
                <!-- Timeline -->
                <div class="px-4 pt-3 pb-1">
                    <!-- Time Markers -->
                    <div class="relative h-4 mb-1">
                        <span
                            v-for="marker in timeMarkers"
                            :key="marker.time"
                            class="absolute text-[10px] text-dark-500 font-mono -translate-x-1/2 select-none"
                            :style="{ left: marker.percent + '%' }"
                        >
                            {{ marker.label }}
                        </span>
                    </div>

                    <!-- Timeline Bar -->
                    <div
                        ref="timelineRef"
                        class="relative h-8 cursor-pointer group select-none"
                        @mousedown="onTimelineMouseDown"
                        @mousemove="onTimelineMouseMoveHover"
                        @mouseleave="onTimelineMouseLeave"
                    >
                        <!-- Track background -->
                        <div class="absolute top-1/2 -translate-y-1/2 left-0 right-0 h-2 bg-dark-700 rounded-full transition-all group-hover:h-3" />

                        <!-- Segment bars (colored blocks for each segment) -->
                        <div
                            v-for="(bar, idx) in segmentBars"
                            :key="idx"
                            :class="[
                                'absolute top-1/2 -translate-y-1/2 h-2 rounded-sm transition-all group-hover:h-3',
                                bar.active ? 'bg-primary-700/60' : 'bg-dark-600'
                            ]"
                            :style="{ left: bar.left + '%', width: bar.width + '%' }"
                        />

                        <!-- Buffered -->
                        <div
                            class="absolute top-1/2 -translate-y-1/2 left-0 h-2 bg-dark-500/50 rounded-full transition-all group-hover:h-3"
                            :style="{ width: bufferedPercent + '%' }"
                        />

                        <!-- Clip Region -->
                        <div
                            v-if="clipStartPercent !== null && clipEndPercent !== null"
                            class="absolute top-1/2 -translate-y-1/2 h-2 bg-primary-500/30 group-hover:h-3 transition-all border-x-2 border-primary-400"
                            :style="{ left: clipStartPercent + '%', width: (clipEndPercent - clipStartPercent) + '%' }"
                        >
                            <div
                                class="absolute -left-2 top-1/2 -translate-y-1/2 w-4 h-6 bg-primary-400 rounded cursor-ew-resize flex items-center justify-center hover:bg-primary-300 z-20"
                                @mousedown="onClipHandleMouseDown('start', $event)"
                            >
                                <div class="w-0.5 h-3 bg-dark-900 rounded" />
                            </div>
                            <div
                                class="absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-6 bg-primary-400 rounded cursor-ew-resize flex items-center justify-center hover:bg-primary-300 z-20"
                                @mousedown="onClipHandleMouseDown('end', $event)"
                            >
                                <div class="w-0.5 h-3 bg-dark-900 rounded" />
                            </div>
                        </div>

                        <!-- Progress -->
                        <div
                            class="absolute top-1/2 -translate-y-1/2 left-0 h-2 bg-primary-500 rounded-full transition-all group-hover:h-3"
                            :style="{ width: progressPercent + '%' }"
                        />

                        <!-- Playhead -->
                        <div
                            class="absolute top-1/2 -translate-y-1/2 w-3.5 h-3.5 bg-white rounded-full shadow-lg -ml-[7px] z-10 transition-transform group-hover:scale-125"
                            :style="{ left: progressPercent + '%' }"
                        />

                        <!-- Hover tooltip -->
                        <div
                            v-if="hoverTime !== null"
                            class="absolute -top-8 -translate-x-1/2 bg-dark-800 text-white text-[11px] font-mono px-2 py-1 rounded shadow-lg pointer-events-none z-30 whitespace-nowrap"
                            :style="{ left: hoverX + 'px' }"
                        >
                            {{ formatTime(hoverTime) }}
                            <span class="text-dark-400 ml-1">({{ absoluteTimeAt(hoverTime) }})</span>
                        </div>
                    </div>
                </div>

                <!-- Control Buttons -->
                <div class="flex items-center justify-between px-4 py-2">
                    <!-- Left: Play controls -->
                    <div class="flex items-center gap-2">
                        <button
                            class="p-1.5 rounded-lg hover:bg-dark-700 text-white transition-colors"
                            title="Voltar 10s (J)"
                            @click="seekRelative(-10)"
                        >
                            <BackwardIcon class="h-5 w-5" />
                        </button>
                        <button
                            class="p-2 rounded-lg hover:bg-dark-700 text-white transition-colors"
                            :title="isPlaying ? 'Pausar (K)' : 'Reproduzir (K)'"
                            @click="togglePlay"
                        >
                            <PauseIcon v-if="isPlaying" class="h-5 w-5" />
                            <PlayIcon v-else class="h-5 w-5" />
                        </button>
                        <button
                            class="p-1.5 rounded-lg hover:bg-dark-700 text-white transition-colors"
                            title="Avancar 10s (L)"
                            @click="seekRelative(10)"
                        >
                            <ForwardIcon class="h-5 w-5" />
                        </button>

                        <!-- Volume -->
                        <div class="flex items-center gap-1 ml-2">
                            <button
                                class="p-1.5 rounded-lg hover:bg-dark-700 text-white transition-colors"
                                title="Mudo (M)"
                                @click="toggleMute"
                            >
                                <SpeakerXMarkIcon v-if="isMuted" class="h-4 w-4" />
                                <SpeakerWaveIcon v-else class="h-4 w-4" />
                            </button>
                            <input
                                type="range"
                                :value="isMuted ? 0 : volume"
                                min="0"
                                max="1"
                                step="0.05"
                                class="w-14 h-1 accent-primary-500 cursor-pointer"
                                @input="setVolume(($event.target as HTMLInputElement).valueAsNumber)"
                            />
                        </div>

                        <!-- Time display -->
                        <span class="text-xs text-dark-300 font-mono ml-3 select-none">
                            {{ formatTime(globalCurrentTime) }} / {{ formatTime(globalDuration) }}
                        </span>
                    </div>

                    <!-- Right: Tools -->
                    <div class="flex items-center gap-2">
                        <!-- Speed -->
                        <div class="flex items-center gap-1">
                            <button
                                v-for="speed in speeds"
                                :key="speed"
                                :class="[
                                    'text-[11px] px-1.5 py-0.5 rounded font-mono transition-colors',
                                    playbackRate === speed
                                        ? 'bg-primary-500 text-white'
                                        : 'text-dark-400 hover:text-white hover:bg-dark-700'
                                ]"
                                @click="setPlaybackRate(speed)"
                            >
                                {{ speed }}x
                            </button>
                        </div>

                        <div class="h-4 w-px bg-dark-700 mx-1" />

                        <!-- Clip -->
                        <button
                            v-if="!isSelectingClip"
                            class="p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-white transition-colors"
                            title="Recortar"
                            @click="startClipMode"
                        >
                            <ScissorsIcon class="h-4 w-4" />
                        </button>
                        <template v-else>
                            <button
                                class="text-xs px-2 py-1 rounded bg-dark-700 text-dark-300 hover:text-white transition-colors"
                                title="Marcar ponto (I)"
                                @click="setClipFromCurrent"
                            >
                                Marcar
                            </button>
                            <button
                                v-if="clipStart !== null && clipEnd !== null && clipDuration > 1"
                                class="text-xs px-2 py-1 rounded bg-primary-600 text-white hover:bg-primary-500 transition-colors"
                                @click="previewClip"
                            >
                                Preview
                            </button>
                            <button
                                v-if="clipStart !== null && clipEnd !== null && clipDuration > 1"
                                class="text-xs px-2 py-1 rounded bg-secondary-600 text-white hover:bg-secondary-500 transition-colors flex items-center gap-1"
                                :disabled="isClipping"
                                @click="downloadClip"
                            >
                                <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                                {{ isClipping ? 'Gerando...' : 'Baixar Recorte' }}
                            </button>
                            <button
                                class="text-xs px-2 py-1 rounded bg-dark-700 text-dark-400 hover:text-white transition-colors"
                                @click="cancelClipMode"
                            >
                                Cancelar
                            </button>
                        </template>

                        <div class="h-4 w-px bg-dark-700 mx-1" />

                        <!-- Fullscreen -->
                        <button
                            class="p-1.5 rounded-lg hover:bg-dark-700 text-dark-400 hover:text-white transition-colors"
                            title="Tela Cheia (F)"
                            @click="toggleFullscreen"
                        >
                            <ArrowsPointingOutIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid lg:grid-cols-3 gap-4 max-w-4xl mx-auto">
            <!-- Recording Info -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Detalhes</h3>
                </template>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Camera</span>
                        <a
                            v-if="recording.camera"
                            :href="`/cameras/${recording.camera.id}`"
                            class="text-body-sm text-primary-400 hover:underline"
                        >
                            {{ recording.camera.name }}
                        </a>
                        <span v-else class="text-body-sm text-dark-400">--</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Inicio</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ formatDate(recording.started_at) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Fim</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ formatDate(recording.ended_at) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Duracao</span>
                        <span class="text-body-sm font-mono text-[var(--zy-text-primary)]">{{ formatTime(recording.duration_seconds || globalDuration) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Tamanho</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ formatFileSize(recording.file_size_bytes) }}</span>
                    </div>
                    <div v-if="recording.tenant" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Empresa</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ recording.tenant.name }}</span>
                    </div>
                </div>
            </ZyCard>

            <!-- Technical Info -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Tecnico</h3>
                </template>
                <div class="space-y-2.5">
                    <div v-if="recording.codec" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Codec</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)] uppercase font-mono">{{ recording.codec }}</span>
                    </div>
                    <div v-if="recording.resolution" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Resolucao</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)] font-mono">{{ recording.resolution }}</span>
                    </div>
                    <div v-if="recording.fps" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">FPS</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ recording.fps }}</span>
                    </div>
                    <div v-if="hasTimelineSegments" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Segmentos</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)]">{{ timelineSegments.length }}</span>
                    </div>
                    <div v-if="recording.storage_path" class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Path</span>
                        <span class="text-body-sm text-[var(--zy-text-primary)] font-mono text-xs break-all text-right max-w-[200px]">{{ recording.storage_path }}</span>
                    </div>
                </div>
                <div v-if="!recording.codec && !recording.resolution && !hasTimelineSegments" class="text-center py-4">
                    <p class="text-body-sm text-dark-400 italic">Metadados nao disponiveis</p>
                </div>
            </ZyCard>

            <!-- Segments List -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                        Segmentos
                        <span v-if="hasTimelineSegments" class="text-xs text-dark-500 font-normal">({{ timelineSegments.length }})</span>
                    </h3>
                </template>
                <div v-if="hasTimelineSegments" class="space-y-1.5 max-h-64 overflow-y-auto">
                    <div
                        v-for="(seg, idx) in timelineSegments"
                        :key="seg.id"
                        :class="[
                            'flex items-center justify-between py-1.5 px-2 rounded cursor-pointer transition-colors',
                            idx === currentSegmentIndex
                                ? 'bg-primary-900/40 ring-1 ring-primary-500/30'
                                : 'bg-dark-800/50 hover:bg-dark-700/50'
                        ]"
                        @click="shouldAutoPlay = true; currentSegmentIndex = idx"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-dark-500 font-mono w-5">{{ idx + 1 }}</span>
                            <span class="text-xs text-dark-300">{{ formatDateShort(seg.starts_at) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-dark-500">{{ seg.duration_seconds }}s</span>
                            <span class="text-xs text-dark-400 shrink-0">{{ formatFileSize(seg.file_size_bytes) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else-if="segments.length > 0" class="space-y-1.5 max-h-64 overflow-y-auto">
                    <div
                        v-for="seg in segments"
                        :key="seg.filename"
                        class="flex items-center justify-between py-1.5 px-2 bg-dark-800/50 rounded"
                    >
                        <span class="text-xs font-mono text-dark-300 truncate max-w-[60%]">{{ seg.filename }}</span>
                        <span class="text-xs text-dark-400 shrink-0">{{ seg.size_human }}</span>
                    </div>
                </div>
                <div v-else class="text-center py-4">
                    <p class="text-body-sm text-dark-400 italic">Nenhum segmento encontrado</p>
                </div>
            </ZyCard>
        </div>

        <!-- Keyboard Shortcuts Help -->
        <div class="max-w-4xl mx-auto">
            <ZyCard padding="sm">
                <div class="flex flex-wrap gap-4 text-[11px] text-dark-500">
                    <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">Espaco</kbd> Play/Pause</span>
                    <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">J/L</kbd> -10s/+10s</span>
                    <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">F</kbd> Fullscreen</span>
                    <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">M</kbd> Mudo</span>
                    <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">I</kbd> Marcar ponto (recorte)</span>
                    <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">Esc</kbd> Cancelar recorte</span>
                </div>
            </ZyCard>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Gravacao"
        :message="`Tem certeza que deseja excluir esta gravacao de ${recording.camera?.name || 'camera'}? Os arquivos serao removidos permanentemente.`"
        confirm-text="Excluir"
        @confirm="deleteRecording"
        @cancel="showDeleteConfirm = false"
    />
</template>
