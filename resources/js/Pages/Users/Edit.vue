<script setup lang="ts">
/**
 * Users Edit — Formulário de edição de usuário
 */

import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZySelect, ZySwitch } from '@design';
import type { User } from '@/Types';

defineOptions({ layout: AuthenticatedLayout });

interface Props {
    user: User;
    roles: string[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Usuários', href: '/users' },
    { label: props.user.name },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    phone: (props.user as any).phone || '',
    role: props.user.roles?.[0]?.name || 'viewer',
    is_active: (props.user as any).is_active ?? true,
});

const roleOptions = props.roles.map((r) => ({
    value: r,
    label: r === 'admin' ? 'Administrador' : r === 'operator' ? 'Operador' : 'Visualizador',
}));

function submit() {
    form.put(`/users/${props.user.id}`);
}
</script>

<template>
    <Head :title="`Editar — ${user.name}`" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="`Editar: ${user.name}`" description="Atualize os dados do usuário">
        <template #actions>
            <ZyButton variant="secondary" href="/users">Cancelar</ZyButton>
            <ZyButton :loading="form.processing" @click="submit">Salvar Alterações</ZyButton>
        </template>
    </ZyPageHeader>

    <ZyCard>
        <form @submit.prevent="submit" class="space-y-6 max-w-2xl">
            <div class="grid sm:grid-cols-2 gap-4">
                <ZyInput v-model="form.name" label="Nome completo" :error="form.errors.name" required />
                <ZyInput v-model="form.email" type="email" label="E-mail" :error="form.errors.email" required />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <ZyInput v-model="form.password" type="password" label="Nova Senha" :error="form.errors.password" hint="Deixe vazio para manter a senha atual" />
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
