<script setup lang="ts">
/**
 * Cameras Create — Formulario de cadastro de camera
 * Suporta modo "Camera Direta" e "Canal de Dispositivo"
 */

import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZySelect, ZySwitch, ZyAlert, ZyTextarea } from '@design';

defineOptions({ layout: AuthenticatedLayout });

interface DeviceOption {
    id: string;
    name: string;
    type: string;
    manufacturer: string;
    total_channels: number;
    address: string;
    port: number;
}

interface Props {
    manufacturers: { value: string; label: string; ptz?: boolean; audio?: boolean }[];
    devices: DeviceOption[];
    tenants: { id: string; name: string }[];
    isSuperAdmin: boolean;
    defaultTenantId: string | null;
    defaultDeviceId: string | null;
    quotaReached: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    manufacturers: () => [],
    devices: () => [],
    tenants: () => [],
    isSuperAdmin: false,
    defaultTenantId: null,
    defaultDeviceId: null,
    quotaReached: false,
});

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Cameras', href: '/cameras' },
    { label: 'Nova Camera' },
];

const mode = ref<'direct' | 'device'>(props.defaultDeviceId ? 'device' : 'direct');

const form = useForm({
    name: '',
    device_id: props.defaultDeviceId || '',
    channel_number: 1,
    channel_type: props.defaultDeviceId ? 'analog' : 'direct',
    manufacturer: '',
    model: '',
    address_type: 'ipv4',
    address: '',
    port: 554,
    secondary_address: '',
    transport: 'tcp',
    username: '',
    password: '',
    stream_type: 'main',
    rtsp_path: '',
    has_ptz: false,
    has_audio: false,
    is_active: true,
    location: '',
    description: '',
    ddns_provider: '',
    ddns_hostname: '',
    ipv6_preferred: false,
    tenant_id: props.defaultTenantId || '',
});

const modeOptions = [
    { value: 'direct', label: 'Camera Direta' },
    { value: 'device', label: 'Canal de Dispositivo' },
];

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

const tenantOptions = props.tenants.map((t) => ({
    value: t.id,
    label: t.name,
}));

const selectedDevice = computed(() => {
    return props.devices.find(d => d.id === form.device_id) || null;
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
        const template = rtspPathTemplates[form.manufacturer] || `/ch{ch}/0`;
        path = template.replace('{ch}', String(form.channel_number || 1));
    }

    return `rtsp://${auth}${host}:${port}${path}`;
});

const showDdnsFields = computed(() => form.address_type === 'ddns');

// When mode changes, adjust form fields
watch(mode, (newMode) => {
    if (newMode === 'direct') {
        form.device_id = '';
        form.channel_number = 1;
        form.channel_type = 'direct';
    } else {
        form.channel_type = 'analog';
        form.address = '';
        form.address_type = 'ipv4';
    }
});

// When device changes, pre-fill device info
watch(() => form.device_id, (deviceId) => {
    const device = props.devices.find(d => d.id === deviceId);
    if (device) {
        form.channel_type = device.type === 'nvr' ? 'ip' : 'analog';
    }
});

// When manufacturer changes in direct mode, pre-fill capabilities from profile
watch(() => form.manufacturer, (newManufacturer) => {
    if (mode.value === 'direct' && newManufacturer) {
        const mfr = props.manufacturers.find(m => m.value === newManufacturer);
        if (mfr) {
            form.has_ptz = mfr.ptz ?? false;
            form.has_audio = mfr.audio ?? false;
        }
    }
});

function submit() {
    // Clear irrelevant fields based on mode
    if (mode.value === 'device') {
        form.address = '';
        form.manufacturer = '';
    } else {
        form.device_id = '';
        form.channel_number = 1;
    }

    form.post('/cameras');
}
</script>

<template>
    <Head title="Nova Camera" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Nova Camera" description="Cadastre uma camera direta ou um canal de dispositivo">
        <template #actions>
            <ZyButton variant="secondary" href="/cameras">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" :disabled="quotaReached" @click="submit">Salvar</ZyButton>
        </template>
    </ZyPageHeader>

    <ZyAlert v-if="quotaReached" variant="warning" class="mb-4">
        O limite de cameras deste tenant foi atingido. Para adicionar mais cameras, faca upgrade do plano.
    </ZyAlert>

    <div class="space-y-6 max-w-4xl">
        <!-- Tenant (super-admin only) -->
        <ZyCard v-if="isSuperAdmin && tenantOptions.length > 0">
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Empresa</h3>
            </template>
            <ZySelect
                v-model="form.tenant_id"
                :options="tenantOptions"
                label="Empresa (Tenant)"
                :error="form.errors.tenant_id"
                required
            />
        </ZyCard>

        <!-- Mode Selection -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Tipo de Camera</h3>
            </template>
            <div class="space-y-4">
                <ZySelect
                    v-model="mode"
                    :options="modeOptions"
                    label="Modo de conexao"
                />

                <ZyInput v-model="form.name" label="Nome da camera" placeholder="Ex: Entrada Principal" :error="form.errors.name" required />
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
                <div v-if="selectedDevice" class="grid sm:grid-cols-3 gap-4">
                    <ZyInput v-model.number="form.channel_number" type="number" label="Numero do canal" :min="1" :max="selectedDevice.total_channels" :error="form.errors.channel_number" required />
                    <ZySelect v-model="form.channel_type" :options="channelTypeOptions" label="Tipo de canal" :error="form.errors.channel_type" required />
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Endereco do dispositivo</p>
                        <p class="text-body text-[var(--zy-text-primary)] font-mono mt-2">{{ selectedDevice.address }}:{{ selectedDevice.port }}</p>
                    </div>
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
                    <ZyInput v-model="form.address" label="Endereco (IP/Hostname)" placeholder="Ex: 192.168.1.100" :error="form.errors.address" required />
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
                    <div v-if="rtspPreview" class="bg-dark-100 dark:bg-dark-800 rounded-lg px-3 py-2">
                        <p class="text-[11px] text-dark-500 mb-1 font-medium">Preview da URL RTSP:</p>
                        <code class="text-body-sm font-mono text-primary-400 break-all">{{ rtspPreview }}</code>
                    </div>
                </div>

                <!-- DDNS -->
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
                <ZyInput v-model="form.username" label="Usuario" placeholder="admin" :error="form.errors.username" />
                <ZyInput v-model="form.password" type="password" label="Senha" :error="form.errors.password" />
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
                    <ZyInput v-model="form.location" label="Localizacao" placeholder="Ex: Portaria - Entrada" :error="form.errors.location" />
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
