<script setup lang="ts">
/**
 * ZyInput — Campo de texto do Design System ZyraVision
 */

import { computed } from 'vue';

interface Props {
    modelValue?: string | number;
    type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'search';
    label?: string;
    placeholder?: string;
    error?: string;
    hint?: string;
    disabled?: boolean;
    required?: boolean;
    id?: string;
    name?: string;
    icon?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'text',
    disabled: false,
    required: false,
    icon: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: string | number];
}>();

const inputId = computed(() => props.id || `zy-input-${Math.random().toString(36).slice(2, 9)}`);

const inputClasses = computed(() => [
    'block w-full rounded-zy border px-3 py-2 text-body-sm transition-colors duration-200',
    'placeholder:text-dark-400 dark:placeholder:text-dark-500',
    'focus:outline-none focus:ring-2 focus:ring-offset-0',
    props.error
        ? 'border-danger-500 text-danger-900 focus:ring-danger-500 dark:border-danger-400 dark:text-danger-300'
        : 'border-dark-300 text-dark-900 focus:border-primary-500 focus:ring-primary-500 dark:border-dark-600 dark:bg-dark-800 dark:text-dark-100',
    props.disabled ? 'bg-dark-100 cursor-not-allowed opacity-60 dark:bg-dark-900' : 'bg-white dark:bg-dark-800',
    props.icon ? 'pl-10' : '',
]);

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:modelValue', props.type === 'number' ? Number(target.value) : target.value);
}
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="inputId" class="block text-body-sm font-medium text-[var(--zy-text-primary)] mb-1">
            {{ label }}
            <span v-if="required" class="text-danger-500 ml-0.5">*</span>
        </label>

        <div class="relative">
            <div v-if="$slots.icon" class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-dark-400">
                <slot name="icon" />
            </div>

            <input
                :id="inputId"
                :type="type"
                :name="name"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :required="required"
                :class="inputClasses"
                :aria-invalid="!!error"
                :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
                @input="handleInput"
            />
        </div>

        <p v-if="error" :id="`${inputId}-error`" class="mt-1 text-caption text-danger-600 dark:text-danger-400" role="alert">
            {{ error }}
        </p>
        <p v-else-if="hint" :id="`${inputId}-hint`" class="mt-1 text-caption text-dark-500">
            {{ hint }}
        </p>
    </div>
</template>
