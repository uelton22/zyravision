<script setup lang="ts">
/**
 * ZyHeader — Cabeçalho da aplicação autenticada do Design System
 */

import { router } from '@inertiajs/vue3';
import { Bars3Icon, MoonIcon, SunIcon, BellIcon } from '@heroicons/vue/24/outline';
import ZyAvatar from './ZyAvatar.vue';
import ZyDropdown from './ZyDropdown.vue';
import { useTheme } from '../Composables/useTheme';

interface Props {
    user?: {
        name: string;
        email: string;
        avatar_url?: string;
    };
}

defineProps<Props>();

const emit = defineEmits<{
    'toggle-sidebar': [];
}>();

const { isDark, toggleTheme } = useTheme();

function logout() {
    router.post('/logout');
}
</script>

<template>
    <header class="flex items-center h-16 px-4 sm:px-6 bg-white dark:bg-dark-800 border-b border-dark-200 dark:border-dark-700">
        <!-- Mobile menu button -->
        <button
            type="button"
            class="lg:hidden p-2 rounded-zy text-dark-500 hover:bg-dark-100 dark:hover:bg-dark-700 transition-colors mr-2"
            aria-label="Abrir menu"
            @click="emit('toggle-sidebar')"
        >
            <Bars3Icon class="h-6 w-6" />
        </button>

        <!-- Spacer -->
        <div class="flex-1" />

        <!-- Right actions -->
        <div class="flex items-center gap-2">
            <!-- Theme toggle -->
            <button
                type="button"
                class="p-2 rounded-zy text-dark-500 hover:bg-dark-100 dark:hover:bg-dark-700 transition-colors"
                :aria-label="isDark ? 'Ativar tema claro' : 'Ativar tema escuro'"
                @click="toggleTheme"
            >
                <MoonIcon v-if="!isDark" class="h-5 w-5" />
                <SunIcon v-else class="h-5 w-5" />
            </button>

            <!-- Notifications -->
            <button
                type="button"
                class="relative p-2 rounded-zy text-dark-500 hover:bg-dark-100 dark:hover:bg-dark-700 transition-colors"
                aria-label="Notificações"
            >
                <BellIcon class="h-5 w-5" />
                <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-danger-500" />
            </button>

            <!-- User dropdown -->
            <ZyDropdown
                v-if="user"
                :items="[
                    { label: 'Meu Perfil', href: '/profile' },
                    { label: 'Configurações', href: '/settings' },
                    { divider: true, label: '' },
                    { label: 'Sair', action: logout, danger: true },
                ]"
            >
                <template #trigger>
                    <button
                        type="button"
                        class="flex items-center gap-2 p-1 rounded-zy hover:bg-dark-50 dark:hover:bg-dark-700 transition-colors"
                    >
                        <ZyAvatar :name="user.name" :src="user.avatar_url" size="sm" />
                        <span class="hidden sm:block text-body-sm font-medium text-[var(--zy-text-primary)]">
                            {{ user.name }}
                        </span>
                    </button>
                </template>
            </ZyDropdown>
        </div>
    </header>
</template>
