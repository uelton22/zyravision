/// <reference types="vite/client" />

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<{}, {}, any>;
    export default component;
}

declare module 'ziggy-js' {
    export interface Config {
        url: string;
        port: number | null;
        defaults: Record<string, string>;
        routes: Record<string, any>;
        location: string;
    }

    export default function route(
        name?: string,
        params?: any,
        absolute?: boolean,
        config?: Config,
    ): string;
}
