<script setup lang="ts">
/**
 * ZyDropdown — Menu dropdown do Design System
 */

import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';

interface DropdownItem {
    label: string;
    icon?: object;
    action?: () => void;
    href?: string;
    danger?: boolean;
    disabled?: boolean;
    divider?: boolean;
}

interface Props {
    items: DropdownItem[];
    align?: 'left' | 'right';
    width?: string;
}

withDefaults(defineProps<Props>(), {
    align: 'right',
    width: 'w-48',
});
</script>

<template>
    <Menu as="div" class="relative inline-block text-left">
        <MenuButton as="template">
            <slot name="trigger" />
        </MenuButton>

        <transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <MenuItems
                :class="[
                    'absolute z-dropdown mt-2 rounded-zy-md bg-white dark:bg-dark-800',
                    'shadow-zy-lg ring-1 ring-dark-200 dark:ring-dark-700',
                    'focus:outline-none py-1',
                    width,
                    align === 'right' ? 'right-0 origin-top-right' : 'left-0 origin-top-left',
                ]"
            >
                <template v-for="(item, index) in items" :key="index">
                    <div v-if="item.divider" class="border-t border-dark-200 dark:border-dark-700 my-1" />
                    <MenuItem v-else v-slot="{ active }" :disabled="item.disabled">
                        <button
                            type="button"
                            :class="[
                                'flex w-full items-center gap-2 px-3 py-2 text-body-sm transition-colors',
                                item.danger
                                    ? active
                                        ? 'bg-danger-50 text-danger-700 dark:bg-danger-900/20 dark:text-danger-300'
                                        : 'text-danger-600 dark:text-danger-400'
                                    : active
                                        ? 'bg-dark-50 text-dark-900 dark:bg-dark-700 dark:text-white'
                                        : 'text-dark-700 dark:text-dark-300',
                                item.disabled ? 'opacity-50 cursor-not-allowed' : '',
                            ]"
                            @click="item.action?.()"
                        >
                            <component v-if="item.icon" :is="item.icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ item.label }}
                        </button>
                    </MenuItem>
                </template>
            </MenuItems>
        </transition>
    </Menu>
</template>
