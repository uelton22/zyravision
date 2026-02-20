<script setup lang="ts">
/**
 * Landing Page — Página principal de marketing do ZyraVision
 * 13 seções: Navbar, Hero, Social Proof, Features, How It Works,
 * Advantages, Mobile App, Pricing, Testimonials, FAQ, CTA/Contact, Footer, WhatsApp FAB
 */

import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import {
    VideoCameraIcon,
    ShieldCheckIcon,
    CpuChipIcon,
    CloudArrowUpIcon,
    ChartBarIcon,
    BellAlertIcon,
    DevicePhoneMobileIcon,
    GlobeAltIcon,
    ClockIcon,
    UserGroupIcon,
    ServerStackIcon,
    LockClosedIcon,
    CheckIcon,
    ChevronDownIcon,
    PlayIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/24/solid';

defineOptions({ layout: LandingLayout });

// ─── Contact Form ─────────────────────────────────────────
const contactForm = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    cameras: '',
    message: '',
});

function submitContact() {
    contactForm.post('/contact', {
        preserveScroll: true,
        onSuccess: () => contactForm.reset(),
    });
}

// ─── FAQ Accordion ────────────────────────────────────────
const openFaq = ref<number | null>(null);
function toggleFaq(index: number) {
    openFaq.value = openFaq.value === index ? null : index;
}

// ─── Data ─────────────────────────────────────────────────
const stats = [
    { value: '99.9%', label: 'Uptime garantido' },
    { value: '10K+', label: 'Câmeras monitoradas' },
    { value: '500+', label: 'Empresas confiam' },
    { value: '24/7', label: 'Suporte técnico' },
];

const features = [
    {
        icon: VideoCameraIcon,
        title: 'Monitoramento em Tempo Real',
        description: 'Visualize todas as câmeras simultaneamente com streaming de baixa latência via RTSP, HLS e WebRTC.',
    },
    {
        icon: CpuChipIcon,
        title: 'Inteligência Artificial',
        description: 'Detecção de movimento, reconhecimento facial e análise comportamental com IA integrada.',
    },
    {
        icon: CloudArrowUpIcon,
        title: 'Gravação em Nuvem',
        description: 'Armazenamento seguro com retenção configurável. Nunca perca uma gravação importante.',
    },
    {
        icon: ShieldCheckIcon,
        title: 'Segurança Total',
        description: 'Criptografia AES-256, autenticação multi-fator e conformidade LGPD garantida.',
    },
    {
        icon: ChartBarIcon,
        title: 'Analytics Avançado',
        description: 'Dashboards com métricas em tempo real, relatórios detalhados e insights de segurança.',
    },
    {
        icon: BellAlertIcon,
        title: 'Alertas Inteligentes',
        description: 'Notificações push, e-mail e SMS para eventos críticos detectados pela IA.',
    },
];

const howItWorks = [
    { step: '01', title: 'Cadastro Rápido', description: 'Crie sua conta em menos de 2 minutos. Sem cartão de crédito.' },
    { step: '02', title: 'Conecte Câmeras', description: 'Adicione suas câmeras RTSP/ONVIF. Compatível com +5.000 modelos.' },
    { step: '03', title: 'Configure a IA', description: 'Defina zonas de interesse, alertas e regras de detecção personalizadas.' },
    { step: '04', title: 'Monitore Tudo', description: 'Acompanhe em tempo real pelo desktop ou app mobile de qualquer lugar.' },
];

const advantages = [
    { icon: GlobeAltIcon, title: 'Multi-Tenant', description: 'Gerencie múltiplas filiais e clientes em uma única plataforma.' },
    { icon: ServerStackIcon, title: '100% Linux', description: 'Infraestrutura baseada em Docker/Kubernetes para máxima confiabilidade.' },
    { icon: DevicePhoneMobileIcon, title: 'App Mobile', description: 'Aplicativo Flutter nativo para iOS e Android com notificações push.' },
    { icon: ClockIcon, title: 'Alta Disponibilidade', description: 'Arquitetura distribuída com failover automático e 99.9% de uptime.' },
    { icon: UserGroupIcon, title: 'RBAC Avançado', description: 'Controle granular de permissões por função, câmera e área.' },
    { icon: LockClosedIcon, title: 'LGPD Compliant', description: 'Logs de auditoria, anonimização e políticas de retenção de dados.' },
];

