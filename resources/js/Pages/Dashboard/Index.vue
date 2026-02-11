<script setup lang="ts">
/**
 * Dashboard — Painel principal do ZyraVision
 */

import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyCard, ZyPageHeader, ZyBreadcrumb, ZyBadge, ZyVideoPlayer } from '@design';
import {
    VideoCameraIcon,
    ExclamationTriangleIcon,
    CircleStackIcon,
    UsersIcon,
    SignalIcon,
    SignalSlashIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface QuotaItem { used: number; limit: number; percentage: number; }

interface LiveCamera {
    id: string;
    name: string;
    slug: string;
    is_online: boolean;
    is_recording: boolean;
    snapshot_url: string | null;
    hls_path: string | null;
    location: string | null;
    latest_stream?: {
        id: string;
        status: string;
        hls_url: string | null;
        error_message?: string | null;
    } | null;
}

interface Props {
    stats?: Record<string, any>;
    quotas?: Record<string, QuotaItem>;
    subscription?: any;
    plan?: string;
    liveCameras?: LiveCamera[];
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({}),
    quotas: () => ({}),
    plan: 'starter',
    liveCameras: () => [],
});

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Dashboard' },
];

const s = computed(() => ({
    cameras_online: props.stats?.cameras_online ?? 0,
    cameras_offline: props.stats?.cameras_offline ?? 0,
    cameras_recording: props.stats?.cameras_recording ?? 0,
    alerts_today: props.stats?.alerts_today ?? 0,
    storage_used_gb: props.stats?.storage_used_gb ?? 0,
    storage_limit_gb: props.stats?.storage_limit_gb ?? 0,
    users_active: props.stats?.users_active ?? 0,
    users_limit: props.stats?.users_limit ?? 0,
}));

const statCards = computed(() => [
    {
        label: 'Câmeras Online',
        value: s.value.cameras_online,
        icon: SignalIcon,
        color: 'text-secondary-500',
        bg: 'bg-secondary-100 dark:bg-secondary-900/30',
    },
    {
        label: 'Câmeras Offline',
        value: s.value.cameras_offline,
        icon: SignalSlashIcon,
        color: 'text-danger-500',
        bg: 'bg-danger-100 dark:bg-danger-900/30',
    },
    {
        label: 'Gravando',
        value: s.value.cameras_recording,
        icon: VideoCameraIcon,
        color: 'text-primary-500',
        bg: 'bg-primary-100 dark:bg-primary-900/30',
    },
]);

const quotaItems = [
    { key: 'cameras', label: 'Câmeras', color: 'bg-primary-500' },
    { key: 'users', label: 'Usuários', color: 'bg-secondary-500' },
    { key: 'storage_gb', label: 'Armazenamento', color: 'bg-accent-500' },
    { key: 'streams', label: 'Streams', color: 'bg-warning-500' },
];

function getQuota(key: string) {
    return (props.quotas as any)?.[key] ?? { used: 0, limit: 0, percentage: 0 };
}

function getBarColor(percentage: number): string {
    if (percentage >= 90) return 'bg-danger-500';
    if (percentage >= 70) return 'bg-warning-500';
    return 'bg-primary-500';
}

const planLabels: Record<string, string> = {
    starter: 'Starter',
    professional: 'Professional',
    enterprise: 'Enterprise',
};

const planVariants: Record<string, 'secondary' | 'primary' | 'success'> = {
    starter: 'secondary',
    professional: 'primary',
    enterprise: 'success',
};
</script>

