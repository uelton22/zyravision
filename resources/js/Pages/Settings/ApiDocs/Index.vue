<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyPageHeader, ZyBreadcrumb, ZyCard, ZyAlert } from '@design';
import { CodeBracketIcon } from '@heroicons/vue/24/outline';
import SidebarNav from './Components/SidebarNav.vue';
import EndpointCard from './Components/EndpointCard.vue';

defineOptions({ layout: AuthenticatedLayout });

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

interface EndpointGroup {
    group: string;
    endpoints: Endpoint[];
}

interface Props {
    baseUrl: string;
    endpoints: EndpointGroup[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Configuracoes' },
    { label: 'Documentacao API' },
];

const activeGroup = ref(props.endpoints[0]?.group ?? '');

const currentEndpoints = computed(() => {
    if (!activeGroup.value) return props.endpoints;
    const group = props.endpoints.find((g) => g.group === activeGroup.value);
    return group ? [group] : props.endpoints;
});

const totalEndpoints = computed(() =>
    props.endpoints.reduce((sum, g) => sum + g.endpoints.length, 0)
);
</script>

<template>
    <Head title="Documentacao API" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <ZyPageHeader
        title="Documentacao da API"
        :description="`API REST v1 — ${totalEndpoints} endpoints disponiveis`"
    >
        <template #actions>
            <div class="flex items-center gap-2 text-sm text-[var(--zy-text-muted)]">
                <CodeBracketIcon class="h-5 w-5" />
                <code>{{ baseUrl }}</code>
            </div>
        </template>
    </ZyPageHeader>

    <!-- Quick Start -->
    <ZyAlert variant="info" class="mb-6">
        <strong>Inicio rapido:</strong> Obtenha um token via
        <code class="mx-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/20 rounded text-xs">POST /api/v1/auth/login</code>
        e inclua o header <code class="mx-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/20 rounded text-xs">Authorization: Bearer SEU_TOKEN</code>
        em todas as requisicoes autenticadas. Formato de resposta padrao:
        <code class="mx-1 px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900/20 rounded text-xs">{"data": {...}, "meta": {"api_version": "v1"}}</code>
    </ZyAlert>

    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Sidebar -->
        <div class="lg:w-56 shrink-0">
            <ZyCard padding="sm" class="lg:sticky lg:top-6">
                <h3 class="text-xs font-semibold text-[var(--zy-text-muted)] uppercase tracking-wider mb-3 px-3">
                    Recursos
                </h3>
                <SidebarNav
                    :groups="endpoints"
                    :active-group="activeGroup"
                    @select="activeGroup = $event"
                />
            </ZyCard>
        </div>

        <!-- Content -->
        <div class="flex-1 space-y-8">
            <div v-for="group in currentEndpoints" :key="group.group">
                <h2 class="text-lg font-semibold text-[var(--zy-text-primary)] mb-4" :id="group.group">
                    {{ group.group }}
                </h2>
                <div class="space-y-3">
                    <EndpointCard
                        v-for="endpoint in group.endpoints"
                        :key="`${endpoint.method}-${endpoint.path}`"
                        :endpoint="endpoint"
                        :base-url="baseUrl"
                    />
                </div>
            </div>

