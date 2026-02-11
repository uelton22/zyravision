<script setup lang="ts">
/**
 * Audit Log — Visualização de auditoria de ações
 */

import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    ZyPageHeader, ZyBreadcrumb, ZyCard, ZyTable, ZyPagination,
    ZyInput, ZySelect, ZyBadge,
} from '@design';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

interface ActivityRow {
    id: number;
    description: string;
    event: string;
    subject_type: string;
    subject_id: string;
    created_at: string;
    properties: { attributes?: Record<string, any>; old?: Record<string, any> };
    causer?: { id: number; name: string; email: string };
}

interface LaravelPagination<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
}

interface Props {
    activities: LaravelPagination<ActivityRow>;
    filters: { search?: string; event?: string };
}

const props = defineProps<Props>();

const search = ref(props.filters?.search || '');
const eventFilter = ref(props.filters?.event || '');

const breadcrumbs = [
    { label: 'Início', href: '/dashboard' },
    { label: 'Auditoria' },
];

const columns = [
    { key: 'created_at', label: 'Data', width: '160px' },
    { key: 'causer', label: 'Usuário' },
    { key: 'event', label: 'Evento', width: '120px' },
    { key: 'description', label: 'Descrição' },
    { key: 'subject_type', label: 'Recurso', width: '140px' },
];

const eventOptions = [
    { value: '', label: 'Todos os eventos' },
    { value: 'created', label: 'Criado' },
    { value: 'updated', label: 'Atualizado' },
    { value: 'deleted', label: 'Excluído' },
];

const eventVariants: Record<string, 'success' | 'primary' | 'danger' | 'secondary'> = {
    created: 'success',
    updated: 'primary',
    deleted: 'danger',
};

const eventLabels: Record<string, string> = {
    created: 'Criado',
    updated: 'Atualizado',
    deleted: 'Excluído',
};

function applyFilters() {
    router.get('/audit', {
        search: search.value || undefined,
        event: eventFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function changePage(page: number) {
    router.get('/audit', { ...props.filters, page }, { preserveState: true });
}

function formatSubjectType(type: string): string {
    if (!type) return '-';
    const parts = type.split('\\');
    return parts[parts.length - 1];
}
</script>

<template>
    <Head title="Auditoria" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader title="Auditoria" description="Histórico de ações realizadas no sistema" />

    <!-- Filters -->
    <ZyCard class="mb-6" padding="md">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <ZyInput
                    v-model="search"
                    type="search"
                    placeholder="Buscar na descrição..."
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
                    v-model="eventFilter"
                    :options="eventOptions"
                    placeholder="Evento"
                    @update:model-value="applyFilters"
                />
            </div>
        </div>
    </ZyCard>

    <!-- Table -->
    <ZyTable :columns="columns" :data="(activities?.data || []) as any[]">
        <template #cell-created_at="{ row }">
            <span class="text-body-sm text-dark-500 whitespace-nowrap">
                {{ (row as any).created_at }}
            </span>
        </template>

        <template #cell-causer="{ row }">
            <div v-if="(row as any).causer">
                <p class="text-body-sm font-medium text-[var(--zy-text-primary)]">{{ (row as any).causer.name }}</p>
                <p class="text-caption text-dark-500">{{ (row as any).causer.email }}</p>
            </div>
            <span v-else class="text-body-sm text-dark-400">Sistema</span>
        </template>

        <template #cell-event="{ row }">
            <ZyBadge :variant="eventVariants[(row as any).event] || 'secondary'">
                {{ eventLabels[(row as any).event] || (row as any).event }}
            </ZyBadge>
        </template>

        <template #cell-subject_type="{ row }">
            <span class="text-body-sm text-dark-600 dark:text-dark-300">
                {{ formatSubjectType((row as any).subject_type) }}
            </span>
        </template>
    </ZyTable>

    <!-- Pagination -->
    <ZyPagination
        v-if="activities && activities.last_page > 1"
        :current-page="activities.current_page"
        :last-page="activities.last_page"
        :from="activities.from"
        :to="activities.to"
        :total="activities.total"
        class="mt-4"
        @page-change="changePage"
    />
</template>
