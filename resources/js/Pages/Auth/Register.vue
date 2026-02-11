<script setup lang="ts">
/**
 * Register Page — ZyraVision
 */

import { Head, useForm, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ZyButton, ZyInput } from '@design';
import { UserIcon, EnvelopeIcon, LockClosedIcon, BuildingOfficeIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: GuestLayout });

const form = useForm({
    name: '',
    email: '',
    company: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Cadastro" />

    <h2 class="text-heading font-bold text-[var(--zy-text-primary)] text-center mb-2">
        Crie sua conta
    </h2>
    <p class="text-body-sm text-[var(--zy-text-secondary)] text-center mb-6">
        14 dias gratuitos. Sem cartão de crédito.
    </p>

    <form @submit.prevent="submit" class="space-y-4">
        <ZyInput
            v-model="form.name"
            label="Nome completo"
            placeholder="Seu nome"
            :error="form.errors.name"
            required
            icon
        >
            <template #icon>
                <UserIcon class="h-5 w-5" />
            </template>
        </ZyInput>

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
            v-model="form.company"
            label="Empresa"
            placeholder="Nome da sua empresa"
            :error="form.errors.company"
            required
            icon
        >
            <template #icon>
                <BuildingOfficeIcon class="h-5 w-5" />
            </template>
        </ZyInput>

        <ZyInput
            v-model="form.password"
            type="password"
            label="Senha"
            placeholder="Mínimo 8 caracteres"
            :error="form.errors.password"
            required
            icon
        >
            <template #icon>
                <LockClosedIcon class="h-5 w-5" />
            </template>
        </ZyInput>

        <ZyInput
            v-model="form.password_confirmation"
            type="password"
            label="Confirmar senha"
            placeholder="Repita a senha"
            required
            icon
        >
            <template #icon>
                <LockClosedIcon class="h-5 w-5" />
            </template>
        </ZyInput>

        <ZyButton type="submit" :loading="form.processing" full-width size="lg">
            Criar conta gratuita
        </ZyButton>
    </form>

    <p class="mt-6 text-center text-body-sm text-[var(--zy-text-secondary)]">
        Já tem conta?
        <Link href="/login" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
            Entrar
        </Link>
    </p>
</template>
