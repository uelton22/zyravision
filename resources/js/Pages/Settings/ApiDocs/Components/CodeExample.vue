<script setup lang="ts">
import { ref } from 'vue';
import { ClipboardDocumentIcon, CheckIcon } from '@heroicons/vue/24/outline';

interface Props {
    endpoint: {
        method: string;
        path: string;
        auth: boolean;
    };
    baseUrl: string;
}

const props = defineProps<Props>();

const activeTab = ref('curl');
const copied = ref(false);

const tabs = [
    { key: 'curl', label: 'cURL' },
    { key: 'javascript', label: 'JavaScript' },
    { key: 'dart', label: 'Dart' },
    { key: 'php', label: 'PHP' },
];

function getCode(lang: string): string {
    const url = `${props.baseUrl}${props.endpoint.path}`;
    const method = props.endpoint.method;
    const auth = props.endpoint.auth;

    switch (lang) {
        case 'curl':
            return [
                `curl -X ${method} "${url}"`,
                auth ? '  -H "Authorization: Bearer YOUR_TOKEN"' : '',
                '  -H "Accept: application/json"',
                '  -H "Content-Type: application/json"',
            ].filter(Boolean).join(' \\\n');

        case 'javascript':
            return [
                `const response = await fetch("${url}", {`,
                `  method: "${method}",`,
                '  headers: {',
                '    "Accept": "application/json",',
                '    "Content-Type": "application/json",',
                auth ? '    "Authorization": `Bearer ${token}`,' : '',
                '  },',
                '});',
                '',
                'const data = await response.json();',
            ].filter(Boolean).join('\n');

        case 'dart':
            return [
                'import \'package:dio/dio.dart\';',
                '',
                'final dio = Dio();',
                `final response = await dio.${method.toLowerCase()}(`,
                `  "${url}",`,
                '  options: Options(headers: {',
                '    "Accept": "application/json",',
                auth ? '    "Authorization": "Bearer $token",' : '',
                '  }),',
                ');',
                '',
                'final data = response.data;',
            ].filter(Boolean).join('\n');

        case 'php':
            return [
                'use Illuminate\\Support\\Facades\\Http;',
                '',
                `$response = Http::withToken($token)`,
                '    ->acceptJson()',
                `    ->${method.toLowerCase()}("${url}");`,
                '',
                '$data = $response->json();',
            ].join('\n');

        default:
            return '';
    }
}

function copyCode() {
    navigator.clipboard.writeText(getCode(activeTab.value));
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <div class="mt-3 rounded-lg border border-[var(--zy-border-primary)] overflow-hidden">
        <!-- Tabs -->
        <div class="flex items-center justify-between bg-[var(--zy-surface-secondary)] border-b border-[var(--zy-border-primary)] px-3 py-1.5">
            <div class="flex gap-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="px-2.5 py-1 text-xs rounded transition-colors"
                    :class="activeTab === tab.key
                        ? 'bg-[var(--zy-primary)] text-white'
                        : 'text-[var(--zy-text-muted)] hover:text-[var(--zy-text-primary)]'"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </button>
            </div>
            <button
                class="p-1 rounded hover:bg-[var(--zy-surface-primary)] transition-colors"
                @click="copyCode"
                :title="copied ? 'Copiado!' : 'Copiar'"
            >
                <CheckIcon v-if="copied" class="h-4 w-4 text-green-500" />
                <ClipboardDocumentIcon v-else class="h-4 w-4 text-[var(--zy-text-muted)]" />
            </button>
        </div>

        <!-- Code -->
        <pre class="p-3 text-xs font-mono overflow-x-auto bg-[var(--zy-surface-primary)] text-[var(--zy-text-primary)]"><code>{{ getCode(activeTab) }}</code></pre>
    </div>
</template>
