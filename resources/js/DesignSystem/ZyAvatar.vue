<script setup lang="ts">
/**
 * ZyAvatar — Avatar de usuário do Design System
 */

import { computed } from 'vue';

interface Props {
    src?: string;
    name?: string;
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    status?: 'online' | 'offline' | 'busy' | 'away';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const sizeClasses: Record<string, string> = {
    xs: 'h-6 w-6 text-xs',
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-12 w-12 text-base',
    xl: 'h-16 w-16 text-lg',
};

const statusClasses: Record<string, string> = {
    online: 'bg-secondary-500',
    offline: 'bg-dark-400',
    busy: 'bg-danger-500',
    away: 'bg-warning-500',
};

const statusSizes: Record<string, string> = {
    xs: 'h-1.5 w-1.5',
    sm: 'h-2 w-2',
    md: 'h-2.5 w-2.5',
    lg: 'h-3 w-3',
    xl: 'h-3.5 w-3.5',
};

const initials = computed(() => {
    if (!props.name) return '?';
    return props.name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
});
</script>

<template>
    <div class="relative inline-flex shrink-0">
        <img
            v-if="src"
            :src="src"
            :alt="name || 'Avatar'"
            :class="['rounded-full object-cover', sizeClasses[size]]"
        />
        <div
            v-else
            :class="[
                'rounded-full flex items-center justify-center font-semibold',
                'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
                sizeClasses[size],
            ]"
        >
            {{ initials }}
        </div>

        <!-- Status indicator -->
        <span
            v-if="status"
            :class="[
                'absolute bottom-0 right-0 block rounded-full ring-2 ring-white dark:ring-dark-800',
                statusClasses[status],
                statusSizes[size],
            ]"
        />
    </div>
</template>
