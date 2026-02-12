<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ZyButton, ZyCard, ZySelect, ZyInput, ZySwitch, ZyAlert, ZyBreadcrumb } from '@design';
import { useToast } from '@/Composables/useToast';
import {
    VideoCameraIcon,
    ClockIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

const { addToast } = useToast();

interface SelectOption {
    value: string | number;
    label: string;
}

interface Props {
    settings: {
        id: string | null;
        segment_duration_seconds: number;
        continuous_recording_default: boolean;
        retention_days: number;
        cleanup_local_after_upload: boolean;
    };
    segmentOptions: SelectOption[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { label: 'Inicio', href: '/dashboard' },
    { label: 'Configuracoes' },
    { label: 'Gravacao' },
];

const form = useForm({
    segment_duration_seconds: props.settings.segment_duration_seconds,
    continuous_recording_default: props.settings.continuous_recording_default,
    retention_days: props.settings.retention_days,
    cleanup_local_after_upload: props.settings.cleanup_local_after_upload,
});

function submit() {
    // Transform types before sending (selects emit strings)
    form.transform((data) => ({
        ...data,
        segment_duration_seconds: Number(data.segment_duration_seconds),
        retention_days: Number(data.retention_days),
        continuous_recording_default: Boolean(data.continuous_recording_default),
        cleanup_local_after_upload: Boolean(data.cleanup_local_after_upload),
    })).put(route('settings.recording.update'), {
        preserveScroll: true,
        onSuccess: () => {
            addToast({
                type: 'success',
                title: 'Sucesso',
                message: 'Configurações de gravação atualizadas com sucesso.',
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            addToast({
                type: 'error',
                title: 'Erro ao salvar',
                message: typeof firstError === 'string' ? firstError : 'Verifique os campos e tente novamente.',
                duration: 8000,
            });
        },
    });
}
</script>

<template>
    <Head title="Configuracoes de Gravacao" />

    <ZyBreadcrumb :items="breadcrumbs" class="mb-4" />

    <!-- Title -->
    <div class="flex items-center gap-3 mb-6">
        <VideoCameraIcon class="h-7 w-7 text-indigo-400" />
        <div>
            <h2 class="text-xl font-semibold text-[var(--zy-text-primary)]">Configuracoes de Gravacao</h2>
            <p class="text-sm text-[var(--zy-text-muted)]">
                Configure como as cameras gravam e armazenam os segmentos de video.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-4xl space-y-6">
            <!-- Info banner -->
            <ZyAlert variant="info">
                <strong>Gravacao Continua por Segmentos:</strong> As cameras gravam continuamente em
                segmentos pequenos (ex: 1 minuto). Cada segmento completo fica disponivel quase em
                tempo real para reproducao e e automaticamente enviado ao storage backend configurado.
            </ZyAlert>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Gravacao Continua -->
                <ZyCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <VideoCameraIcon class="h-5 w-5 text-indigo-400" />
                            <h3 class="text-lg font-semibold text-white">Gravacao Continua</h3>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between rounded-lg bg-slate-800/50 p-4">
                            <div>
                                <p class="font-medium text-white">Gravacao continua por padrao</p>
                                <p class="text-sm text-slate-400">
                                    Novas cameras iniciarao a gravacao automaticamente ao ativar o stream.
                                    Sem necessidade de clicar em "Gravar".
                                </p>
                            </div>
                            <ZySwitch v-model="form.continuous_recording_default" />
                        </div>

                        <ZySelect
                            v-model="form.segment_duration_seconds"
                            :options="segmentOptions"
                            label="Duracao do Segmento"
                            hint="Segmentos menores = acesso mais rapido (quase tempo real). Segmentos maiores = menos arquivos."
                            :error="form.errors.segment_duration_seconds"
                        />

                        <div class="rounded-lg border border-slate-700 bg-slate-800/30 p-3">
                            <p class="text-xs text-slate-400">
                                <strong class="text-slate-300">Como funciona:</strong>
                                Com segmentos de {{ form.segment_duration_seconds }}s, a gravacao mais recente
                                estara disponivel em ate {{ form.segment_duration_seconds }} segundos apos ocorrer.
                                Para acesso ao vivo (streaming), use o player ao vivo da camera.
                            </p>
                        </div>
                    </div>
                </ZyCard>

                <!-- Retencao e Armazenamento -->
                <ZyCard>
                    <template #header>
                        <div class="flex items-center gap-2">
                            <ClockIcon class="h-5 w-5 text-amber-400" />
                            <h3 class="text-lg font-semibold text-white">Retencao e Armazenamento</h3>
                        </div>
                    </template>

                    <div class="space-y-4">
                        <ZyInput
                            v-model="form.retention_days"
                            type="number"
                            label="Dias de Retencao"
                            hint="Gravacoes mais antigas serao automaticamente excluidas."
                            :error="form.errors.retention_days"
                        />

                        <div class="rounded-lg border border-slate-700 bg-slate-800/30 p-3">
                            <p class="text-xs text-slate-400">
                                <strong class="text-slate-300">Estimativa:</strong>
                                Com 1 camera gravando 24h/dia em qualidade original (~500MB/hora),
                                {{ form.retention_days }} dias requerem aproximadamente
                                <strong class="text-white">
                                    {{ Math.round((500 * 24 * form.retention_days) / 1024) }} GB
                                </strong> de armazenamento.
                            </p>
                        </div>

                        <div class="flex items-center justify-between rounded-lg bg-slate-800/50 p-4">
                            <div>
                                <p class="font-medium text-white">Limpar arquivos locais apos upload</p>
                                <p class="text-sm text-slate-400">
                                    Remove os segmentos do disco local apos enviar ao storage backend padrao.
                                    Economiza espaco em disco.
                                </p>
                            </div>
                            <ZySwitch v-model="form.cleanup_local_after_upload" />
                        </div>

                        <div class="rounded-lg border border-slate-700 bg-slate-800/30 p-3">
                            <p class="text-xs text-slate-400">
                                <strong class="text-slate-300">Backend de armazenamento:</strong>
                                O backend padrao e definido em
                                <a href="/settings/storage" class="text-primary-400 hover:underline">Configuracoes → Armazenamento</a>.
                                A qualidade de gravacao e configurada individualmente em cada camera.
                            </p>
                        </div>
                    </div>
                </ZyCard>

                <!-- Salvar -->
                <div class="flex justify-end">
                    <ZyButton
                        type="submit"
                        variant="primary"
                        :disabled="form.processing"
                    >
                        <CheckCircleIcon class="mr-2 h-4 w-4" />
                        Salvar Configuracoes
                    </ZyButton>
                </div>
            </form>
        </div>
</template>
