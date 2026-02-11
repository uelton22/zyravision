<script setup lang="ts">
/**
 * GuestLayout — Layout para páginas públicas (login, register, etc.)
 */

import { ZyToast } from '@design';
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-dark-50 dark:bg-dark-950 px-4">
        <!-- Logo -->
        <div class="mb-8 text-center">
            <div class="inline-flex items-center gap-3">
                <div class="h-12 w-12 rounded-zy-lg bg-primary-600 flex items-center justify-center">
                    <span class="text-white font-bold text-xl">ZV</span>
                </div>
                <span class="text-display font-bold text-[var(--zy-text-primary)]">ZyraVision</span>
            </div>
            <p class="mt-2 text-body-sm text-[var(--zy-text-secondary)]">
                Plataforma Profissional de Monitoramento CFTV
            </p>
        </div>

        <!-- Content Card -->
        <div class="w-full max-w-md rounded-zy-lg bg-white dark:bg-dark-800 shadow-zy-lg border border-dark-200 dark:border-dark-700 p-6 sm:p-8">
            <slot />
        </div>

        <!-- Footer -->
        <p class="mt-8 text-caption text-dark-500">
            &copy; {{ new Date().getFullYear() }} ZyraVision. Todos os direitos reservados.
        </p>

        <ZyToast />
    </div>
</template>
