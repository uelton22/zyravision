<script setup lang="ts">
import { ref } from 'vue';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/outline';
import CodeExample from './CodeExample.vue';

interface Param {
    name: string;
    type: string;
    required: boolean;
    values?: string;
}

interface Endpoint {
    method: string;
    path: string;
    description: string;
    auth: boolean;
    params: Param[];
    response: string;
}

interface Props {
    endpoint: Endpoint;
    baseUrl: string;
}

const props = defineProps<Props>();
const expanded = ref(false);

const methodColors: Record<string, string> = {
    GET: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    POST: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    PUT: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    DELETE: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    PATCH: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
};
</script>

<template>
    <div class="border border-[var(--zy-border-primary)] rounded-lg overflow-hidden">
        <!-- Header -->
        <button
            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-[var(--zy-surface-secondary)] transition-colors"
            @click="expanded = !expanded"
        >
            <span
                class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded min-w-[52px]"
                :class="methodColors[endpoint.method] || 'bg-gray-100 text-gray-700'"
            >
                {{ endpoint.method }}
            </span>
            <code class="text-sm font-mono text-[var(--zy-text-primary)] flex-1">{{ endpoint.path }}</code>
            <span class="text-sm text-[var(--zy-text-muted)] hidden sm:inline">{{ endpoint.description }}</span>
            <ChevronDownIcon v-if="!expanded" class="h-4 w-4 text-[var(--zy-text-muted)] shrink-0" />
            <ChevronUpIcon v-else class="h-4 w-4 text-[var(--zy-text-muted)] shrink-0" />
        </button>

        <!-- Expanded Content -->
        <div v-if="expanded" class="border-t border-[var(--zy-border-primary)] px-4 py-4 space-y-4 bg-[var(--zy-surface-secondary)]">
            <p class="text-sm text-[var(--zy-text-primary)]">{{ endpoint.description }}</p>

            <!-- Auth badge -->
            <div class="flex items-center gap-2">
                <span v-if="endpoint.auth" class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                    Requer autenticacao
                </span>
                <span v-else class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600">
                    Publico
                </span>
            </div>

            <!-- Parameters -->
            <div v-if="endpoint.params.length > 0">
                <h4 class="text-xs font-semibold text-[var(--zy-text-muted)] uppercase tracking-wider mb-2">Parametros</h4>
                <div class="space-y-1">
                    <div
                        v-for="param in endpoint.params"
                        :key="param.name"
                        class="flex items-center gap-2 text-sm"
                    >
                        <code class="font-mono text-[var(--zy-primary)]">{{ param.name }}</code>
                        <span class="text-xs text-[var(--zy-text-muted)]">{{ param.type }}</span>
                        <span v-if="param.required" class="text-xs text-red-500">obrigatorio</span>
                        <span v-if="param.values" class="text-xs text-[var(--zy-text-muted)]">({{ param.values }})</span>
                    </div>
                </div>
            </div>

            <!-- Response Example -->
            <div v-if="endpoint.response">
                <h4 class="text-xs font-semibold text-[var(--zy-text-muted)] uppercase tracking-wider mb-2">Resposta</h4>
                <pre class="p-3 rounded-lg bg-[var(--zy-surface-primary)] text-xs font-mono overflow-x-auto text-[var(--zy-text-primary)]"><code>{{ endpoint.response }}</code></pre>
            </div>

            <!-- Code Examples -->
            <div>
                <h4 class="text-xs font-semibold text-[var(--zy-text-muted)] uppercase tracking-wider mb-1">Exemplo de codigo</h4>
                <CodeExample :endpoint="endpoint" :base-url="baseUrl" />
            </div>
        </div>
    </div>
</template>
