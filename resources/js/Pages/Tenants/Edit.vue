<script setup lang="ts">
/**
 * Tenants Edit — Formulário de edição de tenant
 */

import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZySelect, ZySwitch } from '@design';
import type { Tenant } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface Props {
    tenant: Tenant;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Tenants', href: '/tenants' },
    { label: props.tenant.name },
];

const form = useForm({
    name: props.tenant.name,
    domain: props.tenant.domain || '',
    max_cameras: (props.tenant as any).max_cameras || 16,
    max_users: (props.tenant as any).max_users || 5,
    storage_retention_days: (props.tenant as any).storage_retention_days || 7,
    plan: (props.tenant as any).plan || 'starter',
    is_active: props.tenant.is_active,
});

const planOptions = [
    { value: 'starter', label: 'Starter (até 16 câmeras)' },
    { value: 'professional', label: 'Professional (até 64 câmeras)' },
    { value: 'enterprise', label: 'Enterprise (ilimitado)' },
];

function submit() {
    form.put(`/tenants/${props.tenant.id}`);
}
</script>

<template>
    <Head :title="`Editar — ${tenant.name}`" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="`Editar: ${tenant.name}`" description="Atualize os dados do tenant">
        <template #actions>
            <ZyButton variant="secondary" href="/tenants">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" @click="submit">Salvar Alterações</ZyButton>
        </template>
    </ZyPageHeader>

    <ZyCard>
        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
            <div class="grid sm:grid-cols-2 gap-4">
                <ZyInput v-model="form.name" label="Nome da empresa" :error="form.errors.name" required />
                <ZyInput v-model="form.domain" label="Domínio" :error="form.errors.domain" placeholder="empresa.zyravision.com.br" />
            </div>

            <ZySelect v-model="form.plan" :options="planOptions" label="Plano" :error="form.errors.plan" required />

            <div class="grid sm:grid-cols-3 gap-4">
                <ZyInput v-model="form.max_cameras" type="number" label="Máx. Câmeras" :error="form.errors.max_cameras" required />
                <ZyInput v-model="form.max_users" type="number" label="Máx. Usuários" :error="form.errors.max_users" required />
                <ZyInput v-model="form.storage_retention_days" type="number" label="Retenção (dias)" :error="form.errors.storage_retention_days" required />
            </div>

            <ZySwitch v-model="form.is_active" label="Ativo" description="Tenant pode ser acessado pelos seus usuários" />
        </form>
    </ZyCard>
</template>
