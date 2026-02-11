<script setup lang="ts">
/**
 * ZyCard — Card container do Design System
 */

interface Props {
    padding?: 'none' | 'sm' | 'md' | 'lg';
    hover?: boolean;
    border?: boolean;
}

withDefaults(defineProps<Props>(), {
    padding: 'md',
    hover: false,
    border: true,
});

const paddingClasses: Record<string, string> = {
    none: '',
    sm: 'p-3',
    md: 'p-4 sm:p-6',
    lg: 'p-6 sm:p-8',
};
</script>

<template>
    <div
        :class="[
            'rounded-zy-md bg-white dark:bg-dark-800 transition-all duration-200',
            border ? 'border border-dark-200 dark:border-dark-700' : 'shadow-zy',
            hover ? 'hover:shadow-zy-md hover:border-primary-300 dark:hover:border-primary-700 cursor-pointer' : '',
            paddingClasses[padding],
        ]"
    >
        <div v-if="$slots.header" class="border-b border-dark-200 dark:border-dark-700 pb-4 mb-4">
            <slot name="header" />
        </div>

        <slot />

        <div v-if="$slots.footer" class="border-t border-dark-200 dark:border-dark-700 pt-4 mt-4">
            <slot name="footer" />
        </div>
    </div>
</template>
