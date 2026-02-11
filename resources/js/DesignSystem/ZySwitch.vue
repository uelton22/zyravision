<script setup lang="ts">
/**
 * ZySwitch — Toggle switch do Design System
 */

import { computed } from 'vue';
import { Switch } from '@headlessui/vue';

interface Props {
    modelValue?: boolean;
    label?: string;
    description?: string;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const enabled = computed({
    get: () => props.modelValue,
    set: (val: boolean) => emit('update:modelValue', val),
});
</script>

<template>
    <div class="flex items-center justify-between">
        <div v-if="label || description" class="flex-1 mr-4">
            <p class="text-body-sm font-medium text-[var(--zy-text-primary)]">{{ label }}</p>
            <p v-if="description" class="text-caption text-dark-500">{{ description }}</p>
        </div>
        <Switch
            v-model="enabled"
            :disabled="disabled"
            :class="[
                enabled ? 'bg-primary-600 dark:bg-primary-500' : 'bg-dark-300 dark:bg-dark-600',
                disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent',
                'transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                'dark:focus:ring-offset-dark-900',
            ]"
        >
            <span class="sr-only">{{ label }}</span>
            <span
                :class="[
                    enabled ? 'translate-x-5' : 'translate-x-0',
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg',
                    'ring-0 transition duration-200 ease-in-out',
                ]"
            />
        </Switch>
    </div>
</template>
