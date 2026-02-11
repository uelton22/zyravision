<script setup lang="ts">
/**
 * Recordings Index — Lista de cameras com gravacoes disponiveis (segmentos).
 * Cada camera mostra um resumo dos segmentos e link para o player.
 */

import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZySelect, ZyBadge,
} from '@design';
import {
    MagnifyingGlassIcon, EyeIcon, VideoCameraIcon, FilmIcon,
    ServerStackIcon, ClockIcon, SignalIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface CameraWithRecordings {
    id: string;
    name: string;
    slug: string;
    is_active: boolean;
    is_online: boolean;
    is_recording: boolean;
    continuous_recording: boolean;
    tenant: { id: string; name: string } | null;
    segment_count: number;
    total_size: number;
    total_duration: number;
    first_segment: string | null;
    last_segment: string | null;
    has_recordings: boolean;
}

interface Props {
    cameras: CameraWithRecordings[];
    filters: {
        search?: string;
        tenant_id?: string;
    };
    tenants: { id: string; name: string }[];
    isSuperAdmin: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    cameras: () => [],
    tenants: () => [],
    isSuperAdmin: false,
});

const search = ref(props.filters?.search || '');
const tenantFilter = ref(props.filters?.tenant_id || '');

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Gravacoes' },
];

const tenantOptions = [
    { value: '', label: 'Todas as empresas' },
    ...(props.tenants || []).map((t) => ({ value: t.id, label: t.name })),
];

function formatDuration(seconds: number): string {
    if (!seconds) return '--';
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
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
        hour: '2-digit', minute: '2-digit',
    });
}

function formatDateShort(dateStr: string | null): string {
    if (!dateStr) return '--';
    const d = new Date(dateStr);
    return d.toLocaleString('pt-BR', {
        day: '2-digit', month: '2-digit',
        hour: '2-digit', minute: '2-digit',
    });
}

function applyFilters() {
    router.get('/recordings', {
        search: search.value || undefined,
        tenant_id: tenantFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

const camerasWithRecordings = props.cameras.filter(c => c.has_recordings);
const camerasWithoutRecordings = props.cameras.filter(c => !c.has_recordings);
</script>

<template>
    <Head title="Gravacoes" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Gravacoes"
        :description="isSuperAdmin ? 'Gravacoes de todas as empresas' : 'Gravacoes da sua empresa'"
    />

    <!-- Filters -->
    <ZyCard class="mb-6" padding="md">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <ZyInput
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nome da camera..."
                    icon
                    @keyup.enter="applyFilters"
                >
                    <template #icon>
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </template>
                </ZyInput>
            </div>
            <div v-if="isSuperAdmin" class="w-full sm:w-48">
                <ZySelect
                    v-model="tenantFilter"
                    :options="tenantOptions"
                    placeholder="Empresa"
                    @update:model-value="applyFilters"
                />
            </div>
            <ZyButton variant="secondary" @click="applyFilters">
                Filtrar
            </ZyButton>
        </div>
    </ZyCard>

    <!-- Cameras with recordings -->
    <div v-if="camerasWithRecordings.length > 0" class="space-y-4 mb-6">
        <div
            v-for="cam in camerasWithRecordings"
            :key="cam.id"
            class="group"
        >
            <ZyCard padding="md" class="hover:ring-1 hover:ring-primary-500/30 transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Camera Info -->
                    <div class="flex items-start gap-4 min-w-0">
                        <div class="p-3 rounded-xl bg-indigo-500/10 shrink-0">
                            <VideoCameraIcon class="h-6 w-6 text-indigo-400" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <a
                                    :href="`/cameras/${cam.id}`"
                                    class="text-heading-sm font-semibold text-[var(--zy-text-primary)] hover:underline truncate"
                                >
                                    {{ cam.name }}
                                </a>
                                <ZyBadge v-if="cam.is_recording" variant="danger" dot>Gravando</ZyBadge>
                                <ZyBadge v-else-if="cam.continuous_recording" variant="success">Continua</ZyBadge>
                                <ZyBadge v-if="cam.is_online" variant="success" dot>Online</ZyBadge>
                                <ZyBadge v-else variant="secondary">Offline</ZyBadge>
                            </div>
                            <div v-if="isSuperAdmin && cam.tenant" class="text-caption text-dark-500 mb-2">
                                {{ cam.tenant.name }}
                            </div>

                            <!-- Stats row -->
                            <div class="flex flex-wrap items-center gap-4 text-body-sm text-dark-400">
                                <div class="flex items-center gap-1.5">
                                    <FilmIcon class="h-4 w-4 text-dark-500" />
                                    <span>{{ cam.segment_count }} segmentos</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <ClockIcon class="h-4 w-4 text-dark-500" />
                                    <span>{{ formatDuration(cam.total_duration) }}</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <ServerStackIcon class="h-4 w-4 text-dark-500" />
                                    <span>{{ formatFileSize(cam.total_size) }}</span>
                                </div>
                                <div v-if="cam.last_segment" class="text-dark-500">
                                    Ultimo: {{ formatDateShort(cam.last_segment) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="flex items-center gap-2 shrink-0">
                        <ZyButton variant="primary" size="sm" :href="`/cameras/${cam.id}/recordings`">
                            <EyeIcon class="h-4 w-4" />
                            Ver gravacoes
                        </ZyButton>
                    </div>
                </div>
            </ZyCard>
        </div>
    </div>

    <!-- Cameras without recordings -->
    <div v-if="camerasWithoutRecordings.length > 0">
        <h3 class="text-body-sm text-dark-500 font-medium mb-3 uppercase tracking-wider">
            Cameras sem gravacoes
        </h3>
        <ZyCard padding="md">
            <div class="divide-y divide-dark-200 dark:divide-dark-700">
                <div
                    v-for="cam in camerasWithoutRecordings"
                    :key="cam.id"
                    class="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <VideoCameraIcon class="h-5 w-5 text-dark-600 shrink-0" />
                        <div>
                            <a
                                :href="`/cameras/${cam.id}`"
                                class="text-body-sm text-[var(--zy-text-primary)] hover:underline"
                            >
                                {{ cam.name }}
                            </a>
                            <span v-if="isSuperAdmin && cam.tenant" class="text-caption text-dark-500 ml-2">
                                ({{ cam.tenant.name }})
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <ZyBadge v-if="!cam.is_active" variant="secondary">Inativa</ZyBadge>
                        <ZyBadge v-else-if="!cam.continuous_recording" variant="secondary">Gravacao desativada</ZyBadge>
                        <ZyBadge v-else variant="warning">Sem segmentos</ZyBadge>
                    </div>
                </div>
            </div>
        </ZyCard>
    </div>

    <!-- No cameras at all -->
    <ZyCard v-if="cameras.length === 0" padding="lg">
        <div class="text-center py-12">
            <VideoCameraIcon class="h-12 w-12 text-dark-600 mx-auto mb-4" />
            <p class="text-body text-dark-400">Nenhuma camera encontrada.</p>
            <p class="text-caption text-dark-500 mt-1">Cadastre cameras para comecar a gravar.</p>
            <ZyButton variant="primary" size="sm" href="/cameras/create" class="mt-4">
                Cadastrar Camera
            </ZyButton>
        </div>
    </ZyCard>
</template>
