<script setup lang="ts">
/**
 * ZyModal — Modal dialog do Design System
 */

import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

interface Props {
    show: boolean;
    title?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    closeable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    closeable: true,
});

const emit = defineEmits<{
    close: [];
}>();

const sizeClasses: Record<string, string> = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-lg',
    lg: 'sm:max-w-2xl',
    xl: 'sm:max-w-4xl',
};

function close() {
    if (props.closeable) {
        emit('close');
    }
}
</script>

<template>
    <TransitionRoot :show="show" as="template">
        <Dialog as="div" class="relative z-modal" @close="close">
            <!-- Backdrop -->
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-dark-900/75 dark:bg-dark-950/80 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-modal overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        enter-to="opacity-100 translate-y-0 sm:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 sm:scale-100"
                        leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    >
                        <DialogPanel
                            :class="[
                                'relative w-full transform overflow-hidden rounded-zy-lg',
                                'bg-white dark:bg-dark-800 text-left shadow-zy-xl transition-all',
                                sizeClasses[size],
                            ]"
                        >
                            <!-- Header -->
                            <div v-if="title || closeable" class="flex items-center justify-between px-6 pt-6 pb-0">
                                <DialogTitle v-if="title" class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                                    {{ title }}
                                </DialogTitle>
                                <button
                                    v-if="closeable"
                                    type="button"
                                    class="rounded-zy p-1 text-dark-400 hover:text-dark-600 dark:hover:text-dark-200 transition-colors"
                                    aria-label="Fechar"
                                    @click="close"
                                >
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-4">
                                <slot />
                            </div>

                            <!-- Footer -->
                            <div v-if="$slots.footer" class="flex justify-end gap-3 px-6 pb-6">
                                <slot name="footer" />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
