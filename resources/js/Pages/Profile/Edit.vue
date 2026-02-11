<script setup lang="ts">
/**
 * Profile Edit — Pagina de perfil do usuario
 */

import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyButton, ZyInput, ZyBadge, ZyAvatar, ZyAlert } from '@design';
import {
    ShieldCheckIcon,
    BuildingOfficeIcon,
    CreditCardIcon,
    ClipboardDocumentListIcon,
    UsersIcon,
    VideoCameraIcon,
    ServerStackIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string;
        avatar_url?: string;
        is_active: boolean;
        roles: { id: number; name: string }[];
    };
    tenant?: {
        id: string;
        name: string;
        plan: string;
        storage_retention_days: number;
    } | null;
    subscription?: {
        id: string;
        status: string;
        days_remaining?: number;
        plan?: {
            id: string;
            name: string;
            price: number;
        };
    } | null;
    quotas?: Record<string, { used: number; limit: number; percentage: number }>;
    isSuperAdmin?: boolean;
    platformStats?: {
        total_cameras: number;
        total_devices: number;
        total_tenants: number;
        total_users: number;
    } | null;
}

const props = withDefaults(defineProps<Props>(), {
    isSuperAdmin: false,
    platformStats: null,
});

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Meu Perfil' },
];

// Profile form
const profileForm = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    phone: props.user?.phone || '',
});

function updateProfile() {
    profileForm.put('/profile');
}

// Password form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updatePassword() {
    passwordForm.put('/profile/password', {
        onSuccess: () => passwordForm.reset(),
    });
}

const quotaItems = [
    { key: 'cameras', label: 'Cameras', icon: VideoCameraIcon },
    { key: 'users', label: 'Usuarios', icon: UsersIcon },
    { key: 'storage_gb', label: 'Armazenamento (GB)', icon: ServerStackIcon },
    { key: 'streams', label: 'Streams Simultaneos', icon: ServerStackIcon },
];

function getQuota(key: string) {
    return props.quotas?.[key] ?? { used: 0, limit: 0, percentage: 0 };
}

function getBarColor(percentage: number): string {
    if (percentage >= 90) return 'bg-danger-500';
    if (percentage >= 70) return 'bg-warning-500';
    return 'bg-primary-500';
}

const planLabels: Record<string, string> = {
    starter: 'Starter',
    professional: 'Professional',
    enterprise: 'Enterprise',
};

const adminLinks = [
    { label: 'Tenants', href: '/tenants', icon: BuildingOfficeIcon, description: 'Gerenciar empresas' },
    { label: 'Planos', href: '/plans', icon: CreditCardIcon, description: 'Gerenciar planos' },
    { label: 'Auditoria', href: '/audit', icon: ClipboardDocumentListIcon, description: 'Logs de atividade' },
    { label: 'Usuarios', href: '/users', icon: UsersIcon, description: 'Gerenciar usuarios' },
];
</script>

