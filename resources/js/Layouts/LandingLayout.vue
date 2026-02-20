<script setup lang="ts">
/**
 * LandingLayout — Layout da Landing Page pública
 * Estética industrial/refinada: Syne (display) + DM Sans (body).
 * NÃO usa AuthenticatedLayout. Focado em conversão.
 */

import { ref, onMounted, onUnmounted } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';

const isScrolled = ref(false);
const mobileMenuOpen = ref(false);

const navLinks = [
    { label: 'Recursos', href: '#recursos' },
    { label: 'Vantagens', href: '#vantagens' },
    { label: 'App Mobile', href: '#app-mobile' },
    { label: 'Preços', href: '#precos' },
    { label: 'Contato', href: '#contato' },
];

function handleScroll() {
    isScrolled.value = window.scrollY > 20;
}

function scrollToSection(href: string) {
    mobileMenuOpen.value = false;
    const el = document.querySelector(href);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <Head>
        <!-- Tipografia distinta: Syne (display) + DM Sans (body) -->
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link
            href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Syne:wght@400;500;600;700;800&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="landing-page min-h-screen bg-dark-950 font-landing-sans text-dark-100 antialiased">
        <!-- Ruído sutil de fundo global (opcional) -->
        <div
            class="pointer-events-none fixed inset-0 z-0 opacity-[0.015] dark:opacity-[0.03]"
            aria-hidden="true"
            style="background-image: url('data:image/svg+xml,%3Csvg viewBox=\'0 0 256 256\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cfilter id=\'n\'%3E%3CfeTurbulence type=\'fractalNoise\' baseFrequency=\'0.9\' numOctaves=\'4\' stitchTiles=\'stitch\'/%3E%3C/filter%3E%3Crect width=\'100%25\' height=\'100%25\' filter=\'url(%23n)\'/%3E%3C/svg%3E');"
        />

        <!-- ─── Navbar ─────────────────────────────────────────── -->
        <nav
            :class="[
                'fixed top-0 inset-x-0 z-50 transition-all duration-300',
                isScrolled
                    ? 'bg-dark-950/85 backdrop-blur-xl border-b border-dark-800/60 shadow-lg shadow-dark-950/50'
                    : 'bg-transparent',
            ]"
        >
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <Link href="/" class="flex items-center gap-3 group">
                    <div
                        class="h-9 w-9 rounded-lg bg-primary-500 flex items-center justify-center ring-2 ring-primary-400/20 group-hover:ring-primary-400/40 transition-all"
                    >
                        <span class="text-white font-landing-display font-bold text-lg">ZV</span>
                    </div>
                    <span class="font-landing-display text-heading-sm font-bold text-white">
                        Zyra<span class="text-primary-400">Vision</span>
                    </span>
                </Link>

                <div class="hidden lg:flex items-center gap-8">
                    <button
                        v-for="link in navLinks"
                        :key="link.href"
                        type="button"
                        class="text-body-sm font-medium text-dark-300 hover:text-primary-400 transition-colors duration-200"
                        @click="scrollToSection(link.href)"
                    >
                        {{ link.label }}
                    </button>
                </div>

                <div class="hidden lg:flex items-center gap-3">
                    <Link
                        href="/demo"
                        class="px-4 py-2.5 text-body-sm font-semibold border border-primary-500/60 text-primary-400 rounded-lg hover:bg-primary-500/10 hover:border-primary-400 transition-all duration-200"
                    >
                        Demonstração
                    </Link>
                    <Link
                        href="/login"
                        class="px-4 py-2.5 text-body-sm font-semibold bg-primary-500 text-white rounded-lg hover:bg-primary-400 transition-all duration-200 shadow-lg shadow-primary-500/20"
                    >
                        Entrar
                    </Link>
                </div>

                <button
                    type="button"
                    class="lg:hidden p-2.5 rounded-lg text-dark-300 hover:text-white hover:bg-dark-800 transition-colors"
                    aria-label="Abrir menu"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <Bars3Icon v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <XMarkIcon v-else class="h-6 w-6" />
                </button>
            </div>

            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="lg:hidden absolute inset-x-0 top-full bg-dark-900/98 backdrop-blur-xl border-b border-dark-800 px-4 pb-4 pt-2"
                >
                    <div class="space-y-0.5">
                        <button
                            v-for="link in navLinks"
                            :key="link.href"
                            type="button"
                            class="block w-full text-left px-4 py-3 rounded-lg text-body font-medium text-dark-200 hover:bg-dark-800 hover:text-white transition-colors"
                            @click="scrollToSection(link.href)"
                        >
                            {{ link.label }}
                        </button>
                    </div>
                    <div class="flex gap-3 mt-4 pt-4 border-t border-dark-800">
                        <Link
                            href="/demo"
                            class="flex-1 text-center px-4 py-3 text-body-sm font-semibold border border-primary-500/60 text-primary-400 rounded-lg"
                        >
                            Demonstração
                        </Link>
                        <Link
                            href="/login"
                            class="flex-1 text-center px-4 py-3 text-body-sm font-semibold bg-primary-500 text-white rounded-lg"
                        >
                            Entrar
                        </Link>
                    </div>
                </div>
            </transition>
        </nav>

        <main class="relative z-10">
            <slot />
        </main>

        <!-- ─── Footer ─────────────────────────────────────────── -->
        <footer class="relative z-10 bg-dark-900 border-t border-dark-800 text-dark-400">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                    <div class="md:col-span-1">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="h-9 w-9 rounded-lg bg-primary-500 flex items-center justify-center">
                                <span class="text-white font-landing-display font-bold text-lg">ZV</span>
                            </div>
                            <span class="font-landing-display text-heading-sm font-bold text-white">ZyraVision</span>
                        </div>
                        <p class="text-body-sm text-dark-500 leading-relaxed">
                            Plataforma profissional de monitoramento CFTV com inteligência artificial.
                        </p>
                    </div>
                    <div>
                        <h4 class="text-body-sm font-semibold text-white mb-4">Produto</h4>
                        <ul class="space-y-3 text-body-sm">
                            <li>
                                <button type="button" class="hover:text-primary-400 transition-colors" @click="scrollToSection('#recursos')">
                                    Recursos
                                </button>
                            </li>
                            <li>
                                <button type="button" class="hover:text-primary-400 transition-colors" @click="scrollToSection('#precos')">
                                    Preços
                                </button>
                            </li>
                            <li>
                                <Link href="/demo" class="hover:text-primary-400 transition-colors">Demonstração</Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-body-sm font-semibold text-white mb-4">Empresa</h4>
                        <ul class="space-y-3 text-body-sm">
                            <li><a href="#" class="hover:text-primary-400 transition-colors">Sobre Nós</a></li>
                            <li><a href="#" class="hover:text-primary-400 transition-colors">Blog</a></li>
                            <li><a href="#" class="hover:text-primary-400 transition-colors">Privacidade</a></li>
                            <li><a href="#" class="hover:text-primary-400 transition-colors">Termos de Uso</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-body-sm font-semibold text-white mb-4">Contato</h4>
                        <ul class="space-y-3 text-body-sm">
                            <li>
                                <a href="mailto:contato@ziravision.com.br" class="hover:text-primary-400 transition-colors">
                                    contato@ziravision.com.br
                                </a>
                            </li>
                            <li>
                                <a href="https://wa.me/5564996257284" target="_blank" rel="noopener" class="hover:text-primary-400 transition-colors">
                                    WhatsApp: (64) 99625-7284
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-dark-800 mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-caption text-dark-500">&copy; {{ new Date().getFullYear() }} ZyraVision. Todos os direitos reservados.</p>
                    <p class="text-caption text-dark-500">Feito com dedicação no Brasil 🇧🇷</p>
                </div>
            </div>
        </footer>

        <!-- ─── WhatsApp FAB ───────────────────────────────────── -->
        <a
            href="https://wa.me/5564996257284?text=Olá! Gostaria de saber mais sobre o ZyraVision."
            target="_blank"
            rel="noopener"
            class="whatsapp-fab fixed bottom-6 right-6 z-50 flex items-center justify-center h-14 w-14 rounded-full bg-[#25D366] text-white shadow-xl hover:shadow-2xl hover:scale-110 active:scale-95 transition-all duration-300 group"
            aria-label="Conversar no WhatsApp"
        >
            <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
            <span
                class="absolute right-full mr-3 px-3 py-2 rounded-lg bg-dark-800 text-white text-body-sm font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none shadow-xl border border-dark-700"
            >
                Fale conosco no WhatsApp
            </span>
        </a>
    </div>
</template>

<style scoped>
/* Pulse sutil a cada 10s no FAB para chamar atenção */
.whatsapp-fab {
    animation: fabPulse 10s ease-in-out infinite;
}
@keyframes fabPulse {
    0%, 90%, 100% {
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }
    92%, 98% {
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1), 0 0 0 0 4px rgba(37, 211, 102, 0.4);
    }
}
</style>
