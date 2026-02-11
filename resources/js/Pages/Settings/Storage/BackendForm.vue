<script setup lang="ts">
/**
 * BackendForm — Formulario dinamico para criar/editar backends de armazenamento
 * Wizard: 1. Selecionar Driver → 2. Configurar → 3. Salvar
 */

import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ZyButton, ZyInput, ZySelect, ZySwitch, ZyTextarea, ZyBadge } from '@design';
import {
    FolderIcon, ServerStackIcon, CloudIcon, Cog6ToothIcon,
    ArrowLeftIcon, ArrowRightIcon, CheckIcon,
} from '@heroicons/vue/24/outline';

interface ConfigField {
    key: string;
    label: string;
    type: string;
    required: boolean;
    placeholder?: string;
    options?: string[];
    description?: string;
}

interface DriverDef {
    value: string;
    label: string;
    category: string;
    config_fields: ConfigField[];
}

interface TenantOption {
    id: string;
    name: string;
}

interface BackendItem {
    id: string;
    tenant_id: string | null;
    name: string;
    driver: string;
    driver_label: string;
    driver_category: string;
    is_active: boolean;
    is_default: boolean;
    priority: number;
    safe_config: Record<string, any>;
}

interface Props {
    backend?: BackendItem | null;
    drivers: DriverDef[];
    tenants?: TenantOption[];
    isSuperAdmin?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    backend: null,
    tenants: () => [],
    isSuperAdmin: false,
});

const emit = defineEmits<{
    saved: [];
    cancel: [];
}>();

const isEditing = computed(() => !!props.backend);
const step = ref(isEditing.value ? 2 : 1);

// ─── Driver Selection ──────────────────────────────────
const selectedDriver = ref(props.backend?.driver ?? '');

const driversByCategory = computed(() => {
    const categories: Record<string, { label: string; icon: any; drivers: DriverDef[] }> = {
        local: { label: 'Armazenamento Local', icon: FolderIcon, drivers: [] },
        network: { label: 'Armazenamento em Rede', icon: ServerStackIcon, drivers: [] },
        cloud: { label: 'Armazenamento em Nuvem', icon: CloudIcon, drivers: [] },
        other: { label: 'Outros', icon: Cog6ToothIcon, drivers: [] },
    };

    props.drivers.forEach((d) => {
        const cat = d.category || 'other';
        if (categories[cat]) {
            categories[cat].drivers.push(d);
        } else {
            categories.other.drivers.push(d);
        }
    });

    return Object.entries(categories).filter(([, c]) => c.drivers.length > 0);
});

const selectedDriverDef = computed(() => {
    return props.drivers.find((d) => d.value === selectedDriver.value);
});

const configFields = computed(() => {
    return selectedDriverDef.value?.config_fields ?? [];
});

// ─── Form ──────────────────────────────────────────────
const form = useForm({
    name: props.backend?.name ?? '',
    driver: props.backend?.driver ?? '',
    is_active: props.backend?.is_active ?? true,
    is_default: props.backend?.is_default ?? false,
    priority: props.backend?.priority ?? 0,
    config: { ...(props.backend?.safe_config ?? {}) } as Record<string, any>,
    tenant_id: props.backend?.tenant_id ?? null as string | null,
});

// Tenant options for select (with a "Global" option for super admin)
const tenantSelectOptions = computed(() => {
    const opts: { value: string | null; label: string }[] = [
        { value: '', label: 'Global (Sistema)' },
    ];
    props.tenants.forEach((t) => {
        opts.push({ value: t.id, label: t.name });
    });
    return opts;
});

// When driver changes, update form and reset config
watch(selectedDriver, (newDriver) => {
    if (!isEditing.value) {
        form.driver = newDriver;
        form.config = {};
    }
});

// ─── Driver Category Icons ─────────────────────────────
function getCategoryIcon(category: string) {
    switch (category) {
        case 'local': return FolderIcon;
        case 'network': return ServerStackIcon;
        case 'cloud': return CloudIcon;
        default: return Cog6ToothIcon;
    }
}

function getDriverEmoji(driver: string): string {
    const map: Record<string, string> = {
        local: '💾', lvm: '💿', nas: '🗄️', nfs: '📁', cifs: '📂',
        sftp: '🔒', ftp: '📤', cdntv: '📡', iscsi: '🔌',
        minio: '🪣', s3: '☁️', azure_blob: '☁️', gcs: '☁️',
        webdav: '🌐', custom: '⚙️',
    };
    return map[driver] ?? '📦';
}

