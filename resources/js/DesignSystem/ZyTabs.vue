<script setup lang="ts">
/**
 * ZyTabs — Componente de abas do Design System
 */

import { computed } from 'vue';

interface Tab {
    key: string;
    label: string;
    icon?: object;
    badge?: string | number;
    disabled?: boolean;
}

interface Props {
    tabs: Tab[];
    modelValue: string;
    variant?: 'underline' | 'pills';
    size?: 'sm' | 'md';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'underline',
    size: 'md',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const tabClasses = computed(() => {
    if (props.variant === 'pills') {
        return {
            container: 'flex gap-1 p-1 bg-dark-100 dark:bg-dark-800 rounded-lg',
            base: [
                'rounded-md font-medium transition-all duration-200',
                props.size === 'sm' ? 'px-3 py-1.5 text-xs' : 'px-4 py-2 text-sm',
            ].join(' '),
            active: 'bg-white dark:bg-dark-700 text-[var(--zy-text-primary)] shadow-sm',
            inactive: 'text-dark-500 dark:text-dark-400 hover:text-[var(--zy-text-primary)]',
            disabled: 'opacity-50 cursor-not-allowed',
        };
    }

    return {
        container: 'flex border-b border-dark-200 dark:border-dark-700',
        base: [
            'font-medium transition-all duration-200 border-b-2 -mb-px',
            props.size === 'sm' ? 'px-3 py-2 text-xs' : 'px-4 py-2.5 text-sm',
        ].join(' '),
        active: 'border-primary-500 text-primary-600 dark:text-primary-400',
        inactive: 'border-transparent text-dark-500 dark:text-dark-400 hover:text-[var(--zy-text-primary)] hover:border-dark-300 dark:hover:border-dark-600',
        disabled: 'opacity-50 cursor-not-allowed',
    };
});

function selectTab(tab: Tab) {
    if (tab.disabled) return;
    emit('update:modelValue', tab.key);
}
</script>

<template>
    <div>
        <div :class="tabClasses.container" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="modelValue === tab.key"
                :disabled="tab.disabled"
                :class="[
                    tabClasses.base,
                    tab.disabled
                        ? tabClasses.disabled
                        : modelValue === tab.key
                            ? tabClasses.active
                            : tabClasses.inactive,
                ]"
                @click="selectTab(tab)"
            >
                <span class="inline-flex items-center gap-1.5">
                    <component v-if="tab.icon" :is="tab.icon" class="h-4 w-4" />
                    {{ tab.label }}
                    <span
                        v-if="tab.badge != null"
                        class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-medium rounded-full bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300"
                    >
                        {{ tab.badge }}
                    </span>
                </span>
            </button>
        </div>
        <div class="mt-4" role="tabpanel">
            <slot :name="modelValue" />
        </div>
    </div>
</template>
