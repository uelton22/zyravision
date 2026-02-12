<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader,
    ZyBreadcrumb,
    ZyCard,
    ZyButton,
    ZyAlert,
    ZyConfirmDialog,
} from '@design';
import {
    WrenchScrewdriverIcon,
    FilmIcon,
    TrashIcon,
    ServerStackIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

const { addToast } = useToast();

interface CacheDetail {
    size: number;
    size_formatted: string;
    files: number;
}

interface CacheInfo {
    segment_cache: CacheDetail;
    views_cache: CacheDetail;
    app_cache: CacheDetail;
    total_size: number;
    total_size_formatted: string;
}

interface Props {
    cacheInfo: CacheInfo;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Configuracoes' },
    { label: 'Sistema' },
];

// ─── Segment Cache ───────────────────────────────────────
const showClearSegmentConfirm = ref(false);
const clearingSegmentCache = ref(false);

function clearSegmentCache() {
    clearingSegmentCache.value = true;
    router.post('/settings/system/clear-segment-cache', {}, {
        preserveScroll: true,
        onSuccess: () => {
            showClearSegmentConfirm.value = false;
            addToast({
                type: 'success',
                title: 'Sucesso',
                message: 'Cache de segmentos de video limpo com sucesso.',
            });
        },
        onError: () => {
            addToast({
                type: 'error',
                title: 'Erro',
                message: 'Nao foi possivel limpar o cache de segmentos.',
            });
        },
        onFinish: () => {
            clearingSegmentCache.value = false;
        },
    });
}

// ─── App Cache ───────────────────────────────────────────
const showClearAppConfirm = ref(false);
const clearingAppCache = ref(false);

function clearAppCache() {
    clearingAppCache.value = true;
    router.post('/settings/system/clear-app-cache', {}, {
        preserveScroll: true,
        onSuccess: () => {
            showClearAppConfirm.value = false;
            addToast({
                type: 'success',
                title: 'Sucesso',
                message: 'Cache geral da aplicacao limpo com sucesso.',
            });
        },
        onError: () => {
            addToast({
                type: 'error',
                title: 'Erro',
                message: 'Nao foi possivel limpar o cache da aplicacao.',
            });
        },
        onFinish: () => {
            clearingAppCache.value = false;
        },
    });
}
</script>

<template>
    <Head title="Sistema - Manutencao" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Manutencao do Sistema"
        description="Gerencie caches e realize operacoes de manutencao na aplicacao."
    />

    <div class="mt-6 mx-auto max-w-4xl space-y-6">
        <!-- Resumo de Cache -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
                <FilmIcon class="mx-auto h-6 w-6 text-indigo-400 mb-2" />
                <p class="text-xl font-bold text-white">{{ cacheInfo.segment_cache.size_formatted }}</p>
                <p class="text-xs text-[var(--zy-text-muted)]">Cache de segmentos</p>
                <p class="text-xs text-[var(--zy-text-muted)]">{{ cacheInfo.segment_cache.files }} arquivos</p>
            </div>
            <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
                <ServerStackIcon class="mx-auto h-6 w-6 text-blue-400 mb-2" />
                <p class="text-xl font-bold text-white">{{ cacheInfo.app_cache.size_formatted }}</p>
                <p class="text-xs text-[var(--zy-text-muted)]">Cache da aplicacao</p>
                <p class="text-xs text-[var(--zy-text-muted)]">{{ cacheInfo.app_cache.files }} arquivos</p>
            </div>
            <div class="rounded-lg bg-[var(--zy-surface-tertiary)] p-4 text-center">
                <WrenchScrewdriverIcon class="mx-auto h-6 w-6 text-amber-400 mb-2" />
                <p class="text-xl font-bold text-white">{{ cacheInfo.total_size_formatted }}</p>
                <p class="text-xs text-[var(--zy-text-muted)]">Total em cache</p>
                <p class="text-xs text-[var(--zy-text-muted)]">{{ cacheInfo.views_cache.files }} views compiladas</p>
            </div>
        </div>

        <!-- Cache de Segmentos de Video -->
        <ZyCard>
            <template #header>
                <div class="flex items-center gap-2">
                    <FilmIcon class="h-5 w-5 text-indigo-400" />
                    <h3 class="text-lg font-semibold text-white">Cache de Segmentos de Video</h3>
                </div>
            </template>

            <div class="space-y-4">
                <p class="text-sm text-[var(--zy-text-muted)]">
                    Quando voce reproduz gravacoes armazenadas em storage remoto (MinIO, S3), os segmentos
                    sao baixados temporariamente para o disco local em
                    <code class="rounded bg-slate-700 px-1.5 py-0.5 text-xs text-slate-300">storage/app/segment_cache/</code>.
                    Com o tempo, esses arquivos podem acumular e ocupar espaco significativo.
                </p>

                <div class="flex items-center justify-between rounded-lg bg-slate-800/50 p-4">
                    <div>
                        <p class="font-medium text-white">
                            {{ cacheInfo.segment_cache.size_formatted }} em cache
                        </p>
                        <p class="text-sm text-slate-400">
                            {{ cacheInfo.segment_cache.files }} arquivos de video em cache local
                        </p>
                    </div>
                    <ZyButton
                        variant="danger"
                        size="sm"
                        :disabled="cacheInfo.segment_cache.files === 0"
                        @click="showClearSegmentConfirm = true"
                    >
                        <TrashIcon class="mr-1 h-4 w-4" />
                        Limpar Cache
                    </ZyButton>
                </div>

                <ZyAlert variant="info">
                    Limpar este cache nao afeta as gravacoes originais. Os segmentos serao baixados
                    novamente automaticamente quando reproduzidos.
                </ZyAlert>
            </div>
        </ZyCard>

        <!-- Cache Geral da Aplicacao -->
        <ZyCard>
            <template #header>
                <div class="flex items-center gap-2">
                    <ServerStackIcon class="h-5 w-5 text-blue-400" />
                    <h3 class="text-lg font-semibold text-white">Cache Geral da Aplicacao</h3>
                </div>
            </template>

            <div class="space-y-4">
                <p class="text-sm text-[var(--zy-text-muted)]">
                    O Laravel utiliza diversos caches para otimizar a performance: configuracoes, rotas,
                    views compiladas e cache da aplicacao. Em caso de comportamentos inesperados apos
                    atualizacoes, limpar esses caches pode resolver o problema.
                </p>

                <div class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg bg-slate-800/30 p-3">
                        <div>
                            <p class="text-sm font-medium text-white">Cache da aplicacao</p>
                            <p class="text-xs text-slate-400">Dados em cache (queries, computacoes)</p>
                        </div>
                        <span class="text-sm text-slate-300">{{ cacheInfo.app_cache.size_formatted }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-800/30 p-3">
                        <div>
                            <p class="text-sm font-medium text-white">Views compiladas</p>
                            <p class="text-xs text-slate-400">Templates Blade pre-compilados</p>
                        </div>
                        <span class="text-sm text-slate-300">{{ cacheInfo.views_cache.size_formatted }} ({{ cacheInfo.views_cache.files }} arquivos)</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-slate-800/30 p-3">
                        <div>
                            <p class="text-sm font-medium text-white">Configuracao e rotas</p>
                            <p class="text-xs text-slate-400">Cache de config e rotas do framework</p>
                        </div>
                        <span class="text-sm text-xs text-slate-400">Limpo sob demanda</span>
                    </div>
                </div>

                <div class="flex items-center justify-between rounded-lg bg-slate-800/50 p-4">
                    <div>
                        <p class="font-medium text-white">Limpar todo o cache da aplicacao</p>
                        <p class="text-sm text-slate-400">
                            Remove cache de config, rotas, views, eventos e aplicacao.
                        </p>
                    </div>
                    <ZyButton
                        variant="danger"
                        size="sm"
                        @click="showClearAppConfirm = true"
                    >
                        <TrashIcon class="mr-1 h-4 w-4" />
                        Limpar Cache
                    </ZyButton>
                </div>

                <ZyAlert variant="warning">
                    <ExclamationTriangleIcon class="mr-1 inline h-4 w-4" />
                    Apos limpar o cache, a primeira requisicao pode ser um pouco mais lenta enquanto os
                    caches sao reconstruidos automaticamente.
                </ZyAlert>
            </div>
        </ZyCard>
    </div>

    <!-- Confirm: Clear Segment Cache -->
    <ZyConfirmDialog
        :show="showClearSegmentConfirm"
        title="Limpar Cache de Segmentos"
        :message="`Deseja remover ${cacheInfo.segment_cache.files} arquivos de cache de video (${cacheInfo.segment_cache.size_formatted})? As gravacoes originais nao serao afetadas.`"
        confirm-text="Limpar"
        @confirm="clearSegmentCache"
        @cancel="showClearSegmentConfirm = false"
    />

    <!-- Confirm: Clear App Cache -->
    <ZyConfirmDialog
        :show="showClearAppConfirm"
        title="Limpar Cache Geral"
        message="Deseja limpar todo o cache da aplicacao (config, rotas, views, eventos, dados)? A primeira requisicao apos a limpeza pode ser mais lenta."
        confirm-text="Limpar"
        @confirm="clearAppCache"
        @cancel="showClearAppConfirm = false"
    />
</template>