// ─── Navigation ────────────────────────────────────────
function goToStep(n: number) {
    step.value = n;
}

function selectDriver(driver: string) {
    selectedDriver.value = driver;
    step.value = 2;
}

// ─── Submission ────────────────────────────────────────
function submit() {
    form.driver = selectedDriver.value;

    // Normalize tenant_id: empty string → null (global)
    if (!form.tenant_id) {
        form.tenant_id = null;
    }

    if (isEditing.value && props.backend) {
        form.put(`/settings/storage/${props.backend.id}`, {
            onSuccess: () => emit('saved'),
        });
    } else {
        form.post('/settings/storage', {
            onSuccess: () => emit('saved'),
        });
    }
}

// Priority options
const priorityOptions = [
    { value: 0, label: '0 - Mais alta' },
    { value: 1, label: '1' },
    { value: 2, label: '2' },
    { value: 3, label: '3' },
    { value: 5, label: '5 - Media' },
    { value: 10, label: '10 - Baixa' },
    { value: 99, label: '99 - Backup' },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Step Indicators -->
        <div class="flex items-center gap-2 px-1">
            <button
                v-for="(s, idx) in [
                    { num: 1, label: 'Driver' },
                    { num: 2, label: 'Configurar' },
                ]"
                :key="s.num"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-body-sm font-medium transition-colors"
                :class="step === s.num ? 'bg-primary-500 text-white' : step > s.num ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-dark-100 text-dark-500 dark:bg-dark-800'"
                @click="s.num < step || isEditing ? goToStep(s.num) : null"
            >
                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold" :class="step > s.num ? 'bg-primary-500 text-white' : ''">
                    <CheckIcon v-if="step > s.num" class="h-3 w-3" />
                    <span v-else>{{ s.num }}</span>
                </span>
                {{ s.label }}
            </button>
        </div>

        <!-- Step 1: Select Driver -->
        <div v-if="step === 1" class="space-y-6">
            <p class="text-body-sm text-dark-500">Selecione o tipo de armazenamento que deseja configurar:</p>

            <div v-for="[category, group] in driversByCategory" :key="category" class="space-y-2">
                <div class="flex items-center gap-2">
                    <component :is="group.icon" class="h-4 w-4 text-dark-400" />
                    <h4 class="text-body-sm font-semibold text-dark-500">{{ group.label }}</h4>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    <button
                        v-for="driver in group.drivers"
                        :key="driver.value"
                        class="flex items-center gap-2.5 p-3 rounded-lg border-2 transition-all text-left"
                        :class="selectedDriver === driver.value
                            ? 'border-primary-500 bg-primary-50 text-dark-800 dark:bg-dark-700 dark:border-primary-500 dark:text-[var(--zy-text-primary)]'
                            : 'border-dark-200 dark:border-dark-700 hover:border-primary-300 dark:hover:border-primary-700 hover:bg-dark-50 dark:hover:bg-dark-800 text-[var(--zy-text-primary)]'"
                        @click="selectDriver(driver.value)"
                    >
                        <span class="text-xl flex-shrink-0">{{ getDriverEmoji(driver.value) }}</span>
                        <span class="text-body-sm font-medium leading-tight">{{ driver.label }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 2: Configure -->
        <div v-if="step === 2" class="space-y-5">
            <!-- Selected driver info (tema claro: fundo suave; tema escuro: cinza medio, sem branco) -->
            <div class="flex items-center gap-2 p-3 rounded-lg bg-primary-50 text-dark-800 dark:bg-dark-700 dark:text-[var(--zy-text-primary)] border border-dark-200 dark:border-dark-600">
                <span class="text-xl">{{ getDriverEmoji(selectedDriver) }}</span>
                <div>
                    <p class="text-body-sm font-semibold">{{ selectedDriverDef?.label }}</p>
                    <p class="text-[11px] text-dark-500 dark:text-dark-400">{{ selectedDriverDef?.category === 'local' ? 'Armazenamento Local' : selectedDriverDef?.category === 'network' ? 'Rede' : selectedDriverDef?.category === 'cloud' ? 'Nuvem' : 'Outro' }}</p>
                </div>
                <div class="flex-1" />
                <ZyButton v-if="!isEditing" variant="ghost" size="sm" @click="step = 1">
                    <ArrowLeftIcon class="h-3.5 w-3.5 mr-1" />
                    Trocar
                </ZyButton>
            </div>

            <!-- General Settings -->
            <div class="space-y-4">
                <h4 class="text-body font-semibold text-[var(--zy-text-primary)]">Configuracoes Gerais</h4>
                <div class="grid sm:grid-cols-2 gap-4">
                    <ZyInput
                        v-model="form.name"
                        label="Nome do Backend"
                        placeholder="Ex: NAS Principal, S3 Backup"
                        :error="form.errors.name"
                        required
                    />
                    <ZySelect
                        v-model="form.priority"
                        :options="priorityOptions"
                        label="Prioridade (failover)"
                        :error="form.errors.priority"
                    />
                </div>
                <!-- Tenant selector (super admin only) -->
                <div v-if="isSuperAdmin && tenants.length > 0">
                    <ZySelect
                        v-model="form.tenant_id"
                        :options="tenantSelectOptions"
                        label="Empresa (Tenant)"
                        description="Deixe 'Global' para disponibilizar a todas as empresas"
                        :error="form.errors.tenant_id"
                    />
                </div>
                <div class="flex gap-6">
                    <ZySwitch v-model="form.is_active" label="Ativo" description="Backend habilitado para uso" />
                    <ZySwitch v-model="form.is_default" label="Padrao" description="Usar como backend principal" />
                </div>
            </div>

            <!-- Driver-specific Config Fields -->
            <div v-if="configFields.length > 0" class="space-y-4">
                <h4 class="text-body font-semibold text-[var(--zy-text-primary)]">Configuracao do Driver</h4>

                <div class="space-y-3">
                    <template v-for="field in configFields" :key="field.key">
                        <!-- Text Input -->
                        <ZyInput
                            v-if="field.type === 'text'"
                            v-model="form.config[field.key]"
                            :label="field.label"
                            :placeholder="field.placeholder"
                            :required="field.required"
                            :error="(form.errors as any)[`config.${field.key}`]"
                        />

                        <!-- Password Input -->
                        <ZyInput
                            v-else-if="field.type === 'password'"
                            v-model="form.config[field.key]"
                            type="password"
                            :label="field.label"
                            :placeholder="isEditing ? 'Deixe vazio para manter atual' : field.placeholder"
                            :required="field.required && !isEditing"
                            :error="(form.errors as any)[`config.${field.key}`]"
                        />

                        <!-- Number Input -->
                        <ZyInput
                            v-else-if="field.type === 'number'"
                            v-model.number="form.config[field.key]"
                            type="number"
                            :label="field.label"
                            :placeholder="field.placeholder"
                            :required="field.required"
                            :error="(form.errors as any)[`config.${field.key}`]"
                        />

                        <!-- Select Input -->
                        <ZySelect
                            v-else-if="field.type === 'select'"
                            v-model="form.config[field.key]"
                            :label="field.label"
                            :options="(field.options ?? []).map((o: string) => ({ value: o, label: o }))"
                            :required="field.required"
                            :error="(form.errors as any)[`config.${field.key}`]"
                        />

                        <!-- Textarea -->
                        <ZyTextarea
                            v-else-if="field.type === 'textarea'"
                            v-model="form.config[field.key]"
                            :label="field.label"
                            :placeholder="field.placeholder"
                            :required="field.required"
                            :error="(form.errors as any)[`config.${field.key}`]"
                            rows="4"
                        />

                        <!-- Boolean Switch -->
                        <ZySwitch
                            v-else-if="field.type === 'boolean'"
                            v-model="form.config[field.key]"
                            :label="field.label"
                        />
                    </template>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-dark-200 dark:border-dark-700">
                <ZyButton variant="secondary" @click="emit('cancel')">Cancelar</ZyButton>
                <ZyButton @click="submit" :loading="form.processing" :disabled="!form.name || !selectedDriver">
                    <CheckIcon class="h-4 w-4 mr-1.5" />
                    {{ isEditing ? 'Salvar Alteracoes' : 'Criar Backend' }}
                </ZyButton>
            </div>
        </div>
    </div>
</template>
