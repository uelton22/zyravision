<script setup lang="ts">
/**
 * ZySelect — Select/Dropdown do Design System
 */

import { computed } from 'vue';

interface Option {
    value: string | number;
    label: string;
    disabled?: boolean;
}

interface Props {
    modelValue?: string | number;
    options: Option[];
    label?: string;
    placeholder?: string;
    error?: string;
    hint?: string;
    disabled?: boolean;
    required?: boolean;
    id?: string;
    name?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    required: false,
    placeholder: 'Selecione...',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const inputId = computed(() => props.id || `zy-select-${Math.random().toString(36).slice(2, 9)}`);
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="inputId" class="block text-body-sm font-medium text-[var(--zy-text-primary)] mb-1">
            {{ label }}
            <span v-if="required" class="text-danger-500 ml-0.5">*</span>
        </label>

        <select
            :id="inputId"
            :name="name"
            :value="modelValue"
            :disabled="disabled"
            :required="required"
            :class="[
                'block w-full rounded-zy border px-3 py-2 text-body-sm transition-colors duration-200 appearance-none',
                'bg-no-repeat bg-right pr-10',
                'focus:outline-none focus:ring-2 focus:ring-offset-0',
                error
                    ? 'border-danger-500 focus:ring-danger-500 dark:border-danger-400'
                    : 'border-dark-300 focus:border-primary-500 focus:ring-primary-500 dark:border-dark-600 dark:text-dark-100',
                disabled ? 'bg-dark-100 cursor-not-allowed opacity-60 dark:bg-dark-900' : 'bg-white dark:bg-dark-800',
            ]"
            :aria-invalid="!!error"
            @change="emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
        >
            <option value="" disabled>{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                :disabled="option.disabled"
            >
                {{ option.label }}
            </option>
        </select>

        <p v-if="error" class="mt-1 text-caption text-danger-600 dark:text-danger-400" role="alert">
            {{ error }}
        </p>
        <p v-else-if="hint" class="mt-1 text-caption text-dark-500">
            {{ hint }}
        </p>
    </div>
</template>
