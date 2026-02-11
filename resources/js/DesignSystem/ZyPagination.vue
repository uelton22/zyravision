<script setup lang="ts">
/**
 * ZyPagination — Paginação server-side do Design System
 */

import { computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';

interface Props {
    currentPage: number;
    lastPage: number;
    from: number;
    to: number;
    total: number;
    perPage?: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'page-change': [page: number];
}>();

const pages = computed(() => {
    const range: (number | string)[] = [];
    const delta = 2;

    for (let i = 1; i <= props.lastPage; i++) {
        if (
            i === 1 ||
            i === props.lastPage ||
            (i >= props.currentPage - delta && i <= props.currentPage + delta)
        ) {
            range.push(i);
        } else if (range[range.length - 1] !== '...') {
            range.push('...');
        }
    }

    return range;
});
</script>

<template>
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-2 py-3">
        <p class="text-body-sm text-dark-500 dark:text-dark-400">
            Mostrando <span class="font-medium text-[var(--zy-text-primary)]">{{ from }}</span>
            a <span class="font-medium text-[var(--zy-text-primary)]">{{ to }}</span>
            de <span class="font-medium text-[var(--zy-text-primary)]">{{ total }}</span> resultados
        </p>

        <nav class="flex items-center gap-1" aria-label="Paginação">
            <!-- Previous -->
            <button
                type="button"
                :disabled="currentPage <= 1"
                class="p-2 rounded-zy text-dark-500 hover:bg-dark-100 dark:hover:bg-dark-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                aria-label="Página anterior"
                @click="emit('page-change', currentPage - 1)"
            >
                <ChevronLeftIcon class="h-4 w-4" />
            </button>

            <!-- Pages -->
            <template v-for="page in pages" :key="page">
                <span v-if="page === '...'" class="px-2 text-dark-400">...</span>
                <button
                    v-else
                    type="button"
                    :class="[
                        'min-w-[2rem] h-8 px-2 rounded-zy text-body-sm font-medium transition-colors',
                        page === currentPage
                            ? 'bg-primary-600 text-white dark:bg-primary-500'
                            : 'text-dark-600 hover:bg-dark-100 dark:text-dark-400 dark:hover:bg-dark-700',
                    ]"
                    :aria-current="page === currentPage ? 'page' : undefined"
                    @click="emit('page-change', page as number)"
                >
                    {{ page }}
                </button>
            </template>

            <!-- Next -->
            <button
                type="button"
                :disabled="currentPage >= lastPage"
                class="p-2 rounded-zy text-dark-500 hover:bg-dark-100 dark:hover:bg-dark-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                aria-label="Próxima página"
                @click="emit('page-change', currentPage + 1)"
            >
                <ChevronRightIcon class="h-4 w-4" />
            </button>
        </nav>
    </div>
</template>
