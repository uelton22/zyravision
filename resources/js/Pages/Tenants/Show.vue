<script setup lang="ts">
/**
 * Tenants Show — Detalhes completos do tenant: info, usuarios, plano, quotas
 */

import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyBadge,
    ZyConfirmDialog, ZyTable, ZyAvatar, ZySelect, ZyAlert,
} from '@design';
import { PencilIcon, TrashIcon, PlusIcon, UserIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface TenantUser {
    id: number;
    name: string;
    email: string;
    phone?: string;
    avatar_url?: string;
    is_active: boolean;
    last_login_at?: string;
    role: string;
}

interface QuotaItem { used: number; limit: number; percentage: number; }

interface PlanData {
    id: string;
    slug: string;
    name: string;
    price: number;
    max_cameras: number;
    max_users: number;
    max_storage_gb: number;
    retention_days: number;
    max_concurrent_streams: number;
    max_resolution: string;
    max_fps: number;
}

interface Props {
    tenant: {
        id: string;
        name: string;
        slug: string;
        domain?: string;
        plan: string;
        is_active: boolean;
        max_cameras: number;
        max_users: number;
        storage_retention_days: number;
        users_count: number;
        created_at: string;
    };
    users: TenantUser[];
    subscription: any;
    plan: PlanData | null;
    quotas: Record<string, QuotaItem>;
    plans: PlanData[];
}

const props = defineProps<Props>();

const activeTab = ref<'info' | 'users' | 'plan'>('info');
const showDeleteConfirm = ref(false);
const showDeleteUserConfirm = ref(false);
const userToDelete = ref<TenantUser | null>(null);
const showChangePlan = ref(false);

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Tenants', href: '/tenants' },
    { label: props.tenant.name },
];

const planForm = useForm({
    plan_slug: props.tenant.plan,
});

const tabs = [
    { key: 'info', label: 'Informações' },
    { key: 'users', label: `Usuários (${props.users?.length || 0})` },
    { key: 'plan', label: 'Plano & Quotas' },
];

const roleLabels: Record<string, string> = {
    'super-admin': 'Super Admin',
    admin: 'Administrador',
    operator: 'Operador',
    viewer: 'Visualizador',
};

const roleBadgeVariants: Record<string, 'danger' | 'primary' | 'warning' | 'secondary'> = {
    'super-admin': 'danger',
    admin: 'primary',
    operator: 'warning',
    viewer: 'secondary',
};

const planLabels: Record<string, string> = { starter: 'Starter', professional: 'Professional', enterprise: 'Enterprise' };
const planVariants: Record<string, 'secondary' | 'primary' | 'success'> = { starter: 'secondary', professional: 'primary', enterprise: 'success' };

const userColumns = [
    { key: 'name', label: 'Usuário' },
    { key: 'email', label: 'E-mail' },
    { key: 'role', label: 'Função' },
    { key: 'is_active', label: 'Status' },
    { key: 'last_login_at', label: 'Último Acesso' },
];

function getQuota(key: string) {
    return props.quotas?.[key] ?? { used: 0, limit: 0, percentage: 0 };
}

function getBarColor(pct: number): string {
    if (pct >= 90) return 'bg-danger-500';
    if (pct >= 70) return 'bg-warning-500';
    return 'bg-primary-500';
}

function confirmDeleteUser(user: TenantUser) {
    userToDelete.value = user;
    showDeleteUserConfirm.value = true;
}

function deleteUser() {
    if (userToDelete.value) {
        router.delete(`/users/${userToDelete.value.id}`, {
            data: { redirect_to: 'tenants.show' },
            onSuccess: () => { showDeleteUserConfirm.value = false; userToDelete.value = null; },
        });
    }
}

