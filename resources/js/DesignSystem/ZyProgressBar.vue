<script setup lang="ts">
/**
 * ZyProgressBar — Barra de progresso do Design System
 */

import { computed } from 'vue';

interface Props {
    value: number;
    max?: number;
    variant?: 'primary' | 'success' | 'warning' | 'danger' | 'info' | 'auto';
    size?: 'sm' | 'md' | 'lg';
    showLabel?: boolean;
    label?: string;
    striped?: boolean;
    indeterminate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    max: 100,
    variant: 'auto',
    size: 'md',
    showLabel: false,
    striped: false,
    indeterminate: false,
});

const percentage = computed(() => {
    if (props.indeterminate) return 100;
    return Math.min(100, Math.max(0, (props.value / props.max) * 100));
});

const sizeClass = computed(() => {
    const map: Record<string, string> = {
        sm: 'h-1.5',
        md: 'h-2.5',
        lg: 'h-4',
    };
    return map[props.size];
});

const resolvedVariant = computed(() => {
    if (props.variant !== 'auto') return props.variant;
    if (percentage.value >= 90) return 'danger';
    if (percentage.value >= 70) return 'warning';
    return 'success';
});

const barClass = computed(() => {
    const map: Record<string, string> = {
        primary: 'bg-primary-500',
        success: 'bg-secondary-500',
        warning: 'bg-warning-500',
        danger: 'bg-danger-500',
        info: 'bg-primary-400',
    };
    return map[resolvedVariant.value] ?? 'bg-primary-500';
});
</script>

<template>
    <div class="w-full">
        <div v-if="showLabel || label" class="flex justify-between mb-1">
            <span class="text-body-sm text-dark-500 dark:text-dark-400">{{ label }}</span>
            <span class="text-body-sm font-medium text-[var(--zy-text-primary)]">
                {{ indeterminate ? '...' : `${Math.round(percentage)}%` }}
            </span>
        </div>
        <div :class="['w-full rounded-full bg-dark-200 dark:bg-dark-700 overflow-hidden', sizeClass]">
            <div
                :class="[
                    'rounded-full transition-all duration-500',
                    barClass,
                    sizeClass,
                    indeterminate && 'animate-pulse',
                    striped && 'bg-gradient-to-r from-transparent via-white/20 to-transparent bg-[length:1rem_100%]',
                ]"
                :style="{ width: `${percentage}%` }"
                role="progressbar"
                :aria-valuenow="indeterminate ? undefined : value"
                :aria-valuemin="0"
                :aria-valuemax="max"
            />
        </div>
    </div>
</template>
