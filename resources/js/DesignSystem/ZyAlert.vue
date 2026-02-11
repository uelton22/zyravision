<script setup lang="ts">
/**
 * ZyAlert — Alerta inline do Design System
 */

import { XMarkIcon, CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';

interface Props {
    variant?: 'success' | 'danger' | 'warning' | 'info';
    title?: string;
    dismissible?: boolean;
}

withDefaults(defineProps<Props>(), {
    variant: 'info',
    dismissible: false,
});

const emit = defineEmits<{
    dismiss: [];
}>();

const styles: Record<string, { bg: string; border: string; text: string; icon: string }> = {
    success: {
        bg: 'bg-secondary-50 dark:bg-secondary-900/20',
        border: 'border-secondary-400 dark:border-secondary-700',
        text: 'text-secondary-800 dark:text-secondary-200',
        icon: 'text-secondary-500',
    },
    danger: {
        bg: 'bg-danger-50 dark:bg-danger-900/20',
        border: 'border-danger-400 dark:border-danger-700',
        text: 'text-danger-800 dark:text-danger-200',
        icon: 'text-danger-500',
    },
    warning: {
        bg: 'bg-warning-50 dark:bg-warning-900/20',
        border: 'border-warning-400 dark:border-warning-700',
        text: 'text-warning-800 dark:text-warning-200',
        icon: 'text-warning-500',
    },
    info: {
        bg: 'bg-primary-50 dark:bg-primary-900/20',
        border: 'border-primary-400 dark:border-primary-700',
        text: 'text-primary-800 dark:text-primary-200',
        icon: 'text-primary-500',
    },
};

const icons = {
    success: CheckCircleIcon,
    danger: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};
</script>

<template>
    <div
        :class="[
            'rounded-zy border-l-4 p-4',
            styles[variant].bg,
            styles[variant].border,
        ]"
        role="alert"
    >
        <div class="flex">
            <component :is="icons[variant]" :class="['h-5 w-5 shrink-0', styles[variant].icon]" aria-hidden="true" />
            <div class="ml-3 flex-1">
                <p v-if="title" :class="['text-body-sm font-semibold', styles[variant].text]">{{ title }}</p>
                <div :class="['text-body-sm', styles[variant].text, title ? 'mt-1' : '']">
                    <slot />
                </div>
            </div>
            <button
                v-if="dismissible"
                type="button"
                :class="['ml-3 shrink-0 rounded-zy p-0.5 transition-colors', styles[variant].text, 'hover:opacity-75']"
                aria-label="Fechar alerta"
                @click="emit('dismiss')"
            >
                <XMarkIcon class="h-5 w-5" />
            </button>
        </div>
    </div>
</template>
