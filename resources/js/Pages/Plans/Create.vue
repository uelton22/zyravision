<script setup lang="ts">
/**
 * Plans Create — Formulário de criação de plano
 */

import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton,
    ZyInput, ZySelect, ZySwitch, ZyTextarea,
} from '@design';

defineOptions({ layout: AuthenticatedLayout });

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Planos', href: '/plans' },
    { label: 'Novo Plano' },
];

const form = useForm({
    name: '',
    description: '',
    price: 0,
    billing_period: 'monthly',
    is_active: true,
    is_featured: false,
    sort_order: 0,
    // Quotas
    max_cameras: 16,
    max_users: 5,
    max_storage_gb: 50,
    retention_days: 7,
    max_concurrent_streams: 4,
    max_resolution: '1080p',
    max_fps: 15,
    // Features
    has_ai_detection: false,
    has_ai_recognition: false,
    has_analytics: false,
    has_mobile_app: false,
    has_api_access: false,
    has_webrtc: false,
    has_ptz_control: false,
    has_custom_alerts: false,
    has_export: false,
    has_multi_tenant: false,
    has_sla: false,
    has_onpremise: false,
    has_priority_support: false,
    has_dedicated_support: false,
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
    form.post('/plans');
}
</script>

<template>
    <Head title="Novo Plano" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Novo Plano" description="Cadastre um novo plano na plataforma">
        <template #actions>
            <ZyButton variant="secondary" href="/plans">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" @click="submit">Salvar</ZyButton>
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
