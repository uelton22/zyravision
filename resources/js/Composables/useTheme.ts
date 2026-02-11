/**
 * useTheme — Composable para gerenciar tema claro/escuro
 */

import { ref, watch, onMounted } from 'vue';
import { useLocalStorage } from '@vueuse/core';

type Theme = 'light' | 'dark' | 'system';

const theme = useLocalStorage<Theme>('zyravision-theme', 'system');
const isDark = ref(false);

export function useTheme() {
    function applyTheme() {
        if (theme.value === 'dark' || (theme.value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            isDark.value = true;
        } else {
            document.documentElement.classList.remove('dark');
            isDark.value = false;
        }
    }

    function setTheme(newTheme: Theme) {
        theme.value = newTheme;
        applyTheme();
    }

    function toggleTheme() {
        setTheme(isDark.value ? 'light' : 'dark');
    }

    onMounted(() => {
        applyTheme();

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (theme.value === 'system') {
                applyTheme();
            }
        });
    });

    watch(theme, applyTheme);

    return {
        theme,
        isDark,
        setTheme,
        toggleTheme,
    };
}
