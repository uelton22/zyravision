<script setup lang="ts">
/**
 * ZyVideoPlayer — HLS video player with robust reconnection for live streams.
 *
 * Key behaviors:
 * - For live streams: aggressive retry with short initial delays (muxer creation takes 3-6s)
 * - For VOD: minimal retries
 * - Shows loading state (not error) during reconnection attempts
 * - Exponential backoff: 1s, 2s, 4s, 8s...
 * - Respects MAX_RECONNECT_ATTEMPTS before showing error
 */
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import Hls from 'hls.js';

interface Props {
    hlsUrl?: string | null;
    snapshotUrl?: string | null;
    autoplay?: boolean;
    isLive?: boolean;
    isRecording?: boolean;
    reconnectOnError?: boolean;
    /** Max reconnect attempts (default: 5 for live, 2 for VOD) */
    maxReconnect?: number;
}

const props = withDefaults(defineProps<Props>(), {
    hlsUrl: null,
    snapshotUrl: null,
    autoplay: false,
    isLive: false,
    isRecording: false,
    reconnectOnError: true,
    maxReconnect: -1, // -1 means auto: 5 for live, 2 for VOD
});

const videoRef = ref<HTMLVideoElement | null>(null);
const status = ref<'idle' | 'loading' | 'playing' | 'reconnecting' | 'error'>('idle');
const showSnapshot = ref(true);

let hls: Hls | null = null;
let reconnectTimer: ReturnType<typeof setTimeout> | null = null;
let reconnectAttempts = 0;
let mountedAt = 0;

const maxAttempts = computed(() => {
    if (props.maxReconnect >= 0) return props.maxReconnect;
    return props.isLive ? 6 : 2;
});

// Shorter delays for live (muxer needs 2-5s to start):
// Live: 1s, 2s, 3s, 4s, 5s, 6s
// VOD: 3s, 6s
function getReconnectDelay(): number {
    if (props.isLive) {
        return Math.min(1000 + reconnectAttempts * 1000, 6000);
    }
    return Math.min(3000 * Math.pow(2, reconnectAttempts), 15000);
}

function initHls() {
    if (!props.hlsUrl || !videoRef.value) return;

    destroyHls();
    reconnectAttempts = 0;
    mountedAt = Date.now();

    startHlsPlayback();
}

function startHlsPlayback() {
    if (!props.hlsUrl || !videoRef.value) return;

    // Show appropriate status
    if (reconnectAttempts === 0) {
        status.value = 'loading';
    } else {
        status.value = 'reconnecting';
    }
    showSnapshot.value = false;

    // Native HLS support (Safari)
    if (videoRef.value.canPlayType('application/vnd.apple.mpegurl')) {
        videoRef.value.src = props.hlsUrl;
        videoRef.value.addEventListener('loadeddata', onNativeLoaded, { once: true });
        videoRef.value.addEventListener('error', onNativeError, { once: true });
        if (props.autoplay) videoRef.value.play().catch(() => {});
        return;
    }

    if (!Hls.isSupported()) {
        status.value = 'error';
        showSnapshot.value = true;
        return;
    }

    hls = new Hls({
        enableWorker: true,
        lowLatencyMode: props.isLive,
        liveSyncDurationCount: 3,
        liveMaxLatencyDurationCount: 6,
        liveDurationInfinity: props.isLive,
        highBufferWatchdogPeriod: 1,
        // Slightly more generous internal retries for live (muxer startup)
        manifestLoadingMaxRetry: props.isLive ? 4 : 2,
        manifestLoadingRetryDelay: props.isLive ? 1500 : 3000,
        manifestLoadingMaxRetryTimeout: props.isLive ? 8000 : 15000,
        levelLoadingMaxRetry: props.isLive ? 3 : 2,
        levelLoadingRetryDelay: 2000,
        fragLoadingMaxRetry: 3,
        fragLoadingRetryDelay: 2000,
        // Prevent buffer stall issues
        maxBufferLength: props.isLive ? 10 : 30,
        maxMaxBufferLength: props.isLive ? 30 : 600,
    });

    hls.loadSource(props.hlsUrl);
    hls.attachMedia(videoRef.value);

    hls.on(Hls.Events.MANIFEST_PARSED, () => {
        status.value = 'playing';
        reconnectAttempts = 0;
        showSnapshot.value = false;
        if (props.autoplay || props.isLive) {
            videoRef.value?.play().catch(() => {});
        }
    });

    hls.on(Hls.Events.ERROR, (_event, data) => {
        if (data.fatal) {
            if (data.type === Hls.ErrorTypes.MEDIA_ERROR) {
                // Try to recover media errors first
                hls?.recoverMediaError();
            } else {
                // Network or other fatal error
                handlePlaybackError();
            }
        }
    });

    // Handle non-fatal frag loading errors gracefully for live
    if (props.isLive) {
        hls.on(Hls.Events.FRAG_LOADED, () => {
            // Successfully loaded a fragment — connection is good
            if (status.value === 'reconnecting') {
                status.value = 'playing';
                reconnectAttempts = 0;
            }
        });
    }
}

function onNativeLoaded() {
    status.value = 'playing';
    reconnectAttempts = 0;
    showSnapshot.value = false;
}

function onNativeError() {
    handlePlaybackError();
}