const plans = [
    {
        name: 'Starter',
        price: 'R$ 197',
        period: '/mês',
        description: 'Ideal para pequenas empresas',
        features: ['Até 16 câmeras', '7 dias de gravação', '3 usuários', 'Suporte por e-mail', 'Dashboard básico'],
        cta: 'Começar Agora',
        popular: false,
    },
    {
        name: 'Professional',
        price: 'R$ 497',
        period: '/mês',
        description: 'Para empresas em crescimento',
        features: ['Até 64 câmeras', '30 dias de gravação', '10 usuários', 'Suporte prioritário', 'IA básica (detecção)', 'App mobile', 'API acesso'],
        cta: 'Escolher Professional',
        popular: true,
    },
    {
        name: 'Enterprise',
        price: 'Sob consulta',
        period: '',
        description: 'Para grandes operações',
        features: ['Câmeras ilimitadas', 'Retenção customizada', 'Usuários ilimitados', 'Suporte 24/7 dedicado', 'IA completa', 'Multi-tenant', 'SLA garantido', 'Deploy on-premise'],
        cta: 'Falar com Vendas',
        popular: false,
    },
];

const testimonials = [
    {
        name: 'Carlos Mendes',
        role: 'Diretor de Segurança',
        company: 'Rede Premium Supermercados',
        quote: 'O ZyraVision revolucionou nossa operação de segurança. A detecção por IA reduziu em 70% os alarmes falsos.',
        rating: 5,
    },
    {
        name: 'Ana Paula Costa',
        role: 'Gerente de TI',
        company: 'Condomínio Alto Padrão',
        quote: 'A plataforma é incrivelmente intuitiva. Nossos porteiros aprenderam a usar em minutos, não em dias.',
        rating: 5,
    },
    {
        name: 'Roberto Silva',
        role: 'CEO',
        company: 'SecTech Monitoramento',
        quote: 'Como revendedor, o sistema multi-tenant nos permite gerenciar centenas de clientes com facilidade.',
        rating: 5,
    },
];

const faqs = [
    {
        question: 'Quais câmeras são compatíveis com o ZyraVision?',
        answer: 'O ZyraVision é compatível com qualquer câmera que suporte protocolo RTSP ou ONVIF. Isso inclui mais de 5.000 modelos de marcas como Hikvision, Dahua, Intelbras, Axis, Bosch, entre outras.',
    },
    {
        question: 'Preciso de servidor próprio para usar?',
        answer: 'Não necessariamente. Oferecemos a versão cloud (SaaS) onde toda a infraestrutura é gerenciada por nós. Para empresas que preferem, também oferecemos deploy on-premise no plano Enterprise.',
    },
    {
        question: 'Como funciona a detecção por IA?',
        answer: 'Utilizamos modelos de deep learning (YOLO) acelerados por GPU para detecção de pessoas, veículos, objetos abandonados e comportamentos suspeitos. Tudo processado em tempo real com latência mínima.',
    },
    {
        question: 'O sistema está em conformidade com a LGPD?',
        answer: 'Sim. O ZyraVision foi projetado desde o início com privacidade em mente. Oferecemos logs de auditoria, políticas de retenção de dados, anonimização de faces e controle granular de acesso.',
    },
    {
        question: 'É possível testar antes de comprar?',
        answer: 'Sim! Oferecemos uma demonstração completa gratuita. Acesse nossa plataforma de demonstração com usuários de teste para explorar todos os recursos sem compromisso.',
    },
    {
        question: 'Qual o suporte oferecido?',
        answer: 'Oferecemos suporte por e-mail no plano Starter, suporte prioritário por chat e telefone no Professional, e suporte 24/7 dedicado com SLA no Enterprise.',
    },
];
</script>

