<script setup lang="ts">
/**
 * ZySlideOver — Painel lateral deslizante do Design System
 */

import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

interface Props {
    show: boolean;
    title?: string;
    size?: 'sm' | 'md' | 'lg';
    side?: 'left' | 'right';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    side: 'right',
});

const emit = defineEmits<{
    close: [];
}>();

const sizeClasses: Record<string, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
};
</script>

<template>
    <TransitionRoot :show="show" as="template">
        <Dialog as="div" class="relative z-modal" @close="emit('close')">
            <!-- Backdrop -->
            <TransitionChild
                as="template"
                enter="ease-in-out duration-500"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in-out duration-500"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-dark-900/75 dark:bg-dark-950/80 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div :class="['fixed inset-y-0 flex max-w-full', side === 'right' ? 'right-0 pl-10' : 'left-0 pr-10']">
                        <TransitionChild
                            as="template"
                            enter="transform transition ease-in-out duration-500"
                            :enter-from="side === 'right' ? 'translate-x-full' : '-translate-x-full'"
                            enter-to="translate-x-0"
                            leave="transform transition ease-in-out duration-500"
                            leave-from="translate-x-0"
                            :leave-to="side === 'right' ? 'translate-x-full' : '-translate-x-full'"
                        >
                            <DialogPanel :class="['w-screen', sizeClasses[size]]">
                                <div class="flex h-full flex-col bg-white dark:bg-dark-800 shadow-zy-xl">
                                    <!-- Header -->
                                    <div class="flex items-center justify-between px-6 py-4 border-b border-dark-200 dark:border-dark-700">
                                        <DialogTitle v-if="title" class="text-heading-sm font-semibold text-[var(--zy-text-primary)]">
                                            {{ title }}
                                        </DialogTitle>
                                        <button
                                            type="button"
                                            class="rounded-zy p-1 text-dark-400 hover:text-dark-600 dark:hover:text-dark-200 transition-colors"
                                            aria-label="Fechar painel"
                                            @click="emit('close')"
                                        >
                                            <XMarkIcon class="h-5 w-5" />
                                        </button>
                                    </div>

                                    <!-- Body -->
                                    <div class="flex-1 overflow-y-auto p-6 zy-scrollbar">
                                        <slot />
                                    </div>

                                    <!-- Footer -->
                                    <div v-if="$slots.footer" class="border-t border-dark-200 dark:border-dark-700 px-6 py-4">
                                        <slot name="footer" />
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
