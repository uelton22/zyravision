import '../css/app.css';

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { useToast } from './Composables/useToast';

const appName = import.meta.env.VITE_APP_NAME || 'ZyraVision';

createInertiaApp({
    title: (title) => `${title} — ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        // ─── Bridge Inertia flash messages → ZyToast ───
        const { addToast } = useToast();

        router.on('finish', (event) => {
            const page = (event as any)?.detail?.visit?.completed
                ? router.page
                : null;

            if (!page) return;

            const flash = (page.props as any)?.flash;
            if (!flash) return;

            if (flash.success) {
                addToast({ type: 'success', title: 'Sucesso', message: flash.success });
            }
            if (flash.error) {
                addToast({ type: 'error', title: 'Erro', message: flash.error, duration: 8000 });
            }
            if (flash.info) {
                addToast({ type: 'info', title: 'Info', message: flash.info });
            }
            if (flash.warning) {
                addToast({ type: 'warning', title: 'Atenção', message: flash.warning, duration: 8000 });
            }
        });
    },
    progress: {
        color: '#1A73E8',
        showSpinner: true,
    },
});
