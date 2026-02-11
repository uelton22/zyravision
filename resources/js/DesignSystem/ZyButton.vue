<script setup lang="ts">
/**
 * ZyButton — Componente de botão do Design System ZyraVision
 *
 * @example
 * <ZyButton variant="primary" size="md" @click="save">Salvar</ZyButton>
 * <ZyButton variant="danger" loading>Excluindo...</ZyButton>
 * <ZyButton variant="ghost" size="sm" aria-label="Editar"><PencilIcon class="h-5 w-5" /></ZyButton>
 */

import { computed } from 'vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline' | 'accent';
    size?: 'sm' | 'md' | 'lg';
    disabled?: boolean;
    loading?: boolean;
    type?: 'button' | 'submit' | 'reset';
    fullWidth?: boolean;
    href?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    type: 'button',
    disabled: false,
    loading: false,
    fullWidth: false,
});

const emit = defineEmits<{
    click: [event: MouseEvent];
}>();

const variantClasses = computed(() => {
    const variants: Record<string, string> = {
        primary:
            'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400',
        secondary:
            'bg-white text-dark-700 border border-dark-300 hover:bg-dark-50 dark:bg-dark-800 dark:text-dark-200 dark:border-dark-600 dark:hover:bg-dark-700',
        danger:
            'bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500 dark:bg-danger-500 dark:hover:bg-danger-400',
        ghost:
            'text-dark-600 hover:bg-dark-100 hover:text-dark-900 dark:text-dark-400 dark:hover:bg-dark-800 dark:hover:text-white',
        outline:
            'border border-primary-600 text-primary-600 hover:bg-primary-50 dark:border-primary-400 dark:text-primary-400 dark:hover:bg-primary-500/10',
        accent:
            'bg-accent-500 text-white hover:bg-accent-600 focus:ring-accent-500 dark:bg-accent-400 dark:hover:bg-accent-500',
    };
    return variants[props.variant] || variants.primary;
});

const sizeClasses = computed(() => {
    const sizes: Record<string, string> = {
        sm: 'px-3 py-1.5 text-xs',
        md: 'px-4 py-2 text-sm',
        lg: 'px-6 py-3 text-base',
    };
    return sizes[props.size] || sizes.md;
});

const isDisabled = computed(() => props.disabled || props.loading);

function handleClick(event: MouseEvent) {
    if (!isDisabled.value) {
        emit('click', event);
    }
}
</script>

<template>
    <component
        :is="href ? 'a' : 'button'"
        :type="!href ? type : undefined"
        :href="href"
        :disabled="isDisabled"
        :class="[
            'inline-flex items-center justify-center gap-2 font-medium rounded-zy transition-all duration-200',
            'focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-dark-900',
            variantClasses,
            sizeClasses,
            {
                'opacity-50 cursor-not-allowed pointer-events-none': isDisabled,
                'w-full': fullWidth,
            },
        ]"
        @click="handleClick"
    >
        <!-- Loading spinner -->
        <svg
            v-if="loading"
            class="animate-spin h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
        </svg>

        <slot />
    </component>
</template>