            <!-- Response Format Section -->
            <ZyCard padding="lg">
                <h2 class="text-lg font-semibold text-[var(--zy-text-primary)] mb-4">Formato de Resposta</h2>

                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-[var(--zy-text-primary)] mb-2">Recurso unico</h3>
                        <pre class="p-3 rounded-lg bg-[var(--zy-surface-secondary)] text-xs font-mono overflow-x-auto"><code>{
  "data": { "id": "...", "name": "..." },
  "meta": { "api_version": "v1" }
}</code></pre>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-[var(--zy-text-primary)] mb-2">Colecao paginada</h3>
                        <pre class="p-3 rounded-lg bg-[var(--zy-surface-secondary)] text-xs font-mono overflow-x-auto"><code>{
  "data": [{ ... }, { ... }],
  "meta": { "api_version": "v1", "current_page": 1, "per_page": 15, "total": 42, "last_page": 3 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}</code></pre>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-[var(--zy-text-primary)] mb-2">Erro</h3>
                        <pre class="p-3 rounded-lg bg-[var(--zy-surface-secondary)] text-xs font-mono overflow-x-auto"><code>{
  "error": { "code": "VALIDATION_ERROR", "message": "...", "errors": { "field": ["msg"] } }
}</code></pre>
                    </div>
                </div>
            </ZyCard>

            <!-- Rate Limiting Section -->
            <ZyCard padding="lg">
                <h2 class="text-lg font-semibold text-[var(--zy-text-primary)] mb-4">Rate Limiting</h2>
                <div class="space-y-3 text-sm text-[var(--zy-text-primary)]">
                    <p>Todas as requisicoes da API possuem limites de taxa para proteger o servidor:</p>
                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="p-3 rounded-lg bg-[var(--zy-surface-secondary)]">
                            <p class="font-semibold">Web / SPA</p>
                            <p class="text-2xl font-bold text-[var(--zy-primary)]">120</p>
                            <p class="text-xs text-[var(--zy-text-muted)]">requisicoes / minuto</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[var(--zy-surface-secondary)]">
                            <p class="font-semibold">Mobile</p>
                            <p class="text-2xl font-bold text-[var(--zy-primary)]">60</p>
                            <p class="text-xs text-[var(--zy-text-muted)]">requisicoes / minuto</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[var(--zy-surface-secondary)]">
                            <p class="font-semibold">Background Sync</p>
                            <p class="text-2xl font-bold text-[var(--zy-primary)]">10</p>
                            <p class="text-xs text-[var(--zy-text-muted)]">requisicoes / minuto</p>
                        </div>
                    </div>
                    <p class="text-xs text-[var(--zy-text-muted)]">
                        Headers retornados: <code>X-RateLimit-Limit</code>, <code>X-RateLimit-Remaining</code>, <code>X-RateLimit-Reset</code>
                    </p>
                </div>
            </ZyCard>

            <!-- WebSocket Section -->
            <ZyCard padding="lg">
                <h2 class="text-lg font-semibold text-[var(--zy-text-primary)] mb-4">WebSocket (Tempo Real)</h2>
                <div class="space-y-3 text-sm text-[var(--zy-text-primary)]">
                    <p>Conecte-se via WebSocket para receber eventos em tempo real usando Laravel Echo.</p>

                    <div>
                        <h3 class="font-medium mb-2">Canais disponiveis</h3>
                        <div class="space-y-2">
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--zy-surface-secondary)]">
                                <code class="text-xs bg-purple-100 dark:bg-purple-900/30 px-2 py-0.5 rounded text-purple-700 dark:text-purple-400 shrink-0">private-tenant.{id}</code>
                                <span class="text-xs text-[var(--zy-text-muted)]">Eventos de cameras, streams, gravacoes e armazenamento do tenant</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--zy-surface-secondary)]">
                                <code class="text-xs bg-purple-100 dark:bg-purple-900/30 px-2 py-0.5 rounded text-purple-700 dark:text-purple-400 shrink-0">private-camera.{id}</code>
                                <span class="text-xs text-[var(--zy-text-muted)]">Eventos especificos de uma camera (gravacao, heartbeat)</span>
                            </div>
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-[var(--zy-surface-secondary)]">
                                <code class="text-xs bg-purple-100 dark:bg-purple-900/30 px-2 py-0.5 rounded text-purple-700 dark:text-purple-400 shrink-0">private-user.{id}</code>
                                <span class="text-xs text-[var(--zy-text-muted)]">Notificacoes pessoais e revogacao de dispositivo</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-medium mb-2">Eventos</h3>
                        <pre class="p-3 rounded-lg bg-[var(--zy-surface-secondary)] text-xs font-mono overflow-x-auto"><code>stream.started   — Stream iniciado (camera_id, hls_url)
stream.failed    — Stream com erro (camera_id, error_message)
recording.started — Gravacao iniciada (camera_id, recording_id)
recording.stopped — Gravacao parada (camera_id)
camera.status.changed — Camera online/offline (is_online, is_recording)
storage.threshold — Armazenamento > 90% (usage_percentage)</code></pre>
                    </div>
                </div>
            </ZyCard>
        </div>
    </div>
</template>
