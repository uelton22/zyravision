/**
 * useToast — Composable para notificações toast do ZyraVision
 *
 * @example
 * const { addToast } = useToast();
 * addToast({ type: 'success', title: 'Sucesso', message: 'Operação realizada!' });
 */

import { ref } from 'vue';

export interface Toast {
    id: string;
    type: 'success' | 'error' | 'warning' | 'info';
    title?: string;
    message: string;
    duration?: number;
}

const toasts = ref<Toast[]>([]);

export function useToast() {
    function addToast(toast: Omit<Toast, 'id'>) {
        const id = `toast-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;
        const duration = toast.duration ?? 5000;

        toasts.value.push({ ...toast, id });

        if (duration > 0) {
            setTimeout(() => removeToast(id), duration);
        }
    }

    function removeToast(id: string) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    function clearAll() {
        toasts.value = [];
    }

    return {
        toasts,
        addToast,
        removeToast,
        clearAll,
    };
}
