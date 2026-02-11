<script setup lang="ts">
/**
 * ZyPageHeader — Título da página com ações do Design System
 */

interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-heading-lg font-bold text-[var(--zy-text-primary)]">
                {{ title }}
            </h1>
            <p v-if="description" class="mt-1 text-body-sm text-[var(--zy-text-secondary)]">
                {{ description }}
            </p>
        </div>

        <div v-if="$slots.actions" class="flex items-center gap-3 shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
