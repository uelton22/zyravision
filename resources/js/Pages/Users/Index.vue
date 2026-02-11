<script setup lang="ts">
/**
 * Users Index — Listagem de usuários com filtros e paginação
 * Super-admin vê usuários de todos os tenants com filtro
 */

import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyTable, ZyPagination,
    ZyButton, ZyInput, ZySelect, ZyBadge, ZyConfirmDialog, ZyAvatar,
} from '@design';
import { PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface UserRow {
    id: number;
    name: string;
    email: string;
    avatar_url?: string;
    phone?: string;
    is_active: boolean;
    last_login_at?: string;
    roles: { id: number; name: string }[];
    tenant?: { id: string; name: string };
}

interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
}

interface Props {
    users: LaravelPagination<UserRow>;
    filters: {
        search?: string;
        role?: string;
        is_active?: string;
        tenant_id?: string;
    };
    tenants: { id: string; name: string; slug: string }[];
    isSuperAdmin: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    tenants: () => [],
    isSuperAdmin: false,
});

const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const tenantFilter = ref(props.filters?.tenant_id || '');
const showDeleteConfirm = ref(false);
const userToDelete = ref<UserRow | null>(null);

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Usuários' },
];

const columns = [
    { key: 'name', label: 'Usuário', sortable: true },
    { key: 'email', label: 'E-mail', sortable: true },
    ...(props.isSuperAdmin ? [{ key: 'tenant', label: 'Empresa' }] : []),
    { key: 'role', label: 'Função' },
    { key: 'is_active', label: 'Status' },
    { key: 'last_login_at', label: 'Último Acesso' },
];

const roleOptions = [
    { value: '', label: 'Todas as funções' },
    { value: 'super-admin', label: 'Super Admin' },
    { value: 'admin', label: 'Administrador' },
    { value: 'operator', label: 'Operador' },
    { value: 'viewer', label: 'Visualizador' },
];

const tenantOptions = [
    { value: '', label: 'Todas as empresas' },
    ...(props.tenants || []).map((t) => ({ value: t.id, label: t.name })),
];

const roleLabels: Record<string, string> = {
    'super-admin': 'Super Admin',
    admin: 'Administrador',
    operator: 'Operador',
    viewer: 'Visualizador',
};

const roleBadgeVariants: Record<string, string> = {
    'super-admin': 'danger',
    admin: 'primary',
    operator: 'warning',
    viewer: 'secondary',
};

function applyFilters() {
    router.get('/users', {
        search: search.value || undefined,
        role: roleFilter.value || undefined,
        tenant_id: tenantFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function confirmDelete(user: UserRow) {
    userToDelete.value = user;
    showDeleteConfirm.value = true;
}

function deleteUser() {
    if (userToDelete.value) {
        router.delete(`/users/${userToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                userToDelete.value = null;
            },
        });
    }
}

function changePage(page: number) {
    router.get('/users', { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="Usuários" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Usuários" :description="isSuperAdmin ? 'Gerencie os usuários de todas as empresas' : 'Gerencie os usuários da sua empresa'">
        <template #actions>
            <ZyButton href="/users/create" variant="primary">
                <PlusIcon class="h-5 w-5" />
                Novo Usuário
            </ZyButton>
        </template>
    </ZyPageHeader>

    <!-- Filters -->
    <ZyCard class="mb-6" padding="md">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <ZyInput
                    v-model="search"
                    type="search"
                    placeholder="Buscar por nome ou e-mail..."
                    icon
                    @keyup.enter="applyFilters"
                >
                    <template #icon>
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </template>
                </ZyInput>
            </div>
            <div v-if="isSuperAdmin" class="w-full sm:w-48">
                <ZySelect
                    v-model="tenantFilter"
                    :options="tenantOptions"
                    placeholder="Empresa"
                    @update:model-value="applyFilters"
                />
            </div>
            <div class="w-full sm:w-48">
                <ZySelect
                    v-model="roleFilter"
                    :options="roleOptions"
                    placeholder="Função"
                    @update:model-value="applyFilters"
                />
            </div>
            <ZyButton variant="secondary" @click="applyFilters">
                Filtrar
            </ZyButton>
        </div>
    </ZyCard>

    <!-- Table -->
    <ZyTable :columns="columns" :data="(users?.data || []) as any[]" hoverable>
        <template #cell-name="{ row }">
            <div class="flex items-center gap-3">
                <ZyAvatar :name="(row as any).name" :src="(row as any).avatar_url" size="sm" />
                <div>
                    <p class="font-medium text-[var(--zy-text-primary)]">{{ (row as any).name }}</p>
                    <p v-if="(row as any).phone" class="text-caption text-dark-500">{{ (row as any).phone }}</p>
                </div>
            </div>
        </template>

        <template v-if="isSuperAdmin" #cell-tenant="{ row }">
            <span class="text-body-sm text-[var(--zy-text-primary)]">{{ (row as any).tenant?.name || '—' }}</span>
        </template>

        <template #cell-role="{ row }">
            <ZyBadge
                :variant="(roleBadgeVariants[(row as any).roles?.[0]?.name] || 'secondary') as any"
            >
                {{ roleLabels[(row as any).roles?.[0]?.name] || (row as any).roles?.[0]?.name || 'Sem função' }}
            </ZyBadge>
        </template>

        <template #cell-is_active="{ row }">
            <ZyBadge :variant="(row as any).is_active ? 'success' : 'danger'" dot>
                {{ (row as any).is_active ? 'Ativo' : 'Inativo' }}
            </ZyBadge>
        </template>

        <template #cell-last_login_at="{ row }">
            <span class="text-body-sm text-dark-500">
                {{ (row as any).last_login_at || 'Nunca acessou' }}
            </span>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center justify-end gap-1">
                <ZyButton variant="ghost" size="sm" :href="`/users/${(row as any).id}`" aria-label="Visualizar">
                    <EyeIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="ghost" size="sm" :href="`/users/${(row as any).id}/edit`" aria-label="Editar">
                    <PencilIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="ghost" size="sm" aria-label="Excluir" @click="confirmDelete(row as any)">
                    <TrashIcon class="h-4 w-4 text-danger-500" />
                </ZyButton>
            </div>
        </template>
    </ZyTable>

    <!-- Pagination -->
    <ZyPagination
        v-if="users && users.last_page > 1"
        :current-page="users.current_page"
        :last-page="users.last_page"
        :from="users.from"
        :to="users.to"
        :total="users.total"
        class="mt-4"
        @page-change="changePage"
    />

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Usuário"
        :message="`Tem certeza que deseja excluir o usuário ${userToDelete?.name}? Esta ação não pode ser desfeita.`"
        confirm-text="Excluir"
        @confirm="deleteUser"
        @cancel="showDeleteConfirm = false"
    />
</template>
