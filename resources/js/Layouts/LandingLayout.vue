<script setup lang="ts">
/**
 * LandingLayout — Layout da Landing Page pública
 * NÃO usa AuthenticatedLayout. Layout independente focado em conversão.
 */

import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';
import { useTheme } from '../Composables/useTheme';

const { isDark } = useTheme();

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
    <div class="min-h-screen bg-white dark:bg-dark-950">
        <!-- ─── Navbar Fixa ─────────────────────────────────────── -->
        <nav
            :class="[
                'fixed top-0 inset-x-0 z-50 transition-all duration-300',
                isScrolled
                    ? 'bg-white/90 dark:bg-dark-950/90 backdrop-blur-lg border-b border-dark-200/50 dark:border-dark-800/50 shadow-zy-sm'
                    : 'bg-transparent',
            ]"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Logo -->
                <Link href="/" class="flex items-center gap-3">
                    <div class="h-9 w-9 rounded-zy-md bg-primary-600 flex items-center justify-center">
                        <span class="text-white font-bold text-lg">ZV</span>
                    </div>
                    <span class="text-heading-sm font-bold text-dark-900 dark:text-white">
                        Zyra<span class="text-primary-600 dark:text-primary-400">Vision</span>
                    </span>
                </Link>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center gap-8">
                    <button
                        v-for="link in navLinks"
                        :key="link.href"
                        type="button"
                        class="text-body-sm font-medium text-dark-600 hover:text-primary-600 dark:text-dark-300 dark:hover:text-primary-400 transition-colors"
                        @click="scrollToSection(link.href)"
                    >
                        {{ link.label }}
                    </button>
                </div>

                <!-- Desktop CTAs -->
                <div class="hidden lg:flex items-center gap-3">
                    <Link
                        href="/demo"
                        class="px-4 py-2 text-body-sm font-medium border border-primary-600 text-primary-600 rounded-zy hover:bg-primary-50 dark:border-primary-400 dark:text-primary-400 dark:hover:bg-primary-900/20 transition-colors"
                    >
                        Demonstração
                    </Link>
                    <Link
                        href="/login"
                        class="px-4 py-2 text-body-sm font-medium bg-primary-600 text-white rounded-zy hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400 transition-colors"
                    >
                        Entrar
                    </Link>
                </div>

                <!-- Mobile Menu Button -->
                <button
                    type="button"
                    class="lg:hidden p-2 rounded-zy text-dark-600 dark:text-dark-300"
                    aria-label="Abrir menu"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <Bars3Icon v-if="!mobileMenuOpen" class="h-6 w-6" />
                    <XMarkIcon v-else class="h-6 w-6" />
                </button>
            </div>

            <!-- Mobile Menu -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div
                    v-if="mobileMenuOpen"
                    class="lg:hidden bg-white dark:bg-dark-900 border-b border-dark-200 dark:border-dark-800 px-4 pb-4"
                >
                    <div class="space-y-1">
                        <button
                            v-for="link in navLinks"
                            :key="link.href"
                            type="button"
                            class="block w-full text-left px-3 py-2 rounded-zy text-body font-medium text-dark-600 hover:bg-dark-50 dark:text-dark-300 dark:hover:bg-dark-800"
                            @click="scrollToSection(link.href)"
                        >
                            {{ link.label }}
                        </button>
                    </div>
                    <div class="flex gap-3 mt-4 pt-4 border-t border-dark-200 dark:border-dark-800">
                        <Link href="/demo" class="flex-1 text-center px-4 py-2 text-body-sm font-medium border border-primary-600 text-primary-600 rounded-zy">
                            Demonstração
                        </Link>
                        <Link href="/login" class="flex-1 text-center px-4 py-2 text-body-sm font-medium bg-primary-600 text-white rounded-zy">
                            Entrar
                        </Link>
                    </div>
                </div>
            </transition>
        </nav>

        <!-- ─── Main Content ────────────────────────────────────── -->
        <main>
            <slot />
        </main>

        <!-- ─── Footer ──────────────────────────────────────────── -->
        <footer class="bg-dark-900 dark:bg-dark-950 text-dark-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- Brand -->
                    <div class="md:col-span-1">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="h-9 w-9 rounded-zy-md bg-primary-600 flex items-center justify-center">
                                <span class="text-white font-bold text-lg">ZV</span>
                            </div>
                            <span class="text-heading-sm font-bold text-white">ZyraVision</span>
                        </div>
                        <p class="text-body-sm text-dark-400">
                            Plataforma profissional de monitoramento CFTV com inteligência artificial.
                        </p>
                    </div>

                    <!-- Product -->
                    <div>
                        <h4 class="text-body-sm font-semibold text-white mb-4">Produto</h4>
                        <ul class="space-y-2 text-body-sm">
                            <li><button type="button" class="hover:text-white transition-colors" @click="scrollToSection('#recursos')">Recursos</button></li>
                            <li><button type="button" class="hover:text-white transition-colors" @click="scrollToSection('#precos')">Preços</button></li>
                            <li><Link href="/demo" class="hover:text-white transition-colors">Demonstração</Link></li>
                        </ul>
                    </div>

                    <!-- Company -->
                    <div>
                        <h4 class="text-body-sm font-semibold text-white mb-4">Empresa</h4>
                        <ul class="space-y-2 text-body-sm">
                            <li><a href="#" class="hover:text-white transition-colors">Sobre Nós</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Privacidade</a></li>
                            <li><a href="#" class="hover:text-white transition-colors">Termos de Uso</a></li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="text-body-sm font-semibold text-white mb-4">Contato</h4>
                        <ul class="space-y-2 text-body-sm">
                            <li>
                                <a href="mailto:contato@ziravision.com.br" class="hover:text-white transition-colors">
                                    contato@ziravision.com.br
                                </a>
                            </li>
                            <li>
                                <a href="https://wa.me/5564996257284" target="_blank" rel="noopener" class="hover:text-white transition-colors">
                                    WhatsApp: (64) 99625-7284
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom bar -->
                <div class="border-t border-dark-800 mt-8 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-caption text-dark-500">
                        &copy; {{ new Date().getFullYear() }} ZyraVision. Todos os direitos reservados.
                    </p>
                    <p class="text-caption text-dark-500">
                        Feito com dedicação no Brasil 🇧🇷
                    </p>
                </div>
            </div>
        </footer>

        <!-- ─── WhatsApp FAB ────────────────────────────────────── -->
        <a
            href="https://wa.me/5564996257284?text=Olá! Gostaria de saber mais sobre o ZyraVision."
            target="_blank"
            rel="noopener"
            class="fixed bottom-6 right-6 z-50 flex items-center justify-center h-14 w-14 rounded-full bg-[#25D366] text-white shadow-zy-lg hover:shadow-zy-xl hover:scale-110 transition-all duration-300"
            aria-label="Conversar no WhatsApp"
        >
            <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
        </a>
    </div>
</template>