<template>
    <Head title="Dashboard" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Dashboard" description="Visão geral do sistema de monitoramento">
        <template #actions>
            <ZyBadge v-if="plan" :variant="planVariants[plan] || 'secondary'" class="text-body">
                Plano {{ planLabels[plan] || plan }}
            </ZyBadge>
        </template>
    </ZyPageHeader>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <ZyCard v-for="stat in statCards" :key="stat.label" padding="md">
            <div class="flex items-center gap-4">
                <div :class="['h-12 w-12 rounded-zy-md flex items-center justify-center shrink-0', stat.bg]">
                    <component :is="stat.icon" :class="['h-6 w-6', stat.color]" />
                </div>
                <div>
                    <p class="text-caption text-[var(--zy-text-secondary)] font-medium">{{ stat.label }}</p>
                    <p class="text-heading-lg font-bold text-[var(--zy-text-primary)]">{{ stat.value }}</p>
                </div>
            </div>
        </ZyCard>
    </div>

    <!-- Camera Grid -->
    <ZyCard class="mb-8">
        <template #header>
            <div class="flex items-center justify-between">
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Câmeras ao Vivo</h3>
                <span class="text-caption text-dark-500">{{ liveCameras.length }} câmera(s)</span>
            </div>
        </template>

        <div v-if="liveCameras.length > 0" class="grid grid-cols-2 lg:grid-cols-4 gap-3">
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
                    <p v-if="cam.location" class="text-[10px] text-dark-300 truncate">{{ cam.location }}</p>
                </div>
                <div v-if="cam.is_recording" class="absolute top-2 right-2 flex items-center gap-1">
                    <span class="h-2 w-2 bg-danger-500 rounded-full animate-pulse" />
                    <span class="text-[10px] text-danger-400 font-medium">REC</span>
                </div>
            </a>
        </div>

        <div v-else class="text-center py-12">
            <VideoCameraIcon class="h-12 w-12 text-dark-500 mx-auto mb-3" />
            <p class="text-body text-dark-500">Nenhuma câmera cadastrada</p>
            <a href="/cameras/create" class="text-body-sm text-primary-500 hover:text-primary-400 mt-1 inline-block">
                Adicionar primeira câmera
            </a>
        </div>
    </ZyCard>

    <!-- Quotas + Subscription -->
    <div class="grid lg:grid-cols-2 gap-6 mb-8">
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Consumo de Recursos</h3>
            </template>
            <div class="space-y-4">
                <div v-for="item in quotaItems" :key="item.key" class="space-y-1.5">
                    <div class="flex items-center justify-between text-body-sm">
                        <span class="text-dark-600 dark:text-dark-300">{{ item.label }}</span>
                        <span class="font-medium text-[var(--zy-text-primary)]">
                            {{ getQuota(item.key).used }} / {{ getQuota(item.key).limit }}
                        </span>
                    </div>
                    <div class="h-2 bg-dark-200 dark:bg-dark-700 rounded-full overflow-hidden">
                        <div
                            :class="['h-full rounded-full transition-all duration-500', getBarColor(getQuota(item.key).percentage)]"
                            :style="{ width: Math.min(100, getQuota(item.key).percentage) + '%' }"
                        />
                    </div>
                </div>
            </div>
        </ZyCard>

        <!-- Subscription -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Assinatura</h3>
            </template>
            <div v-if="subscription" class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-heading-sm font-bold text-[var(--zy-text-primary)]">
                            {{ subscription.plan?.name || 'Plano' }}
                        </p>
                        <ZyBadge
                            :variant="subscription.status === 'active' ? 'success' : subscription.status === 'trial' ? 'warning' : 'danger'"
                            dot
                        >
                            {{ subscription.status === 'active' ? 'Ativo' : subscription.status === 'trial' ? 'Trial' : subscription.status }}
                        </ZyBadge>
                    </div>
                    <div v-if="subscription.plan?.price > 0" class="text-right">
                        <p class="text-heading font-bold text-[var(--zy-text-primary)]">
                            R$ {{ Number(subscription.plan.price).toFixed(2).replace('.', ',') }}
                        </p>
                        <p class="text-caption text-dark-500">/mês</p>
                    </div>
                </div>

                <div v-if="subscription.plan" class="border-t border-dark-200 dark:border-dark-700 pt-4">
                    <p class="text-caption font-medium text-dark-500 mb-2">Recursos do plano:</p>
                    <div class="grid grid-cols-2 gap-2 text-body-sm">
                        <span>Câmeras: {{ subscription.plan.max_cameras }}</span>
                        <span>Usuários: {{ subscription.plan.max_users }}</span>
                        <span>Storage: {{ subscription.plan.max_storage_gb }}GB</span>
                        <span>Retenção: {{ subscription.plan.retention_days }}d</span>
                        <span>Resolução: {{ subscription.plan.max_resolution }}</span>
                        <span>FPS: {{ subscription.plan.max_fps }}</span>
                    </div>
                </div>
            </div>
            <div v-else class="py-4 text-center">
                <p class="text-body-sm text-dark-500">
                    Plano: <strong>{{ planLabels[plan] || plan || 'Starter' }}</strong>
                </p>
                <p class="text-caption text-dark-400 mt-1">Nenhuma assinatura ativa</p>
            </div>
        </ZyCard>
    </div>
</template>
