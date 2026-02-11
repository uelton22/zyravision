<script setup lang="ts">
/**
 * Cameras Edit — Formulario de edicao de camera
 */

import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZySelect, ZySwitch, ZyTextarea } from '@design';
import type { Camera } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface DeviceOption {
    id: string;
    name: string;
    type: string;
    manufacturer: string;
    total_channels: number;
}

interface Props {
    camera: Camera;
    devices: DeviceOption[];
    manufacturers: { value: string; label: string; ptz?: boolean; audio?: boolean }[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Cameras', href: '/cameras' },
    { label: props.camera.name },
    { label: 'Editar' },
];

const mode = ref<'direct' | 'device'>(props.camera.device_id ? 'device' : 'direct');

const form = useForm({
    name: props.camera.name,
    device_id: props.camera.device_id || '',
    channel_number: props.camera.channel_number || 1,
    channel_type: props.camera.channel_type,
    manufacturer: props.camera.manufacturer || '',
    model: props.camera.model || '',
    address_type: props.camera.address_type || 'ipv4',
    address: props.camera.address || '',
    port: props.camera.port || 554,
    secondary_address: props.camera.secondary_address || '',
    transport: props.camera.transport || 'tcp',
    username: props.camera.username || '',
    password: '',
    stream_type: props.camera.stream_type || 'main',
    rtsp_path: props.camera.rtsp_path || '',
    has_ptz: props.camera.has_ptz,
    has_audio: props.camera.has_audio,
    is_active: props.camera.is_active,
    location: props.camera.location || '',
    description: props.camera.description || '',
    ddns_provider: props.camera.ddns_provider || '',
    ddns_hostname: props.camera.ddns_hostname || '',
    ipv6_preferred: props.camera.ipv6_preferred,
});

// RTSP path templates by manufacturer
const rtspPathTemplates: Record<string, string> = {
    intelbras: '/cam/realmonitor?channel={ch}&subtype=0',
    dahua: '/cam/realmonitor?channel={ch}&subtype=0',
    tecvoz: '/cam/realmonitor?channel={ch}&subtype=0',
    giga: '/cam/realmonitor?channel={ch}&subtype=0',
    hikvision: '/Streaming/Channels/{ch}01',
};

// Computed RTSP URL preview
const rtspPreview = computed(() => {
    if (mode.value !== 'direct' || !form.address) return '';
    const auth = form.username ? `${form.username}:****@` : '';
    const host = form.address;
    const port = form.port || 554;
    let path = form.rtsp_path;

    if (!path) {
        // Auto-generate from manufacturer
        const template = rtspPathTemplates[form.manufacturer] || `/ch{ch}/0`;
        path = template.replace('{ch}', String(form.channel_number || 1));
    }

    return `rtsp://${auth}${host}:${port}${path}`;
});

const channelTypeOptions = [
    { value: 'direct', label: 'Direta' },
    { value: 'analog', label: 'Analogica' },
    { value: 'ip', label: 'IP' },
];

const addressTypeOptions = [
    { value: 'ipv4', label: 'IPv4' },
    { value: 'ipv6', label: 'IPv6' },
    { value: 'ddns', label: 'DDNS' },
    { value: 'hostname', label: 'Hostname' },
];

const transportOptions = [
    { value: 'tcp', label: 'TCP' },
    { value: 'udp', label: 'UDP' },
    { value: 'auto', label: 'Auto' },
];

const streamTypeOptions = [
    { value: 'main', label: 'Principal (Main)' },
    { value: 'sub', label: 'Secundario (Sub)' },
    { value: 'mobile', label: 'Mobile' },
];

const manufacturerOptions = props.manufacturers.map((m) => ({
    value: m.value,
    label: m.label,
}));

const deviceOptions = props.devices.map((d) => ({
    value: d.id,
    label: `${d.name} (${d.manufacturer} - ${d.total_channels} canais)`,
}));

const selectedDevice = computed(() => {
    return props.devices.find(d => d.id === form.device_id) || null;
});

const showDdnsFields = computed(() => form.address_type === 'ddns');

watch(mode, (newMode) => {
    if (newMode === 'direct') {
        form.device_id = '';
        form.channel_type = 'direct';
    } else {
        form.channel_type = 'analog';
    }
});

// When manufacturer changes, pre-fill capabilities from profile (only on actual change, not initial load)
watch(() => form.manufacturer, (newManufacturer, oldManufacturer) => {
    if (mode.value === 'direct' && newManufacturer && oldManufacturer !== undefined && oldManufacturer !== newManufacturer) {
        const mfr = props.manufacturers.find(m => m.value === newManufacturer);
        if (mfr) {
            form.has_ptz = mfr.ptz ?? false;
            form.has_audio = mfr.audio ?? false;
        }
    }
});

function submit() {
    if (mode.value === 'device') {
        form.address = '';
        form.manufacturer = '';
    } else {
        form.device_id = '';
        form.channel_number = 1;
    }

    form.put(`/cameras/${props.camera.id}`);
}
</script>

<template>
    <Head :title="`Editar ${camera.name}`" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="`Editar ${camera.name}`" description="Atualize as informacoes da camera">
        <template #actions>
            <ZyButton variant="secondary" href="/cameras">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" @click="submit">Salvar</ZyButton>
        </template>
    </ZyPageHeader>