function deleteTenant() {
    router.delete(`/tenants/${props.tenant.id}`, {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}

function changePlan() {
    planForm.put(`/tenants/${props.tenant.id}/change-plan`, {
        onSuccess: () => { showChangePlan.value = false; },
    });
}

const quotaItems = [
    { key: 'cameras', label: 'Câmeras', icon: '📷' },
    { key: 'users', label: 'Usuários', icon: '👥' },
    { key: 'storage_gb', label: 'Armazenamento (GB)', icon: '💾' },
    { key: 'streams', label: 'Streams Simultâneos', icon: '📡' },
];
</script>

<template>
    <Head :title="tenant.name" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader :title="tenant.name" :description="`Slug: ${tenant.slug}`">
        <template #actions>
            <ZyBadge :variant="planVariants[tenant.plan] || 'secondary'" class="text-body mr-2">
                {{ planLabels[tenant.plan] || tenant.plan }}
            </ZyBadge>
            <ZyBadge :variant="tenant.is_active ? 'success' : 'danger'" dot>
                {{ tenant.is_active ? 'Ativo' : 'Inativo' }}
            </ZyBadge>
            <ZyButton variant="secondary" :href="`/tenants/${tenant.id}/edit`">
                <PencilIcon class="h-4 w-4" />
                Editar
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

    <!-- Tab: Informacoes -->
    <div v-if="activeTab === 'info'" class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Dados do Tenant</h3>
                </template>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Nome</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ tenant.name }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Slug</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ tenant.slug }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Domínio</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ tenant.domain || 'Não configurado' }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Retenção</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ tenant.storage_retention_days }} dias</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Usuários</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ tenant.users_count }}</p>
                    </div>
                    <div>
                        <p class="text-caption font-medium text-dark-500 mb-1">Criado em</p>
                        <p class="text-body text-[var(--zy-text-primary)]">{{ tenant.created_at }}</p>
                    </div>
                </div>
            </ZyCard>
        </div>
        <div>
            <ZyCard v-if="subscription">
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Assinatura</h3>
                </template>
                <div class="space-y-3 text-body-sm">
                    <div class="flex justify-between">
                        <span class="text-dark-500">Plano</span>
                        <span class="font-medium text-[var(--zy-text-primary)]">{{ subscription.plan?.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-dark-500">Status</span>
                        <ZyBadge :variant="subscription.status === 'active' ? 'success' : 'warning'">{{ subscription.status }}</ZyBadge>
                    </div>
                    <div v-if="subscription.plan?.price > 0" class="flex justify-between">
                        <span class="text-dark-500">Valor</span>
                        <span class="font-bold text-[var(--zy-text-primary)]">R$ {{ Number(subscription.plan.price).toFixed(2).replace('.', ',') }}/mês</span>
                    </div>
                </div>
            </ZyCard>
        </div>
    </div>

    <!-- Tab: Usuarios -->
    <div v-if="activeTab === 'users'">
        <div class="flex items-center justify-between mb-4">
            <p class="text-body-sm text-dark-500">{{ users?.length || 0 }} usuário(s) neste tenant</p>
            <ZyButton :href="`/users/create?tenant_id=${tenant.id}`" variant="primary" size="sm">
                <PlusIcon class="h-4 w-4" />
                Adicionar Usuário
            </ZyButton>
        </div>

        <ZyTable :columns="userColumns" :data="(users || []) as any[]" hoverable>
            <template #cell-name="{ row }">
                <div class="flex items-center gap-3">
                    <ZyAvatar :name="(row as any).name" :src="(row as any).avatar_url" size="sm" />
                    <span class="font-medium text-[var(--zy-text-primary)]">{{ (row as any).name }}</span>
                </div>
            </template>

            <template #cell-role="{ row }">
                <ZyBadge :variant="roleBadgeVariants[(row as any).role] || 'secondary'">
                    {{ roleLabels[(row as any).role] || (row as any).role }}
                </ZyBadge>
            </template>

            <template #cell-is_active="{ row }">
                <ZyBadge :variant="(row as any).is_active ? 'success' : 'danger'" dot>
                    {{ (row as any).is_active ? 'Ativo' : 'Inativo' }}
                </ZyBadge>
            </template>

            <template #cell-last_login_at="{ row }">
                <span class="text-body-sm text-dark-500">{{ (row as any).last_login_at || 'Nunca acessou' }}</span>
            </template>

            <template #actions="{ row }">
                <div class="flex items-center justify-end gap-1">
                    <ZyButton variant="ghost" size="sm" :href="`/users/${(row as any).id}/edit`" aria-label="Editar">
                        <PencilIcon class="h-4 w-4" />
                    </ZyButton>
                    <ZyButton variant="ghost" size="sm" aria-label="Excluir" @click="confirmDeleteUser(row as any)">
                        <TrashIcon class="h-4 w-4 text-danger-500" />
                    </ZyButton>
                </div>
            </template>
        </ZyTable>
    </div>

    <!-- Tab: Plano & Quotas -->
    <div v-if="activeTab === 'plan'" class="grid lg:grid-cols-2 gap-6">
        <!-- Current Plan -->
        <ZyCard>
            <template #header>
                <div class="flex items-center justify-between">
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Plano Atual</h3>
                    <ZyButton variant="secondary" size="sm" @click="showChangePlan = !showChangePlan">
                        Alterar Plano
                    </ZyButton>
                </div>
            </template>

            <!-- Change Plan Form -->
            <div v-if="showChangePlan" class="mb-6 p-4 bg-dark-50 dark:bg-dark-900/50 rounded-zy space-y-3">
                <p class="text-body-sm font-medium text-[var(--zy-text-primary)]">Selecione o novo plano:</p>
                <div class="space-y-2">
                    <button
                        v-for="p in plans"
                        :key="p.slug"
                        :class="[
                            'w-full flex items-center justify-between px-4 py-3 rounded-zy border text-left transition-all',
                            planForm.plan_slug === p.slug
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                                : 'border-dark-200 dark:border-dark-700 hover:border-dark-300',
                        ]"
                        @click="planForm.plan_slug = p.slug"
                    >
                        <div>
                            <p class="font-semibold text-[var(--zy-text-primary)]">{{ p.name }}</p>
                            <p class="text-caption text-dark-500">{{ p.max_cameras }} câm, {{ p.max_users }} users, {{ p.max_storage_gb }}GB</p>
                        </div>
                        <span v-if="p.price > 0" class="font-bold text-[var(--zy-text-primary)]">R$ {{ Number(p.price).toFixed(2).replace('.', ',') }}</span>
                        <span v-else class="text-dark-500 text-body-sm">Sob consulta</span>
                    </button>
                </div>
                <div class="flex gap-2 pt-2">
                    <ZyButton size="sm" :loading="planForm.processing" @click="changePlan">Confirmar Alteração</ZyButton>
                    <ZyButton size="sm" variant="secondary" @click="showChangePlan = false">Cancelar</ZyButton>
                </div>
            </div>

            <!-- Plan details -->
            <div v-if="plan" class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-heading-sm font-bold text-[var(--zy-text-primary)]">{{ plan.name }}</span>
                    <span v-if="plan.price > 0" class="text-heading font-bold text-[var(--zy-text-primary)]">
                        R$ {{ Number(plan.price).toFixed(2).replace('.', ',') }}
                        <span class="text-body-sm font-normal text-dark-500">/mês</span>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-body-sm pt-2 border-t border-dark-200 dark:border-dark-700">
                    <div class="flex justify-between"><span class="text-dark-500">Câmeras</span><span class="font-medium">{{ plan.max_cameras }}</span></div>
                    <div class="flex justify-between"><span class="text-dark-500">Usuários</span><span class="font-medium">{{ plan.max_users }}</span></div>
                    <div class="flex justify-between"><span class="text-dark-500">Storage</span><span class="font-medium">{{ plan.max_storage_gb }}GB</span></div>
                    <div class="flex justify-between"><span class="text-dark-500">Retenção</span><span class="font-medium">{{ plan.retention_days }}d</span></div>
                    <div class="flex justify-between"><span class="text-dark-500">Resolução</span><span class="font-medium">{{ plan.max_resolution }}</span></div>
                    <div class="flex justify-between"><span class="text-dark-500">FPS</span><span class="font-medium">{{ plan.max_fps }}</span></div>
                    <div class="flex justify-between"><span class="text-dark-500">Streams</span><span class="font-medium">{{ plan.max_concurrent_streams }}</span></div>
                </div>
            </div>
        </ZyCard>

        <!-- Quotas -->
        <ZyCard>
            <template #header>
                <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Consumo de Recursos</h3>
            </template>
            <div class="space-y-5">
                <div v-for="item in quotaItems" :key="item.key" class="space-y-2">
                    <div class="flex items-center justify-between text-body-sm">
                        <span class="text-dark-600 dark:text-dark-300">{{ item.icon }} {{ item.label }}</span>
                        <span class="font-semibold text-[var(--zy-text-primary)]">
                            {{ getQuota(item.key).used }} / {{ getQuota(item.key).limit }}
                        </span>
                    </div>
                    <div class="h-3 bg-dark-200 dark:bg-dark-700 rounded-full overflow-hidden">
                        <div
                            :class="['h-full rounded-full transition-all duration-500', getBarColor(getQuota(item.key).percentage)]"
                            :style="{ width: Math.min(100, getQuota(item.key).percentage) + '%' }"
                        />
                    </div>
                    <ZyAlert v-if="getQuota(item.key).percentage >= 90" variant="danger" class="mt-1">
                        Limite quase atingido! {{ getQuota(item.key).used }}/{{ getQuota(item.key).limit }}
                    </ZyAlert>
                </div>
            </div>
        </ZyCard>
    </div>

    <!-- Delete User Confirm -->
    <ZyConfirmDialog
        :show="showDeleteUserConfirm"
        title="Excluir Usuário"
        :message="`Excluir ${userToDelete?.name} deste tenant?`"
        confirm-text="Excluir"
        @confirm="deleteUser"
        @cancel="showDeleteUserConfirm = false"
    />

    <!-- Delete Tenant Confirm -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Tenant"
        :message="`Excluir ${tenant.name}? Todos os dados serão removidos.`"
        confirm-text="Excluir"
        @confirm="deleteTenant"
        @cancel="showDeleteConfirm = false"
    />
</template>
