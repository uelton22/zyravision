<script setup lang="ts">
/**
 * ZyBreadcrumb — Navegação hierárquica do Design System
 */

import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon, HomeIcon } from '@heroicons/vue/24/outline';

interface BreadcrumbItem {
    label: string;
    href?: string;
    icon?: boolean;
}

interface Props {
    items: BreadcrumbItem[];
}

defineProps<Props>();
</script>

<template>
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center gap-1">
            <li v-for="(item, index) in items" :key="index" class="flex items-center gap-1">
                <ChevronRightIcon v-if="index > 0" class="h-4 w-4 text-dark-400 shrink-0" aria-hidden="true" />

                <Link
                    v-if="item.href && index < items.length - 1"
                    :href="item.href"
                    class="inline-flex items-center gap-1 text-body-sm text-dark-500 hover:text-primary-600 dark:text-dark-400 dark:hover:text-primary-400 transition-colors"
                >
                    <HomeIcon v-if="index === 0" class="h-4 w-4 shrink-0" />
                    {{ item.label }}
                </Link>

                <span
                    v-else
                    class="text-body-sm font-medium text-[var(--zy-text-primary)]"
                    :aria-current="index === items.length - 1 ? 'page' : undefined"
                >
                    <HomeIcon v-if="index === 0 && !item.href" class="h-4 w-4 shrink-0 inline" />
                    {{ item.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>
