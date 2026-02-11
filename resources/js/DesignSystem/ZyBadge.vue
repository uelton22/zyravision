<script setup lang="ts">
/**
 * ZyBadge — Badge/status indicator do Design System
 */

interface Props {
    variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info';
    size?: 'sm' | 'md';
    dot?: boolean;
}

withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    dot: false,
});

const variantClasses: Record<string, string> = {
    primary: 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300',
    secondary: 'bg-dark-100 text-dark-800 dark:bg-dark-800 dark:text-dark-300',
    success: 'bg-secondary-100 text-secondary-800 dark:bg-secondary-900/30 dark:text-secondary-300',
    danger: 'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-300',
    warning: 'bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-300',
    info: 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300',
};

const dotClasses: Record<string, string> = {
    primary: 'bg-primary-500',
    secondary: 'bg-dark-500',
    success: 'bg-secondary-500',
    danger: 'bg-danger-500',
    warning: 'bg-warning-500',
    info: 'bg-primary-500',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 font-medium rounded-full',
            variantClasses[variant],
            size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs',
        ]"
    >
        <span v-if="dot" :class="['h-1.5 w-1.5 rounded-full', dotClasses[variant]]" />
        <slot />
    </span>
</template>
