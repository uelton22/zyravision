<script setup lang="ts">
/**
 * Login Page — ZyraVision
 */

import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ZyButton, ZyInput, ZyCheckbox, ZyAlert, ZyBadge } from '@design';
import { EnvelopeIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: GuestLayout });

interface DemoUser {
    role: string;
    email: string;
    password: string;
    description?: string;
    badge?: string;
}

interface Props {
    status?: string;
    demo?: boolean;
    demoUsers?: DemoUser[];
}

const props = withDefaults(defineProps<Props>(), {
    demo: false,
    demoUsers: () => [],
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const selectedUser = ref<string | null>(null);

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}

function fillDemo(user: DemoUser) {
    form.email = user.email;
    form.password = user.password;
    selectedUser.value = user.email;
}

const badgeVariants: Record<string, 'danger' | 'primary' | 'warning' | 'secondary' | 'success'> = {
    danger: 'danger',
    primary: 'primary',
    warning: 'warning',
    secondary: 'secondary',
    success: 'success',
};
</script>

<template>
    <Head title="Entrar" />

    <h2 class="text-heading font-bold text-[var(--zy-text-primary)] text-center mb-2">
        {{ demo ? 'ZyraVision' : 'Entrar na sua conta' }}
    </h2>
    <p v-if="demo" class="text-body-sm text-[var(--zy-text-secondary)] text-center mb-6">
        Selecione um perfil para acessar a plataforma
    </p>

    <!-- Status message -->
    <ZyAlert v-if="status" variant="success" class="mb-4">{{ status }}</ZyAlert>

    <!-- Demo users -->
    <div v-if="demo && demoUsers && demoUsers.length > 0" class="mb-6 space-y-2">
        <button
            v-for="user in demoUsers"
            :key="user.email"
            type="button"
            :class="[
                'w-full flex items-center justify-between px-4 py-3 rounded-zy border transition-all text-left',
                selectedUser === user.email
                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20 ring-1 ring-primary-500'
                    : 'border-dark-200 dark:border-dark-700 hover:border-primary-300 dark:hover:border-primary-700 hover:bg-dark-50 dark:hover:bg-dark-800/50',
            ]"
            @click="fillDemo(user)"
        >
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                    <p class="text-body-sm font-semibold text-[var(--zy-text-primary)]">{{ user.role }}</p>
                    <ZyBadge v-if="user.badge" :variant="badgeVariants[user.badge] || 'secondary'" class="text-[10px]">
                        {{ user.role }}
                    </ZyBadge>
                </div>
                <p class="text-caption text-dark-500 truncate">{{ user.email }}</p>
                <p v-if="user.description" class="text-caption text-dark-400 mt-0.5">{{ user.description }}</p>
            </div>
            <span
                :class="[
                    'text-caption font-medium shrink-0 ml-3',
                    selectedUser === user.email ? 'text-primary-600 dark:text-primary-400' : 'text-dark-400',
                ]"
            >
                {{ selectedUser === user.email ? 'Selecionado' : 'Usar' }}
            </span>
        </button>
    </div>

    <!-- Login Form -->
    <form @submit.prevent="submit" class="space-y-4">
        <ZyInput
            v-model="form.email"
            type="email"
            label="E-mail"
            placeholder="seu@email.com"
            :error="form.errors.email"
            required
            icon
        >
            <template #icon>
                <EnvelopeIcon class="h-5 w-5" />
            </template>
        </ZyInput>

        <ZyInput
            v-model="form.password"
            type="password"
            label="Senha"
            placeholder="Sua senha"
            :error="form.errors.password"
            required
            icon
        >
            <template #icon>
                <LockClosedIcon class="h-5 w-5" />
            </template>
        </ZyInput>

        <div class="flex items-center justify-between">
            <ZyCheckbox v-model="form.remember" label="Lembrar de mim" />
            <Link href="#" class="text-body-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                Esqueceu a senha?
            </Link>
        </div>

        <ZyButton type="submit" :loading="form.processing" full-width size="lg">
            Entrar
        </ZyButton>
    </form>

    <p v-if="!demo" class="mt-6 text-center text-body-sm text-[var(--zy-text-secondary)]">
        Não tem conta?
        <Link href="/register" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
            Cadastre-se gratuitamente
        </Link>
    </p>
</template>
