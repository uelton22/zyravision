<script setup lang="ts">
/**
 * ZyEmptyState — Estado vazio padronizado do Design System
 */

import { InboxIcon } from '@heroicons/vue/24/outline';

interface Props {
    title?: string;
    description?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Nenhum registro encontrado',
    description: 'Comece adicionando um novo registro.',
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-dark-100 dark:bg-dark-800 mb-4">
            <slot name="icon">
                <InboxIcon class="h-8 w-8 text-dark-400" />
            </slot>
        </div>

        <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
            {{ title }}
        </h3>

        <p class="mt-1 text-body-sm text-[var(--zy-text-secondary)] max-w-md">
            {{ description }}
        </p>

        <div v-if="$slots.action" class="mt-6">
            <slot name="action" />
        </div>
    </div>
</template>
