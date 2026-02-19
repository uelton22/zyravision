<script setup lang="ts">
/**
 * Cameras/Recordings — HLS-based continuous playback with VMS-style timeline.
 * Uses hls.js to play a dynamic M3U8 playlist containing all segments for the day.
 * No flickering between segments — seamless playback like professional VMS systems.
 */

import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Hls from 'hls.js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge, ZyInput,
} from '@design';
import {
    PlayIcon, PauseIcon, ForwardIcon, BackwardIcon,
    SpeakerWaveIcon, SpeakerXMarkIcon, ArrowsPointingOutIcon,
    CalendarDaysIcon, FilmIcon, ServerStackIcon, ClockIcon,
    ArrowDownTrayIcon, ArrowLeftIcon,
    ScissorsIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface TimelineSegment {
    id: string;
    starts_at: string;
    ends_at: string | null;
    duration_seconds: number;
    file_size_bytes: number;
    status: string;
    stream_url: string;
    has_local?: boolean;
    has_remote?: boolean;
    playlist_offset_seconds: number;
}

interface AvailableDate {
    date: string;
    segment_count: number;
    total_size: number;
}

interface Props {
    camera: {
        id: string;
        name: string;
        slug: string;
        tenant?: { id: string; name: string } | null;
    };
    segments: TimelineSegment[];
    selectedDate: string;
    availableDates: AvailableDate[];
    hlsPlaylistUrl: string;
    stats: {
        total_segments: number;
        total_size: number;
        today_segments: number;
        today_duration: number;
        today_size: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Cameras', href: '/cameras' },
    { label: props.camera.name, href: `/cameras/${props.camera.id}` },
    { label: 'Gravacoes' },
];

// ─── Date navigation ─────────────────
const selectedDate = ref(props.selectedDate);

function changeDate(date: string) {
    selectedDate.value = date;
    router.get(`/cameras/${props.camera.id}/recordings`, { date }, {
        preserveState: false,
        replace: true,
    });
}

function goToday() {
    changeDate(new Date().toISOString().split('T')[0]);
}

function goPreviousDay() {
    const d = new Date(selectedDate.value);
    d.setDate(d.getDate() - 1);
    changeDate(d.toISOString().split('T')[0]);
}

function goNextDay() {
    const d = new Date(selectedDate.value);
    d.setDate(d.getDate() + 1);
    const today = new Date().toISOString().split('T')[0];
    const next = d.toISOString().split('T')[0];
    if (next <= today) changeDate(next);
}

// ─── Time Helpers ────────────────────
function dateToSecondsFromMidnight(dateStr: string): number {
    const d = new Date(dateStr);
    return d.getHours() * 3600 + d.getMinutes() * 60 + d.getSeconds();
}

function secondsToTimeString(secs: number): string {
    const h = Math.floor(secs / 3600) % 24;
    const m = Math.floor((secs % 3600) / 60);
    const s = Math.floor(secs % 60);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
}

function secondsToShortTime(secs: number): string {
    const h = Math.floor(secs / 3600) % 24;
    const m = Math.floor((secs % 3600) / 60);
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

// ─── Parsed Segments ────────────────
interface ParsedSegment {
    index: number;
    seg: TimelineSegment;
    startSec: number;
    endSec: number;
    durationSec: number;
    playlistOffset: number;
}

const MAX_SEGMENT_DURATION = 600;

const parsedSegments = computed<ParsedSegment[]>(() => {
    return props.segments.map((seg, i) => {
        const startSec = dateToSecondsFromMidnight(seg.starts_at);
        // Use duration_seconds directly from backend to stay consistent
        // with playlist_offset_seconds and the M3U8 #EXTINF values
        const dur = Math.min(seg.duration_seconds || 60, MAX_SEGMENT_DURATION);

        return {
            index: i,
            seg,
            startSec,
            endSec: startSec + dur,
            durationSec: dur,
            playlistOffset: seg.playlist_offset_seconds,
        };
    });
});

// Total HLS playlist duration
const totalPlaylistDuration = computed(() => {
    if (!parsedSegments.value.length) return 0;
    const last = parsedSegments.value[parsedSegments.value.length - 1];
    return last.playlistOffset + last.durationSec;
});

// ─── HLS Player ──────────────────────
const videoRef = ref<HTMLVideoElement | null>(null);
let hlsInstance: Hls | null = null;
const isPlaying = ref(false);
const isMuted = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const volume = ref(1);
const playbackRate = ref(1);
const isVideoReady = ref(false);
const videoError = ref(false);
const hlsLoaded = ref(false);

// Track the current playlist offset so we can map player position → absolute time
const currentStartFrom = ref(0);

// Map HLS player position → real-world absolute time (seconds from midnight).
// The player always starts at 0 relative to the trimmed playlist,
// so we add currentStartFrom to get the cumulative playlist position.
function playlistPosToAbsoluteTime(pos: number): number {
    const cumulativePos = pos + currentStartFrom.value;
    for (const ps of parsedSegments.value) {
        if (cumulativePos >= ps.playlistOffset && cumulativePos < ps.playlistOffset + ps.durationSec) {
            return ps.startSec + (cumulativePos - ps.playlistOffset);
        }
    }
    if (parsedSegments.value.length > 0) {
        const last = parsedSegments.value[parsedSegments.value.length - 1];
        return last.endSec;
    }
    return 0;
}

const currentAbsoluteTime = computed(() => playlistPosToAbsoluteTime(currentTime.value));

const currentSegmentIndex = computed(() => {
    const cumulativePos = currentTime.value + currentStartFrom.value;
    for (const ps of parsedSegments.value) {
        if (cumulativePos >= ps.playlistOffset && cumulativePos < ps.playlistOffset + ps.durationSec) {
            return ps.index;
        }
    }
    return 0;
});

function createHlsInstance(): Hls {
    return new Hls({
        enableWorker: true,
        lowLatencyMode: false,
        maxBufferLength: 10,
        maxMaxBufferLength: 30,
        maxBufferSize: 30 * 1000 * 1000,
        maxBufferHole: 0.5,
        backBufferLength: 10,
        startFragPrefetch: false,
        appendErrorMaxRetry: 5,
        xhrSetup: (xhr) => {
            xhr.withCredentials = true;
        },
    });
}

/**
 * Load (or reload) the HLS playlist starting from a specific cumulative offset.
 * This mirrors the mobile app's approach: instead of seeking within a loaded playlist
 * (which overflows the browser's SourceBuffer with 40MB segments), we request
 * a trimmed M3U8 from the backend. The player always starts at position 0.
 */
function loadPlaylistFrom(startFromSeconds: number = 0) {
    if (!videoRef.value || !props.hlsPlaylistUrl || !props.segments.length) return;

    destroyHlsPlayer();
    currentStartFrom.value = startFromSeconds;

    const url = startFromSeconds > 0
        ? `${props.hlsPlaylistUrl}&start_from=${startFromSeconds}`
        : props.hlsPlaylistUrl;

    if (Hls.isSupported()) {
        hlsInstance = createHlsInstance();

        hlsInstance.loadSource(url);
        hlsInstance.attachMedia(videoRef.value);

        hlsInstance.on(Hls.Events.MANIFEST_PARSED, () => {
            hlsLoaded.value = true;
            isVideoReady.value = true;
            videoError.value = false;
            if (videoRef.value) {
                duration.value = videoRef.value.duration || totalPlaylistDuration.value;
                videoRef.value.playbackRate = playbackRate.value;
                videoRef.value.muted = isMuted.value;
                videoRef.value.play().catch(() => {});
            }
        });

        hlsInstance.on(Hls.Events.ERROR, (_event, data) => {
            if (data.fatal) {
                console.error('[HLS] Fatal:', data.type, data.details);
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        hlsInstance?.startLoad();
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        hlsInstance?.recoverMediaError();
                        break;
                    default:
                        videoError.value = true;
                        break;
                }
            }
        });
    } else if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
        videoRef.value.src = url;
        videoRef.value.addEventListener('loadedmetadata', () => {
            hlsLoaded.value = true;
            isVideoReady.value = true;
        });
    }
}

function initHlsPlayer() {
    loadPlaylistFrom(0);
}

function destroyHlsPlayer() {
    if (hlsInstance) {
        hlsInstance.destroy();
        hlsInstance = null;
    }
    hlsLoaded.value = false;
    isVideoReady.value = false;
}

// ─── VMS-Style Timeline ──────────────
const timelineContainerRef = ref<HTMLDivElement | null>(null);
const isDragging = ref(false);
const didDragThisGesture = ref(false);
const dragStartX = ref(0);
const dragStartScrollTime = ref(0);

const ZOOM_LEVELS = [1800, 3600, 7200, 14400, 28800];
const zoomIndex = ref(2);
const visibleSeconds = computed(() => ZOOM_LEVELS[zoomIndex.value]);

const scrollCenterSec = ref(0);

const isFollowingPlayback = ref(true);

watch(currentAbsoluteTime, (newTime) => {
    if (isFollowingPlayback.value && isPlaying.value && !isDragging.value) {
        scrollCenterSec.value = newTime;
    }
});

const viewStartSec = computed(() => scrollCenterSec.value - visibleSeconds.value / 2);
const viewEndSec = computed(() => scrollCenterSec.value + visibleSeconds.value / 2);

function secToPercent(sec: number): number {
    return ((sec - viewStartSec.value) / visibleSeconds.value) * 100;
}

const visibleTimeMarkers = computed(() => {
    const span = visibleSeconds.value;
    let interval: number;
    if (span <= 1800) interval = 300;
    else if (span <= 3600) interval = 600;
    else if (span <= 7200) interval = 900;
    else if (span <= 14400) interval = 1800;
    else interval = 3600;

    const markers: { sec: number; label: string; percent: number; isMajor: boolean }[] = [];
    const start = Math.ceil(viewStartSec.value / interval) * interval;
    const end = viewEndSec.value;

    for (let t = start; t <= end; t += interval) {
        const majorInterval = interval >= 1800 ? interval : interval * 2;
        markers.push({
            sec: t,
            label: secondsToShortTime(t),
            percent: secToPercent(t),
            isMajor: t % majorInterval === 0,
        });
    }
    return markers;
});

const visibleSegmentBars = computed(() => {
    return parsedSegments.value
        .filter(ps => ps.endSec > viewStartSec.value && ps.startSec < viewEndSec.value)
        .map(ps => {
            const clampedStart = Math.max(ps.startSec, viewStartSec.value);
            const clampedEnd = Math.min(ps.endSec, viewEndSec.value);
            const seg = ps.seg as TimelineSegment;
            return {
                index: ps.index,
                left: secToPercent(clampedStart),
                width: ((clampedEnd - clampedStart) / visibleSeconds.value) * 100,
                active: ps.index === currentSegmentIndex.value,
                isRemote: !!(seg.has_remote),
            };
        });
});

const playheadPercent = computed(() => secToPercent(currentAbsoluteTime.value));

// ─── Timeline Events ─────────────────
function onTimelineMouseDown(e: MouseEvent) {
    isDragging.value = true;
    didDragThisGesture.value = false;
    isFollowingPlayback.value = false;
    dragStartX.value = e.clientX;
    dragStartScrollTime.value = scrollCenterSec.value;
    document.addEventListener('mousemove', onTimelineMouseMove);
    document.addEventListener('mouseup', onTimelineMouseUp);
    e.preventDefault();
}

function onTimelineMouseMove(e: MouseEvent) {
    if (!isDragging.value || !timelineContainerRef.value) return;
    didDragThisGesture.value = true;
    const containerWidth = timelineContainerRef.value.clientWidth;
    const deltaX = e.clientX - dragStartX.value;
    const deltaSec = -(deltaX / containerWidth) * visibleSeconds.value;
    scrollCenterSec.value = dragStartScrollTime.value + deltaSec;
}

function onTimelineMouseUp() {
    isDragging.value = false;
    document.removeEventListener('mousemove', onTimelineMouseMove);
    document.removeEventListener('mouseup', onTimelineMouseUp);
}

function onTimelineClick(e: MouseEvent) {
    if (!timelineContainerRef.value || didDragThisGesture.value) return;
    const rect = timelineContainerRef.value.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const clickPercent = x / rect.width;
    const clickSec = viewStartSec.value + clickPercent * visibleSeconds.value;
    seekToAbsoluteTime(clickSec);
    isFollowingPlayback.value = true;
}

function onTimelineWheel(e: WheelEvent) {
    e.preventDefault();
    if (e.deltaY > 0 && zoomIndex.value < ZOOM_LEVELS.length - 1) {
        zoomIndex.value++;
    } else if (e.deltaY < 0 && zoomIndex.value > 0) {
        zoomIndex.value--;
    }
}

function zoomIn() { if (zoomIndex.value > 0) zoomIndex.value--; }
function zoomOut() { if (zoomIndex.value < ZOOM_LEVELS.length - 1) zoomIndex.value++; }

function followPlayback() {
    isFollowingPlayback.value = true;
    scrollCenterSec.value = currentAbsoluteTime.value;
}

// ─── Seek to absolute time ───────────
// Finds the cumulative playlist offset for the given absolute time, then reloads
// the playlist from that offset. This avoids SourceBuffer overflow.
function seekToAbsoluteTime(absSec: number) {
    // Find which parsed segment contains this time
    let targetOffset = 0;
    for (const ps of parsedSegments.value) {
        if (absSec >= ps.startSec && absSec < ps.endSec) {
            targetOffset = ps.playlistOffset + (absSec - ps.startSec);
            break;
        }
        if (absSec < ps.startSec) {
            targetOffset = ps.playlistOffset;
            break;
        }
        targetOffset = ps.playlistOffset + ps.durationSec;
    }

    scrollCenterSec.value = absSec;
    isFollowingPlayback.value = true;
    loadPlaylistFrom(Math.floor(targetOffset));
}

// ─── Jump to specific segment ────────
function jumpToSegment(idx: number) {
    if (idx < 0 || idx >= parsedSegments.value.length) return;
    const ps = parsedSegments.value[idx];

    scrollCenterSec.value = ps.startSec;
    isFollowingPlayback.value = true;
    loadPlaylistFrom(Math.floor(ps.playlistOffset));
}

// ─── Video Controls ──────────────────
function togglePlay() {
    if (!videoRef.value) return;
    if (isPlaying.value) {
        videoRef.value.pause();
    } else {
        videoRef.value.play().catch(() => {});
        isFollowingPlayback.value = true;
    }
}

function toggleMute() {
    if (!videoRef.value) return;
    isMuted.value = !isMuted.value;
    videoRef.value.muted = isMuted.value;
}

function setPlaybackRate(rate: number) {
    if (!videoRef.value) return;
    playbackRate.value = rate;
    videoRef.value.playbackRate = rate;
}

function seekRelative(delta: number) {
    const target = currentAbsoluteTime.value + delta;
    seekToAbsoluteTime(target);
    isFollowingPlayback.value = true;
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

// ─── Video Events ────────────────────
function onTimeUpdate() {
    if (!videoRef.value || isDragging.value) return;
    currentTime.value = videoRef.value.currentTime;
}

function onPlay() { isPlaying.value = true; }
function onPause() { isPlaying.value = false; }

function onError() {
    videoError.value = true;
    isVideoReady.value = false;
}

function retryPlayback() {
    videoError.value = false;
    initHlsPlayer();
}

// ─── Lifecycle ───────────────────────
onMounted(() => {
    if (parsedSegments.value.length > 0) {
        scrollCenterSec.value = parsedSegments.value[0].startSec;
    }

    nextTick(() => {
        initHlsPlayer();
    });

    document.addEventListener('keydown', onKeyDown);
});

onBeforeUnmount(() => {
    destroyHlsPlayer();
    document.removeEventListener('keydown', onKeyDown);
    document.removeEventListener('mousemove', onTimelineMouseMove);
    document.removeEventListener('mouseup', onTimelineMouseUp);
});

// ─── Formatters ──────────────────────
function formatFileSize(bytes: number): string {
    if (!bytes) return '--';
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function formatDuration(seconds: number): string {
    if (!seconds || isNaN(seconds)) return '--';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
}

function formatDateFull(dateStr: string | null): string {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR', {
        hour: '2-digit', minute: '2-digit', second: '2-digit',
    });
}

function formatSelectedDate(date: string): string {
    const d = new Date(date + 'T12:00:00');
    return d.toLocaleDateString('pt-BR', {
        weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
    });
}

const zoomLabel = computed(() => {
    const secs = visibleSeconds.value;
    if (secs >= 3600) return `${secs / 3600}h`;
    return `${secs / 60}m`;
});

// ─── Keyboard shortcuts ─────────────
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
        case '+':
        case '=':
            zoomIn();
            break;
        case '-':
            zoomOut();
            break;
    }
}

