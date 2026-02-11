<script setup lang="ts">
/**
 * Users Create — Formulário de criação de usuário
 * Super-admin pode escolher o tenant e ver se quota foi atingida
 */

import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZySelect, ZySwitch, ZyAlert } from '@design';

defineOptions({ layout: AuthenticatedLayout });

interface Props {
    roles: string[];
    tenants: { id: string; name: string }[];
    isSuperAdmin: boolean;
    defaultTenantId: string | null;
    quotaReached: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    tenants: () => [],
    isSuperAdmin: false,
    defaultTenantId: null,
    quotaReached: false,
});

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Usuários', href: '/users' },
    { label: 'Novo Usuário' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    phone: '',
    role: 'viewer',
    is_active: true,
    tenant_id: props.defaultTenantId || '',
});

const roleLabels: Record<string, string> = {
    'super-admin': 'Super Admin',
    admin: 'Administrador',
    operator: 'Operador',
    viewer: 'Visualizador',
};

const roleOptions = props.roles.map((r) => ({
    value: r,
    label: roleLabels[r] || r,
}));

const tenantOptions = props.tenants.map((t) => ({
    value: t.id,
    label: t.name,
}));

function submit() {
    form.post('/users');
}
</script>

<template>
    <Head title="Novo Usuário" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Novo Usuário" description="Cadastre um novo usuário no sistema">
        <template #actions>
            <ZyButton variant="secondary" href="/users">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" :disabled="quotaReached" @click="submit">Salvar</ZyButton>
        </template>
    </ZyPageHeader>

    <ZyAlert v-if="quotaReached" variant="warning" class="mb-4">
        O limite de usuários deste tenant foi atingido. Para adicionar mais usuários, faça upgrade do plano.
    </ZyAlert>

    <ZyCard>
        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
            <!-- Tenant select (super-admin only) -->
            <ZySelect
                v-if="isSuperAdmin && tenantOptions.length > 0"
                v-model="form.tenant_id"
                :options="tenantOptions"
                label="Empresa (Tenant)"
                :error="form.errors.tenant_id"
                required
            />

            <div class="grid sm:grid-cols-2 gap-4">
                <ZyInput v-model="form.name" label="Nome completo" :error="form.errors.name" required />
                <ZyInput v-model="form.email" type="email" label="E-mail" :error="form.errors.email" required />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <ZyInput v-model="form.password" type="password" label="Senha" :error="form.errors.password" required />
                <ZyInput v-model="form.phone" type="tel" label="Telefone" :error="form.errors.phone" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <ZySelect v-model="form.role" :options="roleOptions" label="Função" :error="form.errors.role" required />
                <div class="flex items-end pb-2">
                    <ZySwitch v-model="form.is_active" label="Ativo" description="Usuário pode acessar o sistema" />
                </div>
            </div>
        </form>
    </ZyCard>
</template>
