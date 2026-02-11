<script setup lang="ts">
/**
 * ZyConfirmDialog — Dialog de confirmação destrutiva
 */

import ZyModal from './ZyModal.vue';
import ZyButton from './ZyButton.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

interface Props {
    show: boolean;
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning';
    loading?: boolean;
}

withDefaults(defineProps<Props>(), {
    title: 'Confirmar ação',
    message: 'Tem certeza que deseja continuar? Esta ação não pode ser desfeita.',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    variant: 'danger',
    loading: false,
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
</script>

<template>
    <ZyModal :show="show" size="sm" @close="emit('cancel')">
        <div class="text-center sm:text-left">
            <div class="flex items-center gap-4">
                <div
                    :class="[
                        'flex h-12 w-12 shrink-0 items-center justify-center rounded-full',
                        variant === 'danger'
                            ? 'bg-danger-100 dark:bg-danger-900/30'
                            : 'bg-warning-100 dark:bg-warning-900/30',
                    ]"
                >
                    <ExclamationTriangleIcon
                        :class="[
                            'h-6 w-6',
                            variant === 'danger' ? 'text-danger-600 dark:text-danger-400' : 'text-warning-600 dark:text-warning-400',
                        ]"
                    />
                </div>
                <div>
                    <h3 class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">{{ title }}</h3>
                    <p class="mt-1 text-body-sm text-[var(--zy-text-secondary)]">{{ message }}</p>
                </div>
            </div>
        </div>

        <template #footer>
            <ZyButton variant="secondary" @click="emit('cancel')">{{ cancelText }}</ZyButton>
            <ZyButton :variant="variant" :loading="loading" @click="emit('confirm')">{{ confirmText }}</ZyButton>
        </template>
    </ZyModal>
</template>
