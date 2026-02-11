<script setup lang="ts">
/**
 * Plans Index — Listagem de planos (super-admin)
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

interface PlanRow {
    id: string;
    name: string;
    slug: string;
    price: number;
    billing_period: string;
    is_active: boolean;
    is_featured: boolean;
    sort_order: number;
    max_cameras: number;
    max_users: number;
    max_storage_gb: number;
    subscriptions_count: number;
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
    plans: LaravelPagination<PlanRow>;
    filters: {
        search?: string;
        is_active?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.is_active ?? '');
const showDeleteConfirm = ref(false);
const planToDelete = ref<PlanRow | null>(null);

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Planos' },
];

const columns = [
    { key: 'name', label: 'Plano', sortable: true },
    { key: 'price', label: 'Preço' },
    { key: 'limits', label: 'Limites' },
    { key: 'subscriptions_count', label: 'Assinaturas', align: 'center' as const },
    { key: 'is_active', label: 'Status' },
];

const statusOptions = [
    { value: '', label: 'Todos' },
    { value: '1', label: 'Ativos' },
    { value: '0', label: 'Inativos' },
];

function formatPrice(price: number): string {
    if (price <= 0) return 'Sob consulta';
    return 'R$ ' + Number(price).toFixed(2).replace('.', ',');
}

function formatBillingPeriod(period: string): string {
    const labels: Record<string, string> = {
        monthly: '/mês',
        yearly: '/ano',
        custom: '',
    };
    return labels[period] || '';
}

function applyFilters() {
    router.get('/plans', {
        search: search.value || undefined,
        is_active: statusFilter.value !== '' ? statusFilter.value : undefined,
    }, { preserveState: true, replace: true });
}

function confirmDelete(plan: PlanRow) {
    planToDelete.value = plan;
    showDeleteConfirm.value = true;
}

function deletePlan() {
    if (planToDelete.value) {
        router.delete(`/plans/${planToDelete.value.id}`, {
            onSuccess: () => {
                showDeleteConfirm.value = false;
                planToDelete.value = null;
            },
        });
    }
}

function changePage(page: number) {
    router.get('/plans', { ...props.filters, page }, { preserveState: true });
}
</script>

<template>
    <Head title="Planos" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Planos" description="Gerencie os planos da plataforma">
        <template #actions>
            <ZyButton href="/plans/create" variant="primary">
                <PlusIcon class="h-5 w-5" />
                Novo Plano
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
                    placeholder="Buscar por nome ou descrição..."
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
                    v-model="statusFilter"
                    :options="statusOptions"
                    placeholder="Status"
                    @update:model-value="applyFilters"
                />
            </div>
            <ZyButton variant="secondary" @click="applyFilters">Filtrar</ZyButton>
        </div>
    </ZyCard>

    <!-- Table -->
    <ZyTable :columns="columns" :data="(plans?.data || []) as any[]" hoverable>
        <template #cell-name="{ row }">
            <div>
                <div class="flex items-center gap-2">
                    <p class="font-medium text-[var(--zy-text-primary)]">{{ (row as any).name }}</p>
                    <ZyBadge v-if="(row as any).is_featured" variant="warning">Destaque</ZyBadge>
                </div>
                <p class="text-caption text-dark-500">{{ (row as any).slug }}</p>
            </div>
        </template>

        <template #cell-price="{ row }">
            <div>
                <span class="font-semibold text-[var(--zy-text-primary)]">
                    {{ formatPrice((row as any).price) }}
                </span>
                <span v-if="(row as any).price > 0" class="text-caption text-dark-500">
                    {{ formatBillingPeriod((row as any).billing_period) }}
                </span>
            </div>
        </template>

        <template #cell-limits="{ row }">
            <div class="text-body-sm text-dark-600 dark:text-dark-300 space-y-0.5">
                <p>{{ (row as any).max_cameras }} câmeras</p>
                <p>{{ (row as any).max_users }} usuários</p>
                <p>{{ (row as any).max_storage_gb }}GB storage</p>
            </div>
        </template>

        <template #cell-subscriptions_count="{ row }">
            <span class="text-body-sm font-medium">{{ (row as any).subscriptions_count }}</span>
        </template>

        <template #cell-is_active="{ row }">
            <ZyBadge :variant="(row as any).is_active ? 'success' : 'danger'" dot>
                {{ (row as any).is_active ? 'Ativo' : 'Inativo' }}
            </ZyBadge>
        </template>

        <template #actions="{ row }">
            <div class="flex items-center justify-end gap-1">
                <ZyButton variant="ghost" size="sm" :href="`/plans/${(row as any).id}`" aria-label="Visualizar">
                    <EyeIcon class="h-4 w-4" />
                </ZyButton>
                <ZyButton variant="ghost" size="sm" :href="`/plans/${(row as any).id}/edit`" aria-label="Editar">
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
        v-if="plans && plans.last_page > 1"
        :current-page="plans.current_page"
        :last-page="plans.last_page"
        :from="plans.from"
        :to="plans.to"
        :total="plans.total"
        class="mt-4"
        @page-change="changePage"
    />

    <!-- Delete Confirmation -->
    <ZyConfirmDialog
        :show="showDeleteConfirm"
        title="Excluir Plano"
        :message="`Tem certeza que deseja excluir o plano '${planToDelete?.name}'? Esta ação não pode ser desfeita.`"
        confirm-text="Excluir"
        @confirm="deletePlan"
        @cancel="showDeleteConfirm = false"
    />
</template>
