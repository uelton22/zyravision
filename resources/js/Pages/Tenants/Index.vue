<script setup lang="ts">
/**
 * Tenants Index — Listagem de tenants (super-admin)
 */

import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyTable, ZyPagination,
    ZyButton, ZyInput, ZySelect, ZyBadge, ZyConfirmDialog,
} from '@design';
import { PlusIcon, MagnifyingGlassIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface TenantRow {
    id: string;
    name: string;
    slug: string;
    plan: string;
    is_active: boolean;
    max_cameras: number;
    max_users: number;
    users_count: number;
    created_at: string;
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
    tenants: LaravelPagination<TenantRow>;
    filters: {
        search?: string;
        is_active?: string;
        plan?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');
const planFilter = ref(props.filters?.plan || '');
const showDeleteConfirm = ref(false);
const tenantToDelete = ref<TenantRow | null>(null);

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Tenants' },
];

const columns = [
    { key: 'name', label: 'Tenant', sortable: true },
    { key: 'plan', label: 'Plano' },
    { key: 'users_count', label: 'Usuários', align: 'center' as const },
    { key: 'max_cameras', label: 'Câmeras', align: 'center' as const },
    { key: 'is_active', label: 'Status' },
];

const planOptions = [
    { value: '', label: 'Todos os planos' },
    { value: 'starter', label: 'Starter' },
    { value: 'professional', label: 'Professional' },
    { value: 'enterprise', label: 'Enterprise' },
];

const planLabels: Record<string, string> = {
    starter: 'Starter',
    professional: 'Professional',
    enterprise: 'Enterprise',
};

const planVariants: Record<string, 'secondary' | 'primary' | 'success'> = {
    starter: 'secondary',
    professional: 'primary',
    enterprise: 'success',
};

function applyFilters() {
    router.get('/tenants', {
        search: search.value || undefined,
        plan: planFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function confirmDelete(tenant: TenantRow) {
    tenantToDelete.value = tenant;
    showDeleteConfirm.value = true;
}

function deleteTenant() {
    if (tenantToDelete.value) {
        router.delete(`/tenants/${tenantToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                tenantToDelete.value = null;
            },
        });
    }
}

function changePage(page: number) {
    router.get('/tenants', { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="Tenants" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Tenants" description="Gerencie as empresas da plataforma">
        <template #actions>
            <ZyButton href="/tenants/create" variant="primary">
                <PlusIcon class="h-5 w-5" />
                Novo Tenant
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
                    placeholder="Buscar por nome, slug ou domínio..."
                    icon
                    @keyup.enter="applyFilters"
                >
                    <template #icon>
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </template>
                </ZyInput>
            </div>
            <div class="w-full sm:w-48">
                <ZySelect
                    v-model="planFilter"
                    :options="planOptions"
                    placeholder="Plano"
                    @update:model-value="applyFilters"
                />
            </div>
            <ZyButton variant="secondary" @click="applyFilters">Filtrar</ZyButton>
        </div>
    </ZyCard>

    <!-- Table -->
    <ZyTable :columns="columns" :data="(tenants?.data || []) as any[]" hoverable>
        <template #cell-name="{ row }">
            <div>
                <p class="font-medium text-[var(--zy-text-primary)]">{{ (row as any).name }}</p>
                <p class="text-caption text-dark-500">{{ (row as any).slug }}</p>
            </div>
        </template>

        <template #cell-plan="{ row }">
            <ZyBadge :variant="planVariants[(row as any).plan] || 'secondary'">
                {{ planLabels[(row as any).plan] || (row as any).plan }}
            </ZyBadge>
        </template>

        <template #cell-users_count="{ row }">
            <span class="text-body-sm">
                {{ (row as any).users_count }} / {{ (row as any).max_users }}
            </span>
        </template>

        <template #cell-max_cameras="{ row }">
            <span class="text-body-sm">{{ (row as any).max_cameras }}</span>
        </template>

        <template #cell-is_active="{ row }">
            <ZyBadge :variant="(row as any).is_active ? 'success' : 'danger'" dot>
                {{ (row as any).is_active ? 'Ativo' : 'Inativo' }}
            </ZyBadge>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center justify-end gap-1">
                <ZyButton variant="ghost" size="sm" :href="`/tenants/${(row as any).id}`" aria-label="Visualizar">
                    <EyeIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="ghost" size="sm" :href="`/tenants/${(row as any).id}/edit`" aria-label="Editar">
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
        v-if="tenants && tenants.last_page > 1"
        :current-page="tenants.current_page"
        :last-page="tenants.last_page"
        :from="tenants.from"
        :to="tenants.to"
        :total="tenants.total"
        class="mt-4"
        @page-change="changePage"
    />

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Tenant"
        :message="`Tem certeza que deseja excluir o tenant ${tenantToDelete?.name}?`"
        confirm-text="Excluir"
        @confirm="deleteTenant"
        @cancel="showDeleteConfirm = false"
    />
</template>