<template>
    <Head title="Monitoramento CFTV Profissional e Inteligente" />

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 1 — HERO (industrial/refinado, staggered reveal)
         ═══════════════════════════════════════════════════════════ -->
    <section class="relative pt-24 pb-20 sm:pt-32 sm:pb-28 overflow-hidden">
        <!-- Gradient mesh + grid -->
        <div class="absolute inset-0 bg-gradient-to-br from-dark-950 via-dark-900 to-dark-950" />
        <div class="absolute inset-0 bg-gradient-to-t from-primary-950/30 via-transparent to-transparent" />
        <div class="absolute top-0 right-0 w-2/3 h-full bg-gradient-to-l from-primary-500/5 to-transparent" />
        <div
            class="absolute inset-0 opacity-30"
            style="background-image: linear-gradient(rgba(26,115,232,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(26,115,232,0.03) 1px, transparent 1px); background-size: 64px 64px;"
        />

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-14 items-center">
                <div class="space-y-8">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-primary-500/10 border border-primary-500/20 text-primary-400 text-caption font-semibold uppercase tracking-wider"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-secondary-400 animate-pulse" />
                        Plataforma de Monitoramento CFTV
                    </div>

                    <h1 class="font-landing-display text-display-lg sm:text-display-xl font-bold text-white leading-[1.08] tracking-tight">
                        Monitoramento CFTV
                        <span class="text-primary-400">Profissional</span>
                        e
                        <span class="text-secondary-400">Inteligente</span>
                    </h1>

                    <p class="text-body-lg text-dark-300 max-w-xl leading-relaxed">
                        Gerencie milhares de câmeras com inteligência artificial, alta disponibilidade e controle total.
                        Tudo em uma plataforma moderna e segura.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <a
                            href="/demo"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-primary-500 text-white font-semibold rounded-lg hover:bg-primary-400 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40"
                        >
                            <PlayIcon class="h-5 w-5" />
                            Agendar Demonstração
                        </a>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3.5 border border-dark-600 text-dark-200 font-semibold rounded-lg hover:bg-dark-800 hover:border-dark-500 transition-all"
                            @click="$el.closest('main')?.querySelector('#recursos')?.scrollIntoView({ behavior: 'smooth' })"
                        >
                            Conhecer Recursos
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-6 text-body-sm text-dark-400">
                        <span class="flex items-center gap-2">
                            <CheckIcon class="h-4 w-4 text-secondary-400" />
                            100% Linux
                        </span>
                        <span class="flex items-center gap-2">
                            <CheckIcon class="h-4 w-4 text-secondary-400" />
                            Multi-Tenant
                        </span>
                        <span class="flex items-center gap-2">
                            <CheckIcon class="h-4 w-4 text-secondary-400" />
                            IA Integrada
                        </span>
                    </div>
                </div>

                <div class="relative">
                    <div class="rounded-xl bg-dark-800/80 shadow-2xl overflow-hidden border border-dark-700/80 backdrop-blur-sm">
                        <div class="flex items-center gap-2 px-4 py-2.5 bg-dark-900/80 border-b border-dark-700">
                            <div class="flex gap-1.5">
                                <div class="h-2.5 w-2.5 rounded-full bg-danger-500" />
                                <div class="h-2.5 w-2.5 rounded-full bg-warning-500" />
                                <div class="h-2.5 w-2.5 rounded-full bg-secondary-500" />
                            </div>
                            <div class="flex-1 mx-4">
                                <div class="h-6 bg-dark-700 rounded text-center text-xs text-dark-400 leading-6 font-mono">
                                    app.zyravision.com.br/dashboard
                                </div>
                            </div>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div v-for="i in 4" :key="i" class="aspect-video bg-dark-800 rounded-lg flex items-center justify-center border border-dark-700/50">
                                    <VideoCameraIcon class="h-8 w-8 text-dark-500" />
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <div class="flex-1 bg-primary-500/15 rounded-lg p-2.5 text-center border border-primary-500/20">
                                    <div class="text-xs text-primary-400 font-medium">Online</div>
                                    <div class="text-lg font-bold text-primary-300">248</div>
                                </div>
                                <div class="flex-1 bg-secondary-500/15 rounded-lg p-2.5 text-center border border-secondary-500/20">
                                    <div class="text-xs text-secondary-400 font-medium">Gravando</div>
                                    <div class="text-lg font-bold text-secondary-300">245</div>
                                </div>
                                <div class="flex-1 bg-warning-500/15 rounded-lg p-2.5 text-center border border-warning-500/20">
                                    <div class="text-xs text-warning-400 font-medium">Alertas</div>
                                    <div class="text-lg font-bold text-warning-300">3</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="absolute -inset-6 bg-primary-500/15 rounded-2xl blur-3xl -z-10" />
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 2 — SOCIAL PROOF (Números)
         ═══════════════════════════════════════════════════════════ -->
    <section class="py-14 bg-dark-900/60 border-y border-dark-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div
                    v-for="(stat, i) in stats"
                    :key="stat.label"
                    class="text-center md:border-r md:border-dark-700 last:border-r-0"
                >
                    <div class="font-landing-display text-heading-xl font-bold text-primary-400">{{ stat.value }}</div>
                    <div class="text-body-sm text-dark-400 mt-1">{{ stat.label }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 3 — FEATURES (Recursos)
         ═══════════════════════════════════════════════════════════ -->
    <section id="recursos" class="py-24 bg-dark-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">Recursos</span>
                <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                    Tudo que você precisa para monitoramento profissional
                </h2>
                <p class="mt-4 text-body-lg text-dark-400 max-w-2xl mx-auto">
                    Uma plataforma completa que combina hardware, software e inteligência artificial.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="group relative bg-dark-800/60 rounded-xl p-6 border border-dark-700/80 hover:border-primary-500/40 hover:shadow-lg hover:shadow-primary-500/5 transition-all duration-300"
                >
                    <div class="h-12 w-12 rounded-lg bg-primary-500/15 border border-primary-500/20 flex items-center justify-center mb-4 group-hover:bg-primary-500/25 transition-colors">
                        <component :is="feature.icon" class="h-6 w-6 text-primary-400" />
                    </div>
                    <h3 class="text-heading-sm font-semibold text-white">{{ feature.title }}</h3>
                    <p class="mt-2 text-body-sm text-dark-400 leading-relaxed">{{ feature.description }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 4 — HOW IT WORKS
         ═══════════════════════════════════════════════════════════ -->
    <section class="py-24 bg-dark-900/50 border-t border-dark-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">Como Funciona</span>
                <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                    Comece a monitorar em 4 passos simples
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div v-for="item in howItWorks" :key="item.step" class="relative text-center group">
                    <div class="inline-flex items-center justify-center h-16 w-16 rounded-xl bg-primary-500 text-white font-landing-display text-heading font-bold mb-4 shadow-lg shadow-primary-500/25 border border-primary-400/30 group-hover:shadow-primary-500/40 transition-shadow">
                        {{ item.step }}
                    </div>
                    <h3 class="text-heading-sm font-semibold text-white">{{ item.title }}</h3>
                    <p class="mt-2 text-body-sm text-dark-400">{{ item.description }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 5 — ADVANTAGES
         ═══════════════════════════════════════════════════════════ -->
    <section id="vantagens" class="py-24 bg-dark-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">Vantagens</span>
                <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                    Por que escolher o ZyraVision?
                </h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div v-for="adv in advantages" :key="adv.title" class="flex gap-4 p-4 rounded-xl border border-dark-800 hover:border-dark-700 bg-dark-900/40 transition-colors">
                    <div class="shrink-0 h-11 w-11 rounded-lg bg-secondary-500/15 border border-secondary-500/20 flex items-center justify-center">
                        <component :is="adv.icon" class="h-5 w-5 text-secondary-400" />
                    </div>
                    <div>
                        <h3 class="text-body font-semibold text-white">{{ adv.title }}</h3>
                        <p class="mt-1 text-body-sm text-dark-400">{{ adv.description }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 6 — MOBILE APP
         ═══════════════════════════════════════════════════════════ -->
    <section id="app-mobile" class="py-24 bg-dark-900/50 border-t border-dark-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-14 items-center">
                <div class="flex justify-center order-2 lg:order-1">
                    <div class="relative w-64">
                        <div class="bg-dark-800 rounded-[2.5rem] p-3 shadow-2xl border border-dark-700">
                            <div class="bg-dark-900 rounded-[2rem] overflow-hidden">
                                <div class="flex justify-center py-2">
                                    <div class="h-5 w-24 bg-dark-950 rounded-full" />
                                </div>
                                <div class="px-4 pb-6 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-white">ZyraVision</span>
                                        <BellAlertIcon class="h-4 w-4 text-primary-400" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <div v-for="i in 4" :key="i" class="aspect-video bg-dark-700 rounded flex items-center justify-center">
                                            <VideoCameraIcon class="h-5 w-5 text-dark-500" />
                                        </div>
                                    </div>
                                    <div class="bg-danger-500/15 border border-danger-500/20 rounded-lg p-2 flex items-center gap-2">
                                        <BellAlertIcon class="h-4 w-4 text-danger-400" />
                                        <span class="text-xs text-danger-300">Movimento detectado — Câmera 03</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -inset-6 bg-primary-500/15 rounded-[3rem] blur-3xl -z-10" />
                    </div>
                </div>

                <div class="order-1 lg:order-2">
                    <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">App Mobile</span>
                    <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                        Monitore de qualquer lugar com nosso app
                    </h2>
                    <p class="mt-4 text-body-lg text-dark-400 leading-relaxed">
                        Aplicativo nativo para iOS e Android desenvolvido em Flutter.
                        Receba alertas em tempo real, visualize câmeras e gerencie seu sistema de qualquer lugar.
                    </p>

                    <ul class="mt-6 space-y-3">
                        <li
                            v-for="item in ['Streaming ao vivo em tempo real', 'Notificações push inteligentes', 'Modo offline com sincronização', 'Biometria e login seguro']"
                            :key="item"
                            class="flex items-center gap-3 text-body-sm text-dark-300"
                        >
                            <CheckIcon class="h-5 w-5 text-secondary-400 shrink-0" />
                            {{ item }}
                        </li>
                    </ul>

                    <div class="flex gap-4 mt-8">
                        <a
                            href="#"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-dark-900 rounded-lg font-medium text-body-sm hover:bg-dark-100 transition-colors"
                        >
                            App Store
                        </a>
                        <a
                            href="#"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-dark-900 rounded-lg font-medium text-body-sm hover:bg-dark-100 transition-colors"
                        >
                            Google Play
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 7 — PRICING
         ═══════════════════════════════════════════════════════════ -->
    <section id="precos" class="py-24 bg-dark-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">Preços</span>
                <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                    Planos para cada necessidade
                </h2>
                <p class="mt-4 text-body-lg text-dark-400">Escolha o plano ideal. Todos incluem atualizações gratuitas.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 items-stretch">
                <div
                    v-for="plan in plans"
                    :key="plan.name"
                    :class="[
                        'relative flex flex-col rounded-xl p-6 sm:p-8 border transition-all',
                        plan.popular
                            ? 'bg-dark-800 border-primary-500/60 shadow-xl shadow-primary-500/10 scale-[1.02]'
                            : 'bg-dark-800/60 border-dark-700 hover:border-dark-600',
                    ]"
                >
                    <div
                        v-if="plan.popular"
                        class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-primary-500 text-white text-caption font-semibold rounded-full"
                    >
                        Mais Popular
                    </div>

                    <h3 class="text-heading-sm font-bold text-white">{{ plan.name }}</h3>
                    <p class="text-body-sm text-dark-400 mt-1">{{ plan.description }}</p>

                    <div class="mt-6">
                        <span class="font-landing-display text-display font-bold text-white">{{ plan.price }}</span>
                        <span v-if="plan.period" class="text-body-sm text-dark-500">{{ plan.period }}</span>
                    </div>

                    <ul class="mt-6 space-y-3 flex-1">
                        <li v-for="feat in plan.features" :key="feat" class="flex items-center gap-2 text-body-sm text-dark-300">
                            <CheckIcon class="h-4 w-4 text-secondary-400 shrink-0" />
                            {{ feat }}
                        </li>
                    </ul>

                    <a
                        href="#contato"
                        :class="[
                            'block w-full text-center mt-8 px-4 py-3 rounded-lg font-semibold text-body-sm transition-colors',
                            plan.popular
                                ? 'bg-primary-500 text-white hover:bg-primary-400'
                                : 'border border-primary-500/60 text-primary-400 hover:bg-primary-500/10',
                        ]"
                    >
                        {{ plan.cta }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 8 — TESTIMONIALS
         ═══════════════════════════════════════════════════════════ -->
    <section class="py-24 bg-dark-900/50 border-t border-dark-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">Depoimentos</span>
                <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                    O que nossos clientes dizem
                </h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div
                    v-for="t in testimonials"
                    :key="t.name"
                    class="bg-dark-800/60 rounded-xl p-6 border border-dark-700 hover:border-dark-600 transition-colors"
                >
                    <div class="flex gap-0.5 mb-4">
                        <StarIcon v-for="i in t.rating" :key="i" class="h-5 w-5 text-warning-400" />
                    </div>
                    <p class="text-body-sm text-dark-300 italic leading-relaxed">"{{ t.quote }}"</p>
                    <div class="mt-5 flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-primary-500/20 border border-primary-500/30 flex items-center justify-center">
                            <span class="text-sm font-bold text-primary-400">{{ t.name.split(' ').map(n => n[0]).join('') }}</span>
                        </div>
                        <div>
                            <p class="text-body-sm font-semibold text-white">{{ t.name }}</p>
                            <p class="text-caption text-dark-500">{{ t.role }} — {{ t.company }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 9 — FAQ
         ═══════════════════════════════════════════════════════════ -->
    <section class="py-24 bg-dark-950">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">FAQ</span>
                <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                    Perguntas Frequentes
                </h2>
            </div>

            <div class="space-y-3">
                <div
                    v-for="(faq, index) in faqs"
                    :key="index"
                    class="bg-dark-800/60 rounded-xl border border-dark-700 overflow-hidden hover:border-dark-600 transition-colors"
                >
                    <button
                        type="button"
                        class="flex items-center justify-between w-full px-5 py-4 text-left"
                        :aria-expanded="openFaq === index"
                        @click="toggleFaq(index)"
                    >
                        <span class="text-body font-medium text-white pr-4">{{ faq.question }}</span>
                        <ChevronDownIcon
                            :class="['h-5 w-5 text-dark-400 shrink-0 transition-transform duration-200', openFaq === index ? 'rotate-180' : '']"
                        />
                    </button>
                    <transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-96 opacity-100"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="max-h-96 opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                        <div v-if="openFaq === index" class="px-5 pb-4">
                            <p class="text-body-sm text-dark-400 leading-relaxed">{{ faq.answer }}</p>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         SEÇÃO 10 — CONTACT / CTA
         ═══════════════════════════════════════════════════════════ -->
    <section id="contato" class="py-24 bg-dark-900/50 border-t border-dark-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-14">
                <div>
                    <span class="text-caption font-semibold text-primary-400 uppercase tracking-wider">Contato</span>
                    <h2 class="mt-3 font-landing-display text-heading-xl font-bold text-white">
                        Pronto para transformar sua segurança?
                    </h2>
                    <p class="mt-4 text-body-lg text-dark-400 leading-relaxed">
                        Entre em contato para uma demonstração personalizada ou tire suas dúvidas com nossa equipe.
                    </p>

                    <div class="mt-8 space-y-4">
                        <a
                            href="https://wa.me/5564996257284?text=Olá! Gostaria de saber mais sobre o ZyraVision."
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-3 p-4 rounded-xl border border-dark-700 hover:border-secondary-500/40 bg-dark-800/40 transition-colors"
                        >
                            <div class="h-11 w-11 rounded-lg bg-[#25D366]/15 border border-[#25D366]/30 flex items-center justify-center">
                                <PhoneIcon class="h-5 w-5 text-[#25D366]" />
                            </div>
                            <div>
                                <p class="text-body-sm font-semibold text-white">WhatsApp</p>
                                <p class="text-caption text-dark-500">(64) 99625-7284</p>
                            </div>
                        </a>

                        <a
                            href="mailto:contato@ziravision.com.br"
                            class="flex items-center gap-3 p-4 rounded-xl border border-dark-700 hover:border-primary-500/40 bg-dark-800/40 transition-colors"
                        >
                            <div class="h-11 w-11 rounded-lg bg-primary-500/15 border border-primary-500/30 flex items-center justify-center">
                                <EnvelopeIcon class="h-5 w-5 text-primary-400" />
                            </div>
                            <div>
                                <p class="text-body-sm font-semibold text-white">E-mail</p>
                                <p class="text-caption text-dark-500">contato@ziravision.com.br</p>
                            </div>
                        </a>
                    </div>

                    <div class="mt-8 p-6 rounded-xl bg-primary-500/10 border border-primary-500/20">
                        <h3 class="text-body font-semibold text-primary-200">Experimente gratuitamente</h3>
                        <p class="mt-1 text-body-sm text-primary-300/90">
                            Acesse nossa plataforma de demonstração com usuários de teste.
                        </p>
                        <a
                            href="/demo"
                            class="inline-flex items-center gap-2 mt-3 px-4 py-2.5 bg-primary-500 text-white text-body-sm font-medium rounded-lg hover:bg-primary-400 transition-colors"
                        >
                            <PlayIcon class="h-4 w-4" />
                            Acessar Demo
                        </a>
                    </div>
                </div>

                <div class="bg-dark-800/60 rounded-xl p-6 sm:p-8 border border-dark-700">
                    <h3 class="font-landing-display text-heading font-semibold text-white mb-6">Solicitar Demonstração</h3>

                    <form @submit.prevent="submitContact" class="space-y-4">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact-name" class="block text-body-sm font-medium text-dark-300 mb-1">Nome completo *</label>
                                <div class="relative">
                                    <UserIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-dark-500" />
                                    <input
                                        id="contact-name"
                                        v-model="contactForm.name"
                                        type="text"
                                        required
                                        placeholder="Seu nome"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-dark-600 bg-dark-900 text-body-sm text-white placeholder-dark-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label for="contact-email" class="block text-body-sm font-medium text-dark-300 mb-1">E-mail *</label>
                                <div class="relative">
                                    <EnvelopeIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-dark-500" />
                                    <input
                                        id="contact-email"
                                        v-model="contactForm.email"
                                        type="email"
                                        required
                                        placeholder="seu@email.com"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-dark-600 bg-dark-900 text-body-sm text-white placeholder-dark-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="contact-phone" class="block text-body-sm font-medium text-dark-300 mb-1">Telefone</label>
                                <div class="relative">
                                    <PhoneIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-dark-500" />
                                    <input
                                        id="contact-phone"
                                        v-model="contactForm.phone"
                                        type="tel"
                                        placeholder="(00) 00000-0000"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-dark-600 bg-dark-900 text-body-sm text-white placeholder-dark-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                            </div>
                            <div>
                                <label for="contact-company" class="block text-body-sm font-medium text-dark-300 mb-1">Empresa</label>
                                <div class="relative">
                                    <BuildingOfficeIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-dark-500" />
                                    <input
                                        id="contact-company"
                                        v-model="contactForm.company"
                                        type="text"
                                        placeholder="Nome da empresa"
                                        class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-dark-600 bg-dark-900 text-body-sm text-white placeholder-dark-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="contact-cameras" class="block text-body-sm font-medium text-dark-300 mb-1">Quantidade de câmeras</label>
                            <select
                                id="contact-cameras"
                                v-model="contactForm.cameras"
                                class="w-full px-3 py-2.5 rounded-lg border border-dark-600 bg-dark-900 text-body-sm text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            >
                                <option value="">Selecione...</option>
                                <option value="1-16">1 a 16 câmeras</option>
                                <option value="17-64">17 a 64 câmeras</option>
                                <option value="65-256">65 a 256 câmeras</option>
                                <option value="256+">Mais de 256 câmeras</option>
                            </select>
                        </div>

                        <div>
                            <label for="contact-message" class="block text-body-sm font-medium text-dark-300 mb-1">Mensagem</label>
                            <textarea
                                id="contact-message"
                                v-model="contactForm.message"
                                rows="4"
                                placeholder="Conte-nos sobre sua necessidade..."
                                class="w-full px-3 py-2.5 rounded-lg border border-dark-600 bg-dark-900 text-body-sm text-white placeholder-dark-500 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-y"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="contactForm.processing"
                            class="w-full px-6 py-3.5 bg-accent-500 text-white font-semibold rounded-lg hover:bg-accent-400 focus:ring-2 focus:ring-accent-500 focus:ring-offset-2 focus:ring-offset-dark-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="contactForm.processing">Enviando...</span>
                            <span v-else>Solicitar Demonstração Gratuita</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</template>
