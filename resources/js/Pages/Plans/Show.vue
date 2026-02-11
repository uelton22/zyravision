<script setup lang="ts">
/**
 * Plans Show — Detalhes completos do plano
 */

import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge,
    ZyConfirmDialog, ZyTable,
} from '@design';
import { PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
import {
    CheckCircleIcon, XCircleIcon,
} from '@heroicons/vue/24/solid';

defineOptions({ layout: AuthenticatedLayout });

interface SubscriptionRow {
    id: string;
    tenant_name: string;
    tenant_id: string;
    status: string;
    started_at: string;
    current_period_end: string;
}

interface PlanData {
    id: string;
    name: string;
    slug: string;
    description: string | null;
    price: number;
    billing_period: string;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    max_cameras: number;
    max_users: number;
    max_storage_gb: number;
    retention_days: number;
    max_concurrent_streams: number;
    max_resolution: string;
    max_fps: number;
    subscriptions_count: number;
    has_ai_detection: boolean;
    has_ai_recognition: boolean;
    has_analytics: boolean;
    has_mobile_app: boolean;
    has_api_access: boolean;
    has_webrtc: boolean;
    has_ptz_control: boolean;
    has_custom_alerts: boolean;
    has_export: boolean;
    has_multi_tenant: boolean;
    has_sla: boolean;
    has_onpremise: boolean;
    has_priority_support: boolean;
    has_dedicated_support: boolean;
}

interface Props {
    plan: PlanData;
    features: string[];
    formattedPrice: string;
    activeSubscriptions: SubscriptionRow[];
}

const props = defineProps<Props>();

const activeTab = ref<'info' | 'features' | 'subscriptions'>('info');
const showDeleteConfirm = ref(false);

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Planos', href: '/plans' },
    { label: props.plan.name },
];

const tabs = [
    { key: 'info', label: 'Informações' },
    { key: 'features', label: 'Funcionalidades' },
    { key: 'subscriptions', label: `Assinaturas (${props.activeSubscriptions?.length || 0})` },
];

const subscriptionColumns = [
    { key: 'tenant_name', label: 'Tenant' },
    { key: 'status', label: 'Status' },
    { key: 'started_at', label: 'Início' },
    { key: 'current_period_end', label: 'Vencimento' },
];

function formatBillingPeriod(period: string): string {
    const labels: Record<string, string> = {
        monthly: 'Mensal',
        yearly: 'Anual',
        custom: 'Personalizado',
    };
    return labels[period] || period;
}

const featureGroups = [
    {
        title: 'Inteligência Artificial',
        items: [
            { key: 'has_ai_detection', label: 'Detecção IA' },
            { key: 'has_ai_recognition', label: 'Reconhecimento Facial' },
        ],
    },
    {
        title: 'Vídeo e Streaming',
        items: [
            { key: 'has_webrtc', label: 'WebRTC (baixa latência)' },
            { key: 'has_ptz_control', label: 'Controle PTZ' },
            { key: 'has_mobile_app', label: 'App Mobile' },
            { key: 'has_api_access', label: 'Acesso API' },
        ],
    },
    {
        title: 'Dados e Alertas',
        items: [
            { key: 'has_analytics', label: 'Analytics Avançado' },
            { key: 'has_custom_alerts', label: 'Alertas Personalizados' },
            { key: 'has_export', label: 'Exportação de Dados' },
            { key: 'has_multi_tenant', label: 'Multi-Tenant' },
        ],
    },
    {
        title: 'Suporte e Deploy',
        items: [
            { key: 'has_sla', label: 'SLA Garantido' },
            { key: 'has_onpremise', label: 'Deploy On-Premise' },
            { key: 'has_priority_support', label: 'Suporte Prioritário' },
            { key: 'has_dedicated_support', label: 'Suporte Dedicado 24/7' },
        ],
    },
];