function handlePlaybackError() {
    // If reconnect is disabled and we already tried, show error
    if (!props.reconnectOnError && reconnectAttempts >= 1) {
        showError();
        return;
    }

    // Check if we've exceeded max attempts
    if (reconnectAttempts >= maxAttempts.value) {
        showError();
        return;
    }

    // Schedule reconnect
    reconnectAttempts++;
    status.value = 'reconnecting';
    const delay = getReconnectDelay();

    // Clean up current hls instance before retry
    if (hls) {
        hls.destroy();
        hls = null;
    }

    reconnectTimer = setTimeout(() => {
        startHlsPlayback();
    }, delay);
}

function showError() {
    status.value = 'error';
    showSnapshot.value = true;
    destroyHls();
}

function destroyHls() {
    if (reconnectTimer) {
        clearTimeout(reconnectTimer);
        reconnectTimer = null;
    }
    if (hls) {
        hls.destroy();
        hls = null;
    }
}

function retry() {
    reconnectAttempts = 0;
    initHls();
}

watch(() => props.hlsUrl, (newUrl, oldUrl) => {
    if (newUrl && newUrl !== oldUrl) {
        initHls();
    } else if (!newUrl && oldUrl) {
        destroyHls();
        status.value = 'idle';
        showSnapshot.value = true;
    }
});

onMounted(() => {
    if (props.hlsUrl) {
        initHls();
    }
});

onBeforeUnmount(() => {
    destroyHls();
});

// Expose retry for parent components
defineExpose({ retry });
</script>

<template>
    <div class="relative bg-dark-900 rounded-lg overflow-hidden" style="aspect-ratio: 16 / 9;">
        <!-- LIVE Badge -->
        <div
            v-if="isLive && status === 'playing'"
            class="absolute top-3 left-3 z-10 flex items-center gap-1.5 px-2 py-1 bg-red-600 rounded-md shadow-lg"
        >
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
            </span>
            <span class="text-white text-xs font-bold tracking-wider">AO VIVO</span>
        </div>

        <!-- REC Badge -->
        <div
            v-if="isRecording && status === 'playing'"
            class="absolute top-3 right-3 z-10 flex items-center gap-1.5 px-2 py-1 bg-red-700 rounded-md shadow-lg"
        >
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-300 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-red-400"></span>
            </span>
            <span class="text-white text-xs font-bold tracking-wider">REC</span>
        </div>

        <!-- Video Element -->
        <video
            v-show="!showSnapshot && status !== 'idle'"
            ref="videoRef"
            class="w-full h-full object-contain"
            controls
            playsinline
            muted
        />

        <!-- Snapshot Fallback -->
        <img
            v-if="showSnapshot && snapshotUrl"
            :src="snapshotUrl"
            alt="Camera snapshot"
            class="w-full h-full object-contain"
        />

        <!-- Loading Overlay (initial connect) -->
        <div
            v-if="status === 'loading'"
            class="absolute inset-0 flex items-center justify-center bg-dark-900/80"
        >
            <div class="flex flex-col items-center gap-3">
                <svg class="animate-spin h-8 w-8 text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="text-body-sm text-dark-400">Conectando ao stream...</span>
            </div>
        </div>

        <!-- Reconnecting Overlay (non-blocking, shows over snapshot) -->
        <div
            v-if="status === 'reconnecting'"
            class="absolute inset-0 flex items-center justify-center bg-dark-900/60"
        >
            <div class="flex flex-col items-center gap-2 bg-dark-800/90 backdrop-blur-sm rounded-xl px-5 py-4">
                <svg class="animate-spin h-6 w-6 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
                <span class="text-sm text-white font-medium">Reconectando...</span>
                <span class="text-[11px] text-dark-400">
                    Tentativa {{ reconnectAttempts }}/{{ maxAttempts }}
                </span>
            </div>
        </div>

        <!-- Error Overlay (no snapshot) -->
        <div
            v-if="status === 'error' && !snapshotUrl"
            class="absolute inset-0 flex items-center justify-center bg-dark-900"
        >
            <div class="flex flex-col items-center gap-3 text-center px-4">
                <svg class="h-10 w-10 text-dark-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span class="text-body-sm text-dark-400">Nao foi possivel conectar ao stream</span>
                <button
                    class="text-body-sm text-primary-400 hover:text-primary-300 underline"
                    @click="retry"
                >
                    Tentar novamente
                </button>
            </div>
        </div>

        <!-- Error overlay with snapshot behind -->
        <div
            v-if="status === 'error' && snapshotUrl"
            class="absolute inset-0 flex items-end justify-center pb-4"
        >
            <div class="bg-black/70 backdrop-blur-sm rounded-lg px-4 py-2 flex items-center gap-3">
                <span class="text-white/80 text-sm">Stream indisponivel</span>
                <button
                    class="text-sm text-primary-400 hover:text-primary-300 underline font-medium"
                    @click="retry"
                >
                    Reconectar
                </button>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-if="status === 'idle' && !snapshotUrl"
            class="absolute inset-0 flex items-center justify-center bg-dark-900"
        >
            <div class="flex flex-col items-center gap-3 text-center px-4">
                <svg class="h-10 w-10 text-dark-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span class="text-body-sm text-dark-500">Sem preview disponivel</span>
            </div>
        </div>
    </div>
</template>
