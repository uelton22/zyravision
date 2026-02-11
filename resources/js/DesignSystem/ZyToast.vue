<script setup lang="ts">
/**
 * ZyToast — Notificação flutuante (toast) do Design System
 * Uso: via composable useToast()
 */

import { TransitionGroup } from 'vue';
import { XMarkIcon, CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { useToast } from '../Composables/useToast';

const { toasts, removeToast } = useToast();

const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};

const styles = {
    success: 'border-secondary-500 dark:border-secondary-400',
    error: 'border-danger-500 dark:border-danger-400',
    warning: 'border-warning-500 dark:border-warning-400',
    info: 'border-primary-500 dark:border-primary-400',
};

const iconStyles = {
    success: 'text-secondary-500',
    error: 'text-danger-500',
    warning: 'text-warning-500',
    info: 'text-primary-500',
};
</script>

<template>
    <div
        aria-live="assertive"
        class="fixed top-4 right-4 z-toast flex flex-col gap-3 w-full max-w-sm pointer-events-none"
    >
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                :class="[
                    'pointer-events-auto rounded-zy-md bg-white dark:bg-dark-800 shadow-zy-lg border-l-4 p-4',
                    styles[toast.type],
                ]"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <component
                        :is="icons[toast.type]"
                        :class="['h-5 w-5 shrink-0 mt-0.5', iconStyles[toast.type]]"
                        aria-hidden="true"
                    />
                    <div class="flex-1 min-w-0">
                        <p v-if="toast.title" class="text-body-sm font-semibold text-[var(--zy-text-primary)]">
                            {{ toast.title }}
                        </p>
                        <p class="text-body-sm text-[var(--zy-text-secondary)]">
                            {{ toast.message }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="shrink-0 rounded-zy p-0.5 text-dark-400 hover:text-dark-600 dark:hover:text-dark-200"
                        aria-label="Fechar notificação"
                        @click="removeToast(toast.id)"
                    >
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>