<template>
    <Head title="Meu Perfil" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Meu Perfil"
        :description="isSuperAdmin ? 'Administrador do sistema' : 'Gerencie suas informacoes pessoais e veja seu consumo'"
    />

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Left column — Profile forms -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Info -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Informacoes Pessoais</h3>
                </template>

                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div class="flex items-center gap-4 mb-6">
                        <ZyAvatar :name="user?.name || ''" :src="user?.avatar_url" size="xl" />
                        <div>
                            <p class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">{{ user?.name }}</p>
                            <p class="text-body-sm text-dark-500">{{ user?.email }}</p>
                            <ZyBadge
                                v-if="user?.roles?.[0]"
                                :variant="user.roles[0].name === 'admin' ? 'primary' : user.roles[0].name === 'super-admin' ? 'danger' : 'secondary'"
                                class="mt-1"
                            >
                                {{ user.roles[0].name === 'super-admin' ? 'Super Admin' : user.roles[0].name }}
                            </ZyBadge>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <ZyInput v-model="profileForm.name" label="Nome completo" :error="profileForm.errors.name" required />
                        <ZyInput v-model="profileForm.email" type="email" label="E-mail" :error="profileForm.errors.email" required />
                    </div>

                    <ZyInput v-model="profileForm.phone" type="tel" label="Telefone" :error="profileForm.errors.phone" />

                    <div class="flex justify-end">
                        <ZyButton type="submit" :loading="profileForm.processing">Salvar Alteracoes</ZyButton>
                    </div>
                </form>
            </ZyCard>

            <!-- Change Password -->
            <ZyCard>
                <template #header>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Alterar Senha</h3>
                </template>

                <form @submit.prevent="updatePassword" class="space-y-4">
                    <ZyInput v-model="passwordForm.current_password" type="password" label="Senha atual" :error="passwordForm.errors.current_password" required />

                    <div class="grid sm:grid-cols-2 gap-4">
                        <ZyInput v-model="passwordForm.password" type="password" label="Nova senha" :error="passwordForm.errors.password" required />
                        <ZyInput v-model="passwordForm.password_confirmation" type="password" label="Confirmar nova senha" required />
                    </div>

                    <div class="flex justify-end">
                        <ZyButton type="submit" :loading="passwordForm.processing" variant="secondary">Alterar Senha</ZyButton>
                    </div>
                </form>
            </ZyCard>
        </div>

        <!-- Right column -->
        <div class="space-y-6">
            <!-- ═══ Super Admin sidebar ═══ -->
            <template v-if="isSuperAdmin">
                <!-- Role & Access -->
                <ZyCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <ShieldCheckIcon class="h-5 w-5 text-danger-500" />
                            <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Acesso</h3>
                        </div>
                    </template>
                    <div class="space-y-3 text-body-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-dark-500">Nivel</span>
                            <ZyBadge variant="danger">Super Admin</ZyBadge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-dark-500">Escopo</span>
                            <span class="text-[var(--zy-text-primary)] font-medium">Global</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-dark-500">Tenant</span>
                            <span class="text-dark-400 italic">Nenhum (acesso total)</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-dark-500">Plano</span>
                            <span class="text-dark-400 italic">Sem restricoes</span>
                        </div>
                    </div>
                </ZyCard>

                <!-- Platform Overview -->
                <ZyCard v-if="platformStats">
                    <template #header>
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Visao da Plataforma</h3>
                    </template>
                    <div class="space-y-3 text-body-sm">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <VideoCameraIcon class="h-4 w-4 text-primary-500" />
                                <span class="text-dark-500">Cameras</span>
                            </div>
                            <span class="font-semibold text-[var(--zy-text-primary)]">{{ platformStats.total_cameras }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <ServerStackIcon class="h-4 w-4 text-accent-500" />
                                <span class="text-dark-500">Dispositivos</span>
                            </div>
                            <span class="font-semibold text-[var(--zy-text-primary)]">{{ platformStats.total_devices }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <BuildingOfficeIcon class="h-4 w-4 text-warning-500" />
                                <span class="text-dark-500">Empresas</span>
                            </div>
                            <span class="font-semibold text-[var(--zy-text-primary)]">{{ platformStats.total_tenants }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <UsersIcon class="h-4 w-4 text-secondary-500" />
                                <span class="text-dark-500">Usuarios</span>
                            </div>
                            <span class="font-semibold text-[var(--zy-text-primary)]">{{ platformStats.total_users }}</span>
                        </div>
                    </div>
                </ZyCard>

                <!-- Quick Links -->
                <ZyCard>
                    <template #header>
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Acesso Rapido</h3>
                    </template>
                    <div class="space-y-2">
                        <a
                            v-for="link in adminLinks"
                            :key="link.href"
                            :href="link.href"
                            class="flex items-center gap-3 p-2 rounded-zy-sm hover:bg-dark-100 dark:hover:bg-dark-800 transition-colors"
                        >
                            <component :is="link.icon" class="h-5 w-5 text-dark-500 shrink-0" />
                            <div class="min-w-0">
                                <p class="text-body-sm font-medium text-[var(--zy-text-primary)]">{{ link.label }}</p>
                                <p class="text-caption text-dark-400">{{ link.description }}</p>
                            </div>
                        </a>
                    </div>
                </ZyCard>
            </template>

            <!-- ═══ Regular user sidebar ═══ -->
            <template v-else>
                <!-- Current Plan -->
                <ZyCard>
                    <template #header>
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Plano Atual</h3>
                    </template>

                    <div v-if="subscription" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-heading-sm font-bold text-[var(--zy-text-primary)]">
                                    {{ subscription.plan?.name || planLabels[tenant?.plan || ''] || 'Starter' }}
                                </p>
                                <ZyBadge
                                    :variant="subscription.status === 'active' ? 'success' : subscription.status === 'trial' ? 'warning' : 'danger'"
                                    dot
                                >
                                    {{ subscription.status === 'active' ? 'Ativo' : subscription.status === 'trial' ? 'Trial' : subscription.status }}
                                </ZyBadge>
                            </div>
                            <p v-if="subscription.plan && subscription.plan.price > 0" class="text-heading font-bold text-[var(--zy-text-primary)]">
                                R$ {{ Number(subscription.plan.price).toFixed(2).replace('.', ',') }}
                                <span class="text-body-sm font-normal text-dark-500">/mes</span>
                            </p>
                        </div>

                        <ZyAlert v-if="subscription.status === 'trial'" variant="warning">
                            Seu trial termina em {{ subscription.days_remaining || '?' }} dias.
                        </ZyAlert>
                    </div>

                    <div v-else class="text-center py-4">
                        <p class="text-body-sm text-dark-500">
                            Plano: <strong>{{ planLabels[tenant?.plan || ''] || 'Starter' }}</strong>
                        </p>
                    </div>
                </ZyCard>

                <!-- Quotas -->
                <ZyCard>
                    <template #header>
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Consumo</h3>
                    </template>

                    <div class="space-y-4">
                        <div v-for="item in quotaItems" :key="item.key" class="space-y-1.5">
                            <div class="flex items-center justify-between text-body-sm">
                                <span class="flex items-center gap-2 text-dark-600 dark:text-dark-300">
                                    <component :is="item.icon" class="h-4 w-4" />
                                    {{ item.label }}
                                </span>
                                <span class="font-medium text-[var(--zy-text-primary)]">
                                    {{ getQuota(item.key).used }} / {{ getQuota(item.key).limit }}
                                </span>
                            </div>
                            <div class="h-2 bg-dark-200 dark:bg-dark-700 rounded-full overflow-hidden">
                                <div
                                    :class="['h-full rounded-full transition-all duration-500', getBarColor(getQuota(item.key).percentage)]"
                                    :style="{ width: Math.min(100, getQuota(item.key).percentage) + '%' }"
                                />
                            </div>
                        </div>
                    </div>
                </ZyCard>

                <!-- Tenant Info -->
                <ZyCard v-if="tenant">
                    <template #header>
                        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">Empresa</h3>
                    </template>
                    <div class="space-y-2 text-body-sm">
                        <div class="flex justify-between">
                            <span class="text-dark-500">Nome</span>
                            <span class="font-medium text-[var(--zy-text-primary)]">{{ tenant.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-dark-500">Retencao</span>
                            <span class="font-medium text-[var(--zy-text-primary)]">{{ tenant.storage_retention_days }} dias</span>
                        </div>
                    </div>
                </ZyCard>
            </template>
        </div>
    </div>
</template>
