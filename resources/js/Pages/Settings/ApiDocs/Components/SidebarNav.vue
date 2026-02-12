<script setup lang="ts">

interface Group {
    group: string;
    endpoints: any[];
}

interface Props {
    groups: Group[];
    activeGroup: string;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'select', group: string): void;
}>();

const methodCounts = (endpoints: any[]): Record<string, number> => {
    const counts: Record<string, number> = {};
    for (const ep of endpoints) {
        counts[ep.method] = (counts[ep.method] || 0) + 1;
    }
    return counts;
};
</script>

<template>
    <nav class="space-y-1">
        <button
            v-for="group in groups"
            :key="group.group"
            class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-lg transition-colors"
            :class="activeGroup === group.group
                ? 'bg-[var(--zy-primary)] text-white'
                : 'text-[var(--zy-text-primary)] hover:bg-[var(--zy-surface-secondary)]'"
            @click="emit('select', group.group)"
        >
            <span class="font-medium">{{ group.group }}</span>
            <span
                class="text-xs px-1.5 py-0.5 rounded"
                :class="activeGroup === group.group ? 'bg-white/20' : 'bg-[var(--zy-surface-secondary)]'"
            >
                {{ group.endpoints.length }}
            </span>
        </button>
    </nav>
</template>
