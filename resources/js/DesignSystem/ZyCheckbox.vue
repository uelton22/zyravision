<script setup lang="ts">
/**
 * ZyCheckbox — Checkbox do Design System
 */

import { computed } from 'vue';

interface Props {
    modelValue?: boolean;
    label?: string;
    description?: string;
    disabled?: boolean;
    id?: string;
    name?: string;
}

const props = withDefaults(defineProps<Props>(), {
    disabled: false,
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const inputId = computed(() => props.id || `zy-checkbox-${Math.random().toString(36).slice(2, 9)}`);
</script>

<template>
    <div class="flex items-start gap-3">
        <input
            :id="inputId"
            type="checkbox"
            :name="name"
            :checked="modelValue"
            :disabled="disabled"
            class="h-4 w-4 rounded border-dark-300 text-primary-600 focus:ring-primary-500 mt-0.5
                   dark:border-dark-600 dark:bg-dark-800 dark:checked:bg-primary-500"
            @change="emit('update:modelValue', ($event.target as HTMLInputElement).checked)"
        />
        <div v-if="label || description">
            <label :for="inputId" class="text-body-sm font-medium text-[var(--zy-text-primary)] cursor-pointer">
                {{ label }}
            </label>
            <p v-if="description" class="text-caption text-dark-500">{{ description }}</p>
        </div>
    </div>
</template>
