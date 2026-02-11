<script setup lang="ts">
/**
 * Devices Edit — Formulario de edicao de dispositivo
 */

import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZySelect, ZySwitch } from '@design';
import type { Device } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface Props {
    device: Device;
    manufacturers: { value: string; label: string }[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Dispositivos', href: '/devices' },
    { label: props.device.name },
    { label: 'Editar' },
];

const form = useForm({
    name: props.device.name,
    type: props.device.type,
    manufacturer: props.device.manufacturer,
    model: props.device.model || '',
    firmware_version: props.device.firmware_version || '',
    address_type: props.device.address_type,
    address: props.device.address,
    port: props.device.port,
    http_port: props.device.http_port,
    rtmp_port: props.device.rtmp_port,
    secondary_address: props.device.secondary_address || '',
    transport: props.device.transport,
    username: props.device.username || '',
    password: '',
    total_channels: props.device.total_channels,
    onvif_supported: props.device.onvif_supported,
    onvif_profile: props.device.onvif_profile || '',
    is_active: props.device.is_active,
    location: props.device.location || '',
    serial_number: props.device.serial_number || '',
    ddns_provider: props.device.ddns_provider || '',
    ddns_hostname: props.device.ddns_hostname || '',
    ipv6_preferred: props.device.ipv6_preferred,
});

const typeOptions = [
    { value: 'dvr', label: 'DVR' },
    { value: 'nvr', label: 'NVR' },
    { value: 'encoder', label: 'Encoder' },
    { value: 'camera', label: 'Camera IP' },
    { value: 'hybrid', label: 'Hibrido' },
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

const manufacturerOptions = props.manufacturers.map((m) => ({
    value: m.value,
    label: m.label,
}));

const showDdnsFields = computed(() => form.address_type === 'ddns');

function submit() {
    form.put(`/devices/${props.device.id}`);
}
</script>

<template>
    <Head :title="`Editar ${device.name}`" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="`Editar ${device.name}`" description="Atualize as informacoes do dispositivo">
        <template #actions>
            <ZyButton variant="secondary" href="/devices">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" @click="submit">Salvar</ZyButton>
        </template>
    </ZyPageHeader>

    <div class="space-y-6 max-w-4xl">
        <!-- Identification -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Identificacao</h3>
            </template>
            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZyInput v-model="form.name" label="Nome do dispositivo" :error="form.errors.name" required />
                    <ZySelect v-model="form.type" :options="typeOptions" label="Tipo" :error="form.errors.type" required />
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZySelect v-model="form.manufacturer" :options="manufacturerOptions" label="Fabricante" :error="form.errors.manufacturer" required />
                    <ZyInput v-model="form.model" label="Modelo" :error="form.errors.model" />
                </div>
                <ZyInput v-model="form.firmware_version" label="Versao do Firmware" :error="form.errors.firmware_version" class="max-w-sm" />
            </form>
        </ZyCard>

        <!-- Connection -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Conexao</h3>
            </template>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZySelect v-model="form.address_type" :options="addressTypeOptions" label="Tipo de endereco" :error="form.errors.address_type" required />
                    <ZyInput v-model="form.address" label="Endereco (IP/Hostname)" :error="form.errors.address" required />
                </div>
                <div class="grid sm:grid-cols-4 gap-4">
                    <ZyInput v-model.number="form.port" type="number" label="Porta RTSP" :error="form.errors.port" required />
                    <ZyInput v-model.number="form.http_port" type="number" label="Porta HTTP" :error="form.errors.http_port" />
                    <ZyInput v-model.number="form.rtmp_port" type="number" label="Porta RTMP" :error="form.errors.rtmp_port" />
                    <ZySelect v-model="form.transport" :options="transportOptions" label="Transporte" :error="form.errors.transport" required />
                </div>
                <ZyInput v-model="form.secondary_address" label="Endereco secundario (fallback)" :error="form.errors.secondary_address" class="max-w-sm" />

                <template v-if="showDdnsFields">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <ZyInput v-model="form.ddns_provider" label="Provedor DDNS" :error="form.errors.ddns_provider" />
                        <ZyInput v-model="form.ddns_hostname" label="Hostname DDNS" :error="form.errors.ddns_hostname" />
                    </div>
                    <ZySwitch v-model="form.ipv6_preferred" label="Preferir IPv6" description="Quando disponivel, usar IPv6 ao inves de IPv4" />
                </template>
            </div>
        </ZyCard>

        <!-- Authentication -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Autenticacao</h3>
            </template>
            <div class="grid sm:grid-cols-2 gap-4 max-w-lg">
                <ZyInput v-model="form.username" label="Usuario" :error="form.errors.username" />
                <ZyInput v-model="form.password" type="password" label="Senha" placeholder="Deixe vazio para manter a atual" :error="form.errors.password" />
            </div>
        </ZyCard>

        <!-- Capacity & Features -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Capacidade e Recursos</h3>
            </template>
            <div class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4 max-w-lg">
                    <ZyInput v-model.number="form.total_channels" type="number" label="Total de canais" :error="form.errors.total_channels" required />
                    <ZyInput v-model="form.location" label="Localizacao" :error="form.errors.location" />
                </div>
                <ZyInput v-model="form.serial_number" label="Numero de serie" :error="form.errors.serial_number" class="max-w-sm" />
                <div class="flex flex-col gap-3">
                    <ZySwitch v-model="form.onvif_supported" label="Suporte ONVIF" description="Dispositivo suporta protocolo ONVIF" />
                    <ZyInput v-if="form.onvif_supported" v-model="form.onvif_profile" label="Perfil ONVIF" :error="form.errors.onvif_profile" class="max-w-sm" />
                    <ZySwitch v-model="form.is_active" label="Ativo" description="Dispositivo habilitado para monitoramento" />
                </div>
            </div>
        </ZyCard>
    </div>
</template>