const speeds = [0.25, 0.5, 1, 1.5, 2];
</script>

<template>
    <Head :title="`Gravacoes - ${camera.name}`" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        :title="`Gravacoes de ${camera.name}`"
        :description="formatSelectedDate(selectedDate)"
    >
        <template #actions>
            <ZyButton variant="secondary" size="sm" :href="`/cameras/${camera.id}`">
                <ArrowLeftIcon class="h-4 w-4" />
                Voltar
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <ZyCard padding="sm">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-indigo-500/10">
                    <FilmIcon class="h-5 w-5 text-indigo-400" />
                </div>
                <div>
                    <p class="text-xs text-dark-500">Segmentos hoje</p>
                    <p class="text-lg font-bold text-white">{{ stats.today_segments }}</p>
                </div>
            </div>
        </ZyCard>
        <ZyCard padding="sm">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-500/10">
                    <ClockIcon class="h-5 w-5 text-blue-400" />
                </div>
                <div>
                    <p class="text-xs text-dark-500">Duracao hoje</p>
                    <p class="text-lg font-bold text-white">{{ formatDuration(stats.today_duration) }}</p>
                </div>
            </div>
        </ZyCard>
        <ZyCard padding="sm">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-green-500/10">
                    <ServerStackIcon class="h-5 w-5 text-green-400" />
                </div>
                <div>
                    <p class="text-xs text-dark-500">Tamanho hoje</p>
                    <p class="text-lg font-bold text-white">{{ formatFileSize(stats.today_size) }}</p>
                </div>
            </div>
        </ZyCard>
        <ZyCard padding="sm">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-amber-500/10">
                    <CalendarDaysIcon class="h-5 w-5 text-amber-400" />
                </div>
                <div>
                    <p class="text-xs text-dark-500">Total geral</p>
                    <p class="text-lg font-bold text-white">{{ stats.total_segments }} seg.</p>
                </div>
            </div>
        </ZyCard>
    </div>

    <div class="grid lg:grid-cols-4 gap-6">
        <!-- Main Content: Player + Timeline -->
        <div class="lg:col-span-3 space-y-4">
            <!-- Date Navigation -->
            <div class="flex items-center gap-2">
                <ZyButton variant="ghost" size="sm" @click="goPreviousDay">
                    <BackwardIcon class="h-4 w-4" />
                </ZyButton>
                <ZyInput
                    :model-value="selectedDate"
                    type="date"
                    class="w-44"
                    @change="changeDate(($event.target as HTMLInputElement).value)"
                />
                <ZyButton variant="ghost" size="sm" @click="goNextDay">
                    <ForwardIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="secondary" size="sm" @click="goToday">
                    Hoje
                </ZyButton>
                <span class="text-sm text-dark-400 ml-2 capitalize">
                    {{ formatSelectedDate(selectedDate) }}
                </span>
            </div>

            <!-- HLS Video Player -->
            <div v-if="segments.length > 0" class="video-container rounded-xl overflow-hidden bg-black relative">
                <div class="relative w-full" style="aspect-ratio: 16 / 9;">
                    <video
                        ref="videoRef"
                        class="absolute inset-0 w-full h-full object-contain bg-black"
                        playsinline
                        @timeupdate="onTimeUpdate"
                        @play="onPlay"
                        @pause="onPause"
                        @error="onError"
                        @dblclick="toggleFullscreen"
                        @click="togglePlay"
                    />

                    <!-- Video Error -->
                    <div
                        v-if="videoError"
                        class="absolute inset-0 flex items-center justify-center bg-dark-900/90"
                    >
                        <div class="flex flex-col items-center gap-3 text-center">
                            <svg class="h-10 w-10 text-danger-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                            </svg>
                            <p class="text-body text-dark-300">Erro ao carregar a gravacao</p>
                            <button class="text-primary-400 hover:text-primary-300 text-sm underline" @click="retryPlayback">
                                Tentar novamente
                            </button>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div
                        v-if="!isVideoReady && !videoError && segments.length > 0"
                        class="absolute inset-0 flex items-center justify-center bg-dark-900/80"
                    >
                        <div class="flex flex-col items-center gap-3">
                            <div class="h-8 w-8 border-2 border-primary-500 border-t-transparent rounded-full animate-spin" />
                            <p class="text-xs text-dark-400">Carregando playlist HLS...</p>
                        </div>
                    </div>

                    <!-- HLS badge -->
                    <div v-if="hlsLoaded" class="absolute top-3 left-3 z-10">
                        <span class="bg-dark-800/80 text-xs text-dark-300 font-mono px-2 py-1 rounded backdrop-blur-sm">
                            HLS
                        </span>
                    </div>

                    <!-- Recording badge -->
                    <div class="absolute top-3 right-3 z-10">
                        <ZyBadge variant="success" dot>REC</ZyBadge>
                    </div>
                </div>

                <!-- Controls Bar -->
                <div class="bg-dark-900 border-t border-dark-700">
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex items-center gap-2">
                            <button class="p-1 rounded hover:bg-dark-700 text-white transition-colors" title="Recortar" @click="seekRelative(-10)">
                                <ScissorsIcon class="h-4 w-4" />
                            </button>
                            <button class="p-1 rounded hover:bg-dark-700 text-white transition-colors" title="Voltar 10s" @click="seekRelative(-10)">
                                <BackwardIcon class="h-4 w-4" />
                            </button>
                            <button class="p-1.5 rounded hover:bg-dark-700 text-white transition-colors" :title="isPlaying ? 'Pausar' : 'Reproduzir'" @click="togglePlay">
                                <PauseIcon v-if="isPlaying" class="h-5 w-5" />
                                <PlayIcon v-else class="h-5 w-5" />
                            </button>
                            <button class="p-1 rounded hover:bg-dark-700 text-white transition-colors" title="Avancar 10s" @click="seekRelative(10)">
                                <ForwardIcon class="h-4 w-4" />
                            </button>

                            <button class="p-1 rounded hover:bg-dark-700 text-white transition-colors ml-1" @click="toggleMute">
                                <SpeakerXMarkIcon v-if="isMuted" class="h-4 w-4" />
                                <SpeakerWaveIcon v-else class="h-4 w-4" />
                            </button>

                            <!-- Live clock time display -->
                            <div class="ml-3 bg-dark-800 rounded px-3 py-1">
                                <span class="text-sm text-white font-mono font-bold tracking-wide">
                                    {{ secondsToTimeString(currentAbsoluteTime) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-0.5">
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

                            <button class="p-1 rounded hover:bg-dark-700 text-dark-400 hover:text-white transition-colors" title="Tela Cheia" @click="toggleFullscreen">
                                <ArrowsPointingOutIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- VMS Timeline -->
                    <div class="px-4 pb-3">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-1">
                                <button
                                    class="text-[10px] text-dark-400 hover:text-white px-1.5 py-0.5 rounded hover:bg-dark-700 transition-colors"
                                    @click="zoomIn"
                                >+</button>
                                <span class="text-[10px] text-dark-500 font-mono w-8 text-center">{{ zoomLabel }}</span>
                                <button
                                    class="text-[10px] text-dark-400 hover:text-white px-1.5 py-0.5 rounded hover:bg-dark-700 transition-colors"
                                    @click="zoomOut"
                                >-</button>
                            </div>
                            <button
                                v-if="!isFollowingPlayback"
                                class="text-[10px] text-primary-400 hover:text-primary-300 px-2 py-0.5 rounded bg-primary-500/10 hover:bg-primary-500/20 transition-colors"
                                @click="followPlayback"
                            >
                                Seguir reproducao
                            </button>
                        </div>

                        <div
                            ref="timelineContainerRef"
                            class="relative h-14 cursor-grab select-none overflow-hidden rounded-lg bg-dark-800"
                            :class="{ 'cursor-grabbing': isDragging }"
                            @mousedown="onTimelineMouseDown"
                            @click="onTimelineClick"
                            @wheel.prevent="onTimelineWheel"
                        >
                            <!-- Time labels -->
                            <div class="absolute bottom-0 left-0 right-0 h-4">
                                <template v-for="marker in visibleTimeMarkers" :key="marker.sec">
                                    <div class="absolute bottom-0" :style="{ left: marker.percent + '%' }">
                                        <div :class="['absolute bottom-full left-0 w-px', marker.isMajor ? 'h-3 bg-dark-500' : 'h-1.5 bg-dark-600']" />
                                        <span
                                            v-if="marker.isMajor"
                                            class="absolute left-0 -translate-x-1/2 text-[10px] text-dark-400 font-mono whitespace-nowrap"
                                        >{{ marker.label }}</span>
                                    </div>
                                </template>
                            </div>

                            <!-- Segment bars -->
                            <div class="absolute top-1 left-0 right-0 bottom-5">
                                <div
                                    v-for="bar in visibleSegmentBars"
                                    :key="bar.index"
                                    :class="[
                                        'absolute top-0 bottom-0 rounded-sm transition-colors cursor-pointer',
                                        bar.isRemote
                                            ? (bar.active ? 'bg-purple-600/80' : 'bg-purple-700/50')
                                            : (bar.active ? 'bg-violet-600/80' : 'bg-violet-700/50')
                                    ]"
                                    :style="{ left: bar.left + '%', width: Math.max(bar.width, 0.2) + '%' }"
                                />
                            </div>

                            <!-- Center playhead -->
                            <div
                                class="absolute top-0 bottom-0 w-0.5 bg-white z-20 pointer-events-none"
                                :style="{ left: playheadPercent + '%' }"
                            >
                                <div class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-0 h-0 border-l-[5px] border-r-[5px] border-t-[6px] border-l-transparent border-r-transparent border-t-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <ZyCard v-else padding="lg">
                <div class="text-center py-12">
                    <FilmIcon class="h-12 w-12 text-dark-600 mx-auto mb-4" />
                    <p class="text-body text-dark-400">Nenhuma gravacao encontrada para este dia.</p>
                    <p class="text-caption text-dark-500 mt-1">Selecione outra data ou verifique se a camera esta gravando.</p>
                    <div v-if="availableDates.length > 0" class="mt-4">
                        <p class="text-xs text-dark-500 mb-2">Datas com gravacoes disponiveis:</p>
                        <div class="flex flex-wrap justify-center gap-2">
                            <ZyButton
                                v-for="d in availableDates.slice(0, 5)"
                                :key="d.date"
                                variant="ghost"
                                size="sm"
                                @click="changeDate(d.date)"
                            >
                                {{ new Date(d.date + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) }}
                                <ZyBadge variant="secondary" class="ml-1">{{ d.segment_count }}</ZyBadge>
                            </ZyButton>
                        </div>
                    </div>
                </div>
            </ZyCard>

            <!-- Keyboard shortcuts -->
            <div v-if="segments.length > 0">
                <ZyCard padding="sm">
                    <div class="flex flex-wrap gap-4 text-[11px] text-dark-500">
                        <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">Espaco</kbd> Play/Pause</span>
                        <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">J/L</kbd> -10s/+10s</span>
                        <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">F</kbd> Fullscreen</span>
                        <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">M</kbd> Mudo</span>
                        <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">+/-</kbd> Zoom</span>
                        <span><kbd class="bg-dark-700 text-dark-300 px-1.5 py-0.5 rounded text-[10px]">Arrastar</kbd> Navegar</span>
                    </div>
                </ZyCard>
            </div>
        </div>

        <!-- Sidebar: Dates + Info -->
        <div class="space-y-4">
            <ZyCard>
                <template #header>
                    <div class="flex items-center gap-2">
                        <CalendarDaysIcon class="h-5 w-5 text-indigo-400" />
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Datas</h3>
                    </div>
                </template>
                <div v-if="availableDates.length > 0" class="space-y-1 max-h-48 overflow-y-auto zy-scrollbar">
                    <div
                        v-for="d in availableDates"
                        :key="d.date"
                        :class="[
                            'flex items-center justify-between py-1.5 px-2 rounded cursor-pointer transition-colors',
                            d.date === selectedDate
                                ? 'bg-primary-900/40 ring-1 ring-primary-500/30'
                                : 'bg-dark-800/50 hover:bg-dark-700/50'
                        ]"
                        @click="changeDate(d.date)"
                    >
                        <span class="text-xs text-dark-300">
                            {{ new Date(d.date + 'T12:00:00').toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: '2-digit' }) }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-dark-500">{{ d.segment_count }} seg</span>
                            <span class="text-[10px] text-dark-500">{{ formatFileSize(d.total_size) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-4">
                    <p class="text-xs text-dark-400 italic">Nenhuma data disponivel</p>
                </div>
            </ZyCard>

            <!-- Segments info (compact) -->
            <ZyCard>
                <template #header>
                    <div class="flex items-center gap-2">
                        <FilmIcon class="h-5 w-5 text-blue-400" />
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                            Segmentos
                            <span class="text-xs text-dark-500 font-normal">({{ segments.length }})</span>
                        </h3>
                    </div>
                </template>
                <div v-if="segments.length > 0" class="space-y-1 max-h-[400px] overflow-y-auto zy-scrollbar">
                    <div
                        v-for="(seg, idx) in segments"
                        :key="seg.id"
                        :class="[
                            'flex items-center justify-between py-2 px-2 rounded cursor-pointer transition-colors group',
                            idx === currentSegmentIndex
                                ? 'bg-primary-900/40 ring-1 ring-primary-500/30'
                                : 'bg-dark-800/50 hover:bg-dark-700/50'
                        ]"
                        @click="jumpToSegment(idx)"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-dark-500 font-mono w-5 text-right">{{ idx + 1 }}</span>
                            <PlayIcon v-if="idx === currentSegmentIndex && isPlaying" class="h-3 w-3 text-primary-400 animate-pulse" />
                            <span class="text-xs font-mono text-white font-semibold">{{ formatDateFull(seg.starts_at) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] text-dark-500">{{ seg.duration_seconds }}s</span>
                            <span class="text-xs text-dark-400">{{ formatFileSize(seg.file_size_bytes) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-4">
                    <p class="text-xs text-dark-400 italic">Nenhum segmento neste dia</p>
                </div>
            </ZyCard>
        </div>
    </div>
</template>
