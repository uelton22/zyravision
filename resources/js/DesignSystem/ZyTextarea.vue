<script setup lang="ts">
/**
 * ZyTextarea — Campo de texto multiline do Design System
 */

import { computed } from 'vue';

interface Props {
    modelValue?: string;
    label?: string;
    placeholder?: string;
    error?: string;
    hint?: string;
    disabled?: boolean;
    required?: boolean;
    id?: string;
    name?: string;
    rows?: number;
    maxLength?: number;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
    required: false,
    rows: 4,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const inputId = computed(() => props.id || `zy-textarea-${Math.random().toString(36).slice(2, 9)}`);
</script>

<template>
    <div class="w-full">
        <label v-if="label" :for="inputId" class="block text-body-sm font-medium text-[var(--zy-text-primary)] mb-1">
            {{ label }}
            <span v-if="required" class="text-danger-500 ml-0.5">*</span>
        </label>

        <textarea
            :id="inputId"
            :name="name"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :rows="rows"
            :maxlength="maxLength"
            :class="[
                'block w-full rounded-zy border px-3 py-2 text-body-sm transition-colors duration-200',
                'placeholder:text-dark-400 dark:placeholder:text-dark-500 resize-y',
                'focus:outline-none focus:ring-2 focus:ring-offset-0',
                error
                    ? 'border-danger-500 focus:ring-danger-500 dark:border-danger-400'
                    : 'border-dark-300 focus:border-primary-500 focus:ring-primary-500 dark:border-dark-600 dark:bg-dark-800 dark:text-dark-100',
                disabled ? 'bg-dark-100 cursor-not-allowed opacity-60 dark:bg-dark-900' : 'bg-white dark:bg-dark-800',
            ]"
            :aria-invalid="!!error"
            :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
            @input="emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
        />

        <div class="flex justify-between mt-1">
            <p v-if="error" :id="`${inputId}-error`" class="text-caption text-danger-600 dark:text-danger-400" role="alert">
                {{ error }}
            </p>
            <p v-else-if="hint" :id="`${inputId}-hint`" class="text-caption text-dark-500">
                {{ hint }}
            </p>
            <span v-if="maxLength" class="text-caption text-dark-400">
                {{ (modelValue || '').length }}/{{ maxLength }}
            </span>
        </div>
    </div>
</template>
