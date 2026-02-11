import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@components': resolve(__dirname, 'resources/js/Components'),
            '@layouts': resolve(__dirname, 'resources/js/Layouts'),
            '@pages': resolve(__dirname, 'resources/js/Pages'),
            '@composables': resolve(__dirname, 'resources/js/Composables'),
            '@types': resolve(__dirname, 'resources/js/Types'),
            '@design': resolve(__dirname, 'resources/js/DesignSystem'),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
        },
    },
});
