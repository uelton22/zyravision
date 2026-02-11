<script setup lang="ts">
/**
 * ZyTable — Tabela com paginação e ações do Design System
 */

interface Column {
    key: string;
    label: string;
    sortable?: boolean;
    align?: 'left' | 'center' | 'right';
    width?: string;
}

interface Props {
    columns: Column[];
    data: Record<string, unknown>[];
    loading?: boolean;
    striped?: boolean;
    hoverable?: boolean;
    emptyMessage?: string;
}

withDefaults(defineProps<Props>(), {
    loading: false,
    striped: false,
    hoverable: true,
    emptyMessage: 'Nenhum registro encontrado.',
});

const emit = defineEmits<{
    sort: [key: string];
    'row-click': [row: Record<string, unknown>];
}>();

const alignClasses: Record<string, string> = {
    left: 'text-left',
    center: 'text-center',
    right: 'text-right',
};
</script>

<template>
    <div class="overflow-hidden rounded-zy-md border border-dark-200 dark:border-dark-700">
        <div class="overflow-x-auto zy-scrollbar">
            <table class="min-w-full divide-y divide-dark-200 dark:divide-dark-700">
                <!-- Head -->
                <thead class="bg-dark-50 dark:bg-dark-800/50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-4 py-3 text-caption font-semibold uppercase tracking-wider text-dark-500 dark:text-dark-400',
                                alignClasses[col.align || 'left'],
                                col.sortable ? 'cursor-pointer hover:text-dark-700 dark:hover:text-dark-200 select-none' : '',
                            ]"
                            :style="col.width ? { width: col.width } : {}"
                            scope="col"
                            @click="col.sortable && emit('sort', col.key)"
                        >
                            {{ col.label }}
                        </th>
                        <th v-if="$slots.actions" scope="col" class="px-4 py-3 text-right">
                            <span class="sr-only">Ações</span>
                        </th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody class="divide-y divide-dark-200 dark:divide-dark-700 bg-white dark:bg-dark-800">
                    <!-- Loading -->
                    <template v-if="loading">
                        <tr v-for="i in 5" :key="`skeleton-${i}`">
                            <td v-for="col in columns" :key="`skeleton-${i}-${col.key}`" class="px-4 py-3">
                                <div class="h-4 bg-dark-200 dark:bg-dark-700 rounded animate-pulse" />
                            </td>
                            <td v-if="$slots.actions" class="px-4 py-3">
                                <div class="h-4 w-20 bg-dark-200 dark:bg-dark-700 rounded animate-pulse ml-auto" />
                            </td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <tr v-else-if="data.length === 0">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-4 py-12 text-center">
                            <slot name="empty">
                                <p class="text-body-sm text-dark-500">{{ emptyMessage }}</p>
                            </slot>
                        </td>
                    </tr>

                    <!-- Data rows -->
                    <tr
                        v-for="(row, index) in data"
                        v-else
                        :key="index"
                        :class="[
                            hoverable ? 'hover:bg-dark-50 dark:hover:bg-dark-700/50 transition-colors' : '',
                            striped && index % 2 ? 'bg-dark-50/50 dark:bg-dark-800/80' : '',
                        ]"
                        @click="emit('row-click', row)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            :class="[
                                'px-4 py-3 text-body-sm text-[var(--zy-text-primary)] whitespace-nowrap',
                                alignClasses[col.align || 'left'],
                            ]"
                        >
                            <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                                {{ row[col.key] }}
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-3 text-right whitespace-nowrap">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