function deletePlan() {
    router.delete(`/plans/${props.plan.id}`, {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}
</script>

<template>
    <Head :title="plan.name" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="plan.name" :description="`Slug: ${plan.slug}`">
        <template #actions>
            <ZyBadge :variant="plan.is_active ? 'success' : 'danger'" dot class="mr-2">
                {{ plan.is_active ? 'Ativo' : 'Inativo' }}
            </ZyBadge>
            <ZyBadge v-if="plan.is_featured" variant="warning" class="mr-2">Destaque</ZyBadge>
            <ZyButton variant="secondary" :href="`/plans/${plan.id}/edit`">
                <PencilIcon class="h-4 w-4" />
                Editar
            </ZyButton>
            <ZyButton variant="danger" @click="showDeleteConfirm = true">
                <TrashIcon class="h-4 w-4" />
                Excluir
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Tabs -->
    <div class="flex border-b border-dark-200 dark:border-dark-700 mb-6">
        <button
            v-for="tab in tabs"
            :key="tab.key"
            :class="[
                'px-4 py-3 text-body-sm font-medium border-b-2 transition-colors -mb-px',
                activeTab === tab.key
                    ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                    : 'border-transparent text-dark-500 hover:text-dark-700 dark:hover:text-dark-300',
            ]"
            @click="activeTab = tab.key as any"
        >
            {{ tab.label }}
        </button>
    </div>

    <!-- Tab: Informações -->
    <div v-if="activeTab === 'info'" class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Dados do Plano</h3>
                </template>
                <div class="space-y-6">
                    <!-- Preço -->
                    <div class="flex items-baseline gap-2">
                        <span class="text-heading font-bold text-[var(--zy-text-primary)]">{{ formattedPrice }}</span>
                        <span v-if="plan.price > 0" class="text-body-sm text-dark-500">{{ formatBillingPeriod(plan.billing_period) }}</span>
                    </div>

                    <p v-if="plan.description" class="text-body text-dark-600 dark:text-dark-300">{{ plan.description }}</p>

                    <div class="grid sm:grid-cols-2 gap-4 pt-2 border-t border-dark-200 dark:border-dark-700">
                        <div>
                            <p class="text-caption font-medium text-dark-500 mb-1">Nome</p>
                            <p class="text-body text-[var(--zy-text-primary)]">{{ plan.name }}</p>
                        </div>
                        <div>
                            <p class="text-caption font-medium text-dark-500 mb-1">Slug</p>
                            <p class="text-body text-[var(--zy-text-primary)]">{{ plan.slug }}</p>
                        </div>
                        <div>
                            <p class="text-caption font-medium text-dark-500 mb-1">Período de Cobrança</p>
                            <p class="text-body text-[var(--zy-text-primary)]">{{ formatBillingPeriod(plan.billing_period) }}</p>
                        </div>
                        <div>
                            <p class="text-caption font-medium text-dark-500 mb-1">Ordem de Exibição</p>
                            <p class="text-body text-[var(--zy-text-primary)]">{{ plan.sort_order }}</p>
                        </div>
                        <div>
                            <p class="text-caption font-medium text-dark-500 mb-1">Assinaturas Ativas</p>
                            <p class="text-body font-semibold text-[var(--zy-text-primary)]">{{ plan.subscriptions_count }}</p>
                        </div>
                    </div>
                </div>
            </ZyCard>
        </div>

        <!-- Limites -->
        <div>
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Limites</h3>
                </template>
                <div class="space-y-3 text-body-sm">
                    <div class="flex justify-between">
                        <span class="text-dark-500">Câmeras</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">{{ plan.max_cameras }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-500">Usuários</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">{{ plan.max_users }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-500">Storage</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">{{ plan.max_storage_gb }}GB</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-500">Retenção</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">{{ plan.retention_days }} dias</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-500">Streams</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">{{ plan.max_concurrent_streams }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-500">Resolução</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">{{ plan.max_resolution }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-500">FPS Máximo</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">{{ plan.max_fps }}</span>
                    </div>
                </div>
            </ZyCard>
        </div>
    </div>

    <!-- Tab: Funcionalidades -->
    <div v-if="activeTab === 'features'" class="grid lg:grid-cols-2 gap-6">
        <ZyCard v-for="group in featureGroups" :key="group.title">
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">{{ group.title }}</h3>
            </template>
            <div class="space-y-3">
                <div
                    v-for="item in group.items"
                    :key="item.key"
                    class="flex items-center justify-between py-2 border-b border-dark-100 dark:border-dark-800 last:border-0"
                >
                    <span class="text-body-sm text-[var(--zy-text-primary)]">{{ item.label }}</span>
                    <CheckCircleIcon v-if="(plan as any)[item.key]" class="h-5 w-5 text-success-500" />
                    <XCircleIcon v-else class="h-5 w-5 text-dark-300 dark:text-dark-600" />
                </div>
            </div>
        </ZyCard>
    </div>

    <!-- Tab: Assinaturas -->
    <div v-if="activeTab === 'subscriptions'">
        <p class="text-body-sm text-dark-500 mb-4">
            {{ activeSubscriptions?.length || 0 }} assinatura(s) ativa(s) neste plano
        </p>

        <ZyTable
            v-if="activeSubscriptions && activeSubscriptions.length > 0"
            :columns="subscriptionColumns"
            :data="(activeSubscriptions || []) as any[]"
            hoverable
        >
            <template #cell-tenant_name="{ row }">
                <ZyButton variant="ghost" size="sm" :href="`/tenants/${(row as any).tenant_id}`">
                    {{ (row as any).tenant_name }}
                </ZyButton>
            </template>

            <template #cell-status="{ row }">
                <ZyBadge :variant="(row as any).status === 'active' ? 'success' : 'warning'">
                    {{ (row as any).status === 'active' ? 'Ativo' : (row as any).status }}
                </ZyBadge>
            </template>
        </ZyTable>

        <div v-else class="text-center py-12">
            <p class="text-body text-dark-500">Nenhuma assinatura ativa para este plano.</p>
        </div>
    </div>

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Plano"
        :message="`Tem certeza que deseja excluir o plano '${plan.name}'? Esta ação não pode ser desfeita.`"
        confirm-text="Excluir"
        @confirm="deletePlan"
        @cancel="showDeleteConfirm = false"
    />
</template>
