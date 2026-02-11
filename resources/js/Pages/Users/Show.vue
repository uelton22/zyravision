<script setup lang="ts">
/**
 * Users Show — Detalhes do usuário
 */

import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge, ZyAvatar, ZyConfirmDialog } from '@design';
import { PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string;
        avatar_url?: string;
        is_active: boolean;
        last_login_at?: string;
        last_login_ip?: string;
        created_at: string;
        tenant?: { id: string; name: string; plan: string };
        roles: { id: number; name: string }[];
    };
}

const props = defineProps<Props>();

const showDeleteConfirm = ref(false);

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Usuários', href: '/users' },
    { label: props.user.name },
];

const roleLabels: Record<string, string> = {
    'super-admin': 'Super Admin',
    admin: 'Administrador',
    operator: 'Operador',
    viewer: 'Visualizador',
};

function deleteUser() {
    router.delete(`/users/${props.user.id}`, {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}
</script>

<template>
    <Head :title="user.name" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="user.name" description="Detalhes do usuário">
        <template #actions>
            <ZyButton variant="secondary" :href="`/users/${user.id}/edit`">
                <PencilIcon class="h-5 w-5" />
                Editar
            </ZyButton>
            <ZyButton variant="danger" @click="showDeleteConfirm = true">
                <TrashIcon class="h-5 w-5" />
                Excluir
            </ZyButton>
        </template>
    </ZyPageHeader>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Informações Pessoais</h3>
                </template>

                <div class="flex items-start gap-6">
                    <ZyAvatar :name="user.name" :src="user.avatar_url" size="xl" />
                    <div class="flex-1 space-y-4">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-caption font-medium text-dark-500 mb-1">Nome</p>
                                <p class="text-body text-[var(--zy-text-primary)]">{{ user.name }}</p>
                            </div>
                            <div>
                                <p class="text-caption font-medium text-dark-500 mb-1">E-mail</p>
                                <p class="text-body text-[var(--zy-text-primary)]">{{ user.email }}</p>
                            </div>
                            <div>
                                <p class="text-caption font-medium text-dark-500 mb-1">Telefone</p>
                                <p class="text-body text-[var(--zy-text-primary)]">{{ user.phone || 'Não informado' }}</p>
                            </div>
                            <div>
                                <p class="text-caption font-medium text-dark-500 mb-1">Função</p>
                                <ZyBadge :variant="user.roles?.[0]?.name === 'admin' ? 'primary' : user.roles?.[0]?.name === 'operator' ? 'warning' : 'secondary'">
                                    {{ roleLabels[user.roles?.[0]?.name] || 'Sem função' }}
                                </ZyBadge>
                            </div>
                        </div>
                    </div>
                </div>
            </ZyCard>

            <!-- Activity -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Atividade</h3>
                </template>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Último acesso</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ user.last_login_at || 'Nunca acessou' }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">IP do último acesso</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ user.last_login_ip || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Criado em</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ user.created_at }}</p>
                    </div>
                </div>
            </ZyCard>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Status</h3>
                </template>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-body-sm text-dark-500">Conta</span>
                        <ZyBadge :variant="user.is_active ? 'success' : 'danger'" dot>
                            {{ user.is_active ? 'Ativa' : 'Inativa' }}
                        </ZyBadge>
                    </div>
                </div>
            </ZyCard>

            <ZyCard v-if="user.tenant">
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Tenant</h3>
                </template>
                <div class="space-y-3">
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Empresa</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ user.tenant.name }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Plano</p>
                        <ZyBadge :variant="user.tenant.plan === 'enterprise' ? 'success' : user.tenant.plan === 'professional' ? 'primary' : 'secondary'">
                            {{ user.tenant.plan }}
                        </ZyBadge>
                    </div>
                </div>
            </ZyCard>
        </div>
    </div>

    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Usuário"
        :message="`Tem certeza que deseja excluir ${user.name}?`"
        confirm-text="Excluir"
        @confirm="deleteUser"
        @cancel="showDeleteConfirm = false"
    />
</template>
