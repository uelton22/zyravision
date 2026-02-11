<script setup lang="ts">
/**
 * ZySkeleton — Loading skeleton placeholder do Design System
 */

interface Props {
    variant?: 'text' | 'circular' | 'rectangular' | 'rounded';
    width?: string;
    height?: string;
    lines?: number;
}

withDefaults(defineProps<Props>(), {
    variant: 'text',
    lines: 1,
});

const variantClasses: Record<string, string> = {
    text: 'h-4 rounded',
    circular: 'rounded-full',
    rectangular: 'rounded-none',
    rounded: 'rounded-zy-md',
};
</script>

<template>
    <div v-if="variant === 'text' && lines > 1" class="space-y-2">
        <div
            v-for="i in lines"
            :key="i"
            :class="[
                'animate-pulse bg-dark-200 dark:bg-dark-700',
                variantClasses.text,
            ]"
            :style="{
                width: i === lines ? '75%' : width || '100%',
                height: height || undefined,
            }"
        />
    </div>
    <div
        v-else
        :class="[
            'animate-pulse bg-dark-200 dark:bg-dark-700',
            variantClasses[variant],
        ]"
        :style="{
            width: width || (variant === 'circular' ? '2.5rem' : '100%'),
            height: height || (variant === 'circular' ? '2.5rem' : variant === 'text' ? '1rem' : '8rem'),
        }"
    />
</template>
