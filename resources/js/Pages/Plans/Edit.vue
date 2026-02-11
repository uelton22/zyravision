<script setup lang="ts">
/**
 * Plans Edit — Formulário de edição de plano
 */

import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton,
    ZyInput, ZySelect, ZySwitch, ZyTextarea,
} from '@design';

defineOptions({ layout: AuthenticatedLayout });

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
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Planos', href: '/plans' },
    { label: props.plan.name },
];

const form = useForm({
    name: props.plan.name,
    description: props.plan.description || '',
    price: props.plan.price,
    billing_period: props.plan.billing_period,
    is_active: props.plan.is_active,
    is_featured: props.plan.is_featured,
    sort_order: props.plan.sort_order,
    // Quotas
    max_cameras: props.plan.max_cameras,
    max_users: props.plan.max_users,
    max_storage_gb: props.plan.max_storage_gb,
    retention_days: props.plan.retention_days,
    max_concurrent_streams: props.plan.max_concurrent_streams,
    max_resolution: props.plan.max_resolution,
    max_fps: props.plan.max_fps,
    // Features
    has_ai_detection: props.plan.has_ai_detection,
    has_ai_recognition: props.plan.has_ai_recognition,
    has_analytics: props.plan.has_analytics,
    has_mobile_app: props.plan.has_mobile_app,
    has_api_access: props.plan.has_api_access,
    has_webrtc: props.plan.has_webrtc,
    has_ptz_control: props.plan.has_ptz_control,
    has_custom_alerts: props.plan.has_custom_alerts,
    has_export: props.plan.has_export,
    has_multi_tenant: props.plan.has_multi_tenant,
    has_sla: props.plan.has_sla,
    has_onpremise: props.plan.has_onpremise,
    has_priority_support: props.plan.has_priority_support,
    has_dedicated_support: props.plan.has_dedicated_support,
});

const billingOptions = [
    { value: 'monthly', label: 'Mensal' },
    { value: 'yearly', label: 'Anual' },
    { value: 'custom', label: 'Personalizado' },
];

const resolutionOptions = [
    { value: '720p', label: '720p (HD)' },
    { value: '1080p', label: '1080p (Full HD)' },
    { value: '4k', label: '4K (Ultra HD)' },
];

function submit() {
    form.put(`/plans/${props.plan.id}`);
}
</script>

<template>
    <Head :title="`Editar — ${plan.name}`" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="`Editar: ${plan.name}`" description="Atualize os dados do plano">
        <template #actions>
            <ZyButton variant="secondary" href="/plans">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" @click="submit">Salvar Alterações</ZyButton>
        </template>
    </ZyPageHeader>

    <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
        <!-- Informações Básicas -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Informações Básicas</h3>
            </template>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZyInput v-model="form.name" label="Nome do Plano" :error="form.errors.name" required placeholder="Ex: Professional" />
                    <div class="grid grid-cols-2 gap-4">
                        <ZyInput v-model="form.price" type="number" label="Preço (R$)" :error="form.errors.price" required step="0.01" min="0" />
                        <ZySelect v-model="form.billing_period" :options="billingOptions" label="Período" :error="form.errors.billing_period" required />
                    </div>
                </div>
                <ZyTextarea v-model="form.description" label="Descrição" :error="form.errors.description" placeholder="Descrição do plano..." rows="3" />
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZyInput v-model="form.sort_order" type="number" label="Ordem de exibição" :error="form.errors.sort_order" min="0" />
                    <div class="flex items-end gap-6 pb-1">
                        <ZySwitch v-model="form.is_active" label="Ativo" />
                        <ZySwitch v-model="form.is_featured" label="Destaque" />
                    </div>
                </div>
            </div>
        </ZyCard>

        <!-- Limites e Quotas -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Limites e Quotas</h3>
            </template>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <ZyInput v-model="form.max_cameras" type="number" label="Máx. Câmeras" :error="form.errors.max_cameras" required min="1" />
                <ZyInput v-model="form.max_users" type="number" label="Máx. Usuários" :error="form.errors.max_users" required min="1" />
                <ZyInput v-model="form.max_storage_gb" type="number" label="Storage (GB)" :error="form.errors.max_storage_gb" required min="1" />
                <ZyInput v-model="form.retention_days" type="number" label="Retenção (dias)" :error="form.errors.retention_days" required min="1" />
                <ZyInput v-model="form.max_concurrent_streams" type="number" label="Streams Simultâneos" :error="form.errors.max_concurrent_streams" required min="1" />
                <ZyInput v-model="form.max_fps" type="number" label="FPS Máximo" :error="form.errors.max_fps" required min="1" max="120" />
                <ZySelect v-model="form.max_resolution" :options="resolutionOptions" label="Resolução Máxima" :error="form.errors.max_resolution" required />
            </div>
        </ZyCard>

        <!-- Features -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Funcionalidades</h3>
            </template>
            <div class="space-y-6">
                <!-- IA -->
                <div>
                    <p class="text-body-sm font-medium text-dark-500 mb-3">Inteligência Artificial</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <ZySwitch v-model="form.has_ai_detection" label="Detecção IA" description="Detecção de movimento e objetos por IA" />
                        <ZySwitch v-model="form.has_ai_recognition" label="Reconhecimento Facial" description="Identificação de pessoas por IA" />
                    </div>
                </div>
                <!-- Vídeo -->
                <div>
                    <p class="text-body-sm font-medium text-dark-500 mb-3">Vídeo e Streaming</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <ZySwitch v-model="form.has_webrtc" label="WebRTC" description="Streaming de baixa latência" />
                        <ZySwitch v-model="form.has_ptz_control" label="Controle PTZ" description="Pan, Tilt & Zoom remoto" />
                        <ZySwitch v-model="form.has_mobile_app" label="App Mobile" description="Acesso via aplicativo mobile" />
                        <ZySwitch v-model="form.has_api_access" label="Acesso API" description="API REST para integrações" />
                    </div>
                </div>
                <!-- Dados e Alertas -->
                <div>
                    <p class="text-body-sm font-medium text-dark-500 mb-3">Dados e Alertas</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <ZySwitch v-model="form.has_analytics" label="Analytics Avançado" description="Relatórios e dashboards avançados" />
                        <ZySwitch v-model="form.has_custom_alerts" label="Alertas Personalizados" description="Configuração de alertas customizados" />
                        <ZySwitch v-model="form.has_export" label="Exportação de Dados" description="Exportar gravações e relatórios" />
                        <ZySwitch v-model="form.has_multi_tenant" label="Multi-Tenant" description="Gestão de múltiplas empresas" />
                    </div>
                </div>
                <!-- Suporte e Deploy -->
                <div>
                    <p class="text-body-sm font-medium text-dark-500 mb-3">Suporte e Deploy</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <ZySwitch v-model="form.has_sla" label="SLA Garantido" description="Acordo de nível de serviço" />
                        <ZySwitch v-model="form.has_onpremise" label="Deploy On-Premise" description="Instalação local no cliente" />
                        <ZySwitch v-model="form.has_priority_support" label="Suporte Prioritário" description="Fila prioritária de atendimento" />
                        <ZySwitch v-model="form.has_dedicated_support" label="Suporte Dedicado 24/7" description="Equipe dedicada de suporte" />
                    </div>
                </div>
            </div>
        </ZyCard>
    </form>
</template>