    <div class="space-y-6 max-w-4xl">
        <!-- Mode & Name -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Identificacao</h3>
            </template>
            <div class="space-y-4">
                <ZyInput v-model="form.name" label="Nome da camera" :error="form.errors.name" required />
            </div>
        </ZyCard>

        <!-- Device Channel Mode -->
        <ZyCard v-if="mode === 'device'">
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Dispositivo e Canal</h3>
            </template>
            <div class="space-y-4">
                <ZySelect
                    v-model="form.device_id"
                    :options="deviceOptions"
                    label="Dispositivo"
                    :error="form.errors.device_id"
                    required
                />
                <div v-if="selectedDevice" class="grid sm:grid-cols-2 gap-4">
                    <ZyInput v-model.number="form.channel_number" type="number" label="Numero do canal" :error="form.errors.channel_number" required />
                    <ZySelect v-model="form.channel_type" :options="channelTypeOptions" label="Tipo de canal" :error="form.errors.channel_type" required />
                </div>
            </div>
        </ZyCard>

        <!-- Direct Camera Connection -->
        <ZyCard v-if="mode === 'direct'">
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Conexao</h3>
            </template>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZySelect v-model="form.manufacturer" :options="manufacturerOptions" label="Fabricante" :error="form.errors.manufacturer" />
                    <ZyInput v-model="form.model" label="Modelo" :error="form.errors.model" />
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZySelect v-model="form.address_type" :options="addressTypeOptions" label="Tipo de endereco" :error="form.errors.address_type" required />
                    <ZyInput v-model="form.address" label="Endereco (IP/Hostname)" :error="form.errors.address" required />
                </div>
                <div class="grid sm:grid-cols-3 gap-4">
                    <ZyInput v-model.number="form.port" type="number" label="Porta RTSP" :error="form.errors.port" />
                    <ZySelect v-model="form.transport" :options="transportOptions" label="Transporte" :error="form.errors.transport" />
                    <ZyInput v-model="form.secondary_address" label="Endereco secundario" :error="form.errors.secondary_address" />
                </div>

                <!-- RTSP Path Override -->
                <div class="space-y-2">
                    <ZyInput
                        v-model="form.rtsp_path"
                        label="RTSP Path (caminho)"
                        :placeholder="rtspPathTemplates[form.manufacturer] ? rtspPathTemplates[form.manufacturer].replace('{ch}', String(form.channel_number || 1)) : '/Streaming/Channels/101'"
                        :error="form.errors.rtsp_path"
                        description="Deixe vazio para usar o padrao do fabricante. Preencha para usar um caminho customizado."
                    />
                    <!-- RTSP URL Preview -->
                    <div v-if="rtspPreview" class="bg-dark-100 dark:bg-dark-800 rounded-lg px-3 py-2">
                        <p class="text-[11px] text-dark-500 mb-1 font-medium">Preview da URL RTSP:</p>
                        <code class="text-body-sm font-mono text-primary-400 break-all">{{ rtspPreview }}</code>
                    </div>
                </div>

                <template v-if="showDdnsFields">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <ZyInput v-model="form.ddns_provider" label="Provedor DDNS" :error="form.errors.ddns_provider" />
                        <ZyInput v-model="form.ddns_hostname" label="Hostname DDNS" :error="form.errors.ddns_hostname" />
                    </div>
                    <ZySwitch v-model="form.ipv6_preferred" label="Preferir IPv6" />
                </template>
            </div>
        </ZyCard>

        <!-- Authentication -->
        <ZyCard v-if="mode === 'direct'">
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Autenticacao</h3>
            </template>
            <div class="grid sm:grid-cols-2 gap-4 max-w-lg">
                <ZyInput v-model="form.username" label="Usuario" :error="form.errors.username" />
                <ZyInput v-model="form.password" type="password" label="Senha" placeholder="Deixe vazio para manter a atual" :error="form.errors.password" />
            </div>
        </ZyCard>

        <!-- Stream & Features -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Stream e Recursos</h3>
            </template>
            <div class="space-y-4">
                <ZySelect v-model="form.stream_type" :options="streamTypeOptions" label="Tipo de stream" :error="form.errors.stream_type" class="max-w-xs" />
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZyInput v-model="form.location" label="Localizacao" :error="form.errors.location" />
                    <ZyTextarea v-model="form.description" label="Descricao" :error="form.errors.description" rows="2" />
                </div>
                <div class="flex flex-col gap-3">
                    <ZySwitch v-model="form.has_ptz" label="Suporte PTZ" description="Camera possui Pan-Tilt-Zoom" />
                    <ZySwitch v-model="form.has_audio" label="Suporte audio" description="Camera possui microfone/audio" />
                    <ZySwitch v-model="form.is_active" label="Ativa" description="Camera habilitada para monitoramento" />
                </div>
            </div>
        </ZyCard>
    </div>
</template>
