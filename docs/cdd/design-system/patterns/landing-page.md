# Landing Page — ZyraVision Design System

## Visao Geral

A landing page e a porta de entrada comercial do ZyraVision. Deve ser profissional, moderna, com foco em conversao e transmitir confianca para o segmento CFTV/seguranca. E uma pagina publica (sem autenticacao), separada do dashboard.

## Dados de Contato Oficiais

```
WhatsApp: +55 64 99625-7284
Email: contato@ziravision.com.br
Demo URL: /demo (redireciona para plataforma de demonstracao)
```

## Stack da Landing Page

A landing page e servida pelo proprio Laravel (rota publica) usando Inertia + Vue:

```php
// routes/web.php
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/demo', [DemoController::class, 'redirect'])->name('demo');
```

## Layout da Landing Page (NAO usa AuthenticatedLayout)

```vue
<!-- Layouts/LandingLayout.vue -->
<template>
  <div class="min-h-screen bg-white dark:bg-gray-950">
    <!-- Navbar fixa -->
    <LandingNavbar />

    <!-- Conteudo -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <LandingFooter />

    <!-- WhatsApp FAB (sempre visivel) -->
    <WhatsAppButton />
  </div>
</template>
```

---

## Secoes da Landing Page (Ordem obrigatoria)

### 1. NAVBAR (Fixo no topo)

```
┌──────────────────────────────────────────────────────────────┐
│ [Logo ZyraVision]    Recursos  Vantagens  App  Precos  Blog │
│                                    [Demonstracao] [Entrar]   │
└──────────────────────────────────────────────────────────────┘
```

- Logo ZyraVision a esquerda
- Links de navegacao smooth-scroll para as secoes
- Botao "Demonstracao" (outline/secondary) → /demo
- Botao "Entrar" (primary) → /login
- Sticky com backdrop-blur no scroll
- Mobile: hamburger menu

```html
<nav class="fixed top-0 inset-x-0 z-50 bg-white/80 dark:bg-gray-950/80 backdrop-blur-lg
            border-b border-gray-200/50 dark:border-gray-800/50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
    <!-- Logo -->
    <!-- Nav Links (hidden mobile) -->
    <!-- CTAs -->
  </div>
</nav>
```

---

### 2. HERO (Secao principal — acima da dobra)

```
┌──────────────────────────────────────────────────────────────┐
│                                                              │
│  Monitoramento CFTV                    [Imagem do Dashboard] │
│  Profissional e Inteligente            [com cameras ao vivo] │
│                                                              │
│  Gerencie milhares de cameras com                            │
│  IA, alta disponibilidade e                                  │
│  controle total.                                             │
│                                                              │
│  [Agendar Demonstracao]  [Conhecer Recursos]                 │
│                                                              │
│  ✓ 100% Linux  ✓ Multi-Tenant  ✓ IA Integrada               │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

- Titulo impactante (text-4xl md:text-6xl font-bold)
- Subtitulo explicativo (text-xl text-gray-500)
- 2 CTAs: "Agendar Demonstracao" (primary grande) + "Conhecer Recursos" (secondary)
- Imagem/mockup do dashboard com cameras
- Trust badges abaixo (checkmarks com diferenciais)
- Gradiente sutil no fundo ou pattern de grid

```html
<section class="relative pt-32 pb-20 lg:pt-40 lg:pb-32 overflow-hidden">
  <!-- Background gradient -->
  <div class="absolute inset-0 bg-gradient-to-br from-primary-50 via-white to-white
              dark:from-gray-950 dark:via-gray-900 dark:to-gray-950"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center">
      <!-- Texto -->
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full
                    bg-primary-50 dark:bg-primary-500/10 text-primary-700 dark:text-primary-400
                    text-sm font-medium mb-6">
          <SparklesIcon class="h-4 w-4" />
          Plataforma CFTV com Inteligencia Artificial
        </div>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white
                   tracking-tight leading-tight">
          Monitoramento CFTV
          <span class="text-primary-600 dark:text-primary-400">Profissional</span>
          e Inteligente
        </h1>
        <p class="mt-6 text-lg md:text-xl text-gray-600 dark:text-gray-400 max-w-xl">
          Gerencie milhares de cameras com IA, alta disponibilidade
          e controle total. 100% Linux, cloud-agnostic e escalavel.
        </p>
        <!-- CTAs -->
        <div class="mt-8 flex flex-col sm:flex-row gap-4">
          <ZyButton size="lg" href="/demo">
            Agendar Demonstracao
          </ZyButton>
          <ZyButton size="lg" variant="secondary" href="#recursos">
            Conhecer Recursos
          </ZyButton>
        </div>
        <!-- Trust badges -->
        <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-500 dark:text-gray-400">
          <span class="flex items-center gap-1.5">
            <CheckCircleIcon class="h-5 w-5 text-success-500" /> 100% Linux
          </span>
          <span>...</span>
        </div>
      </div>

      <!-- Imagem/Mockup -->
      <div class="mt-12 lg:mt-0">
        <div class="rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <img src="/images/landing/dashboard-mockup.webp"
               alt="Dashboard ZyraVision com cameras ao vivo"
               class="w-full" />
        </div>
      </div>
    </div>
  </div>
</section>
```

---

### 3. LOGOS / SOCIAL PROOF (Opcional)

```
┌──────────────────────────────────────────────────────────────┐
│  Usado por empresas que confiam em seguranca                 │
│  [Logo1]  [Logo2]  [Logo3]  [Logo4]  [Logo5]               │
└──────────────────────────────────────────────────────────────┘
```

- Logos de clientes/parceiros em grayscale
- `opacity-50 grayscale hover:opacity-100 hover:grayscale-0 transition`

---

### 4. RECURSOS / FEATURES (id="recursos")

```
┌──────────────────────────────────────────────────────────────┐
│  Tudo que voce precisa para                                  │
│  monitoramento profissional                                  │
│                                                              │
│  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐                    │
│  │ Live │  │Gravar│  │  IA  │  │Multi │                    │
│  │ View │  │ 24/7 │  │Smart │  │Tenant│                    │
│  └──────┘  └──────┘  └──────┘  └──────┘                    │
│  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐                    │
│  │PTZ   │  │Export│  │Alert │  │Cloud │                    │
│  │Ctrl  │  │Video │  │Real  │  │Store │                    │
│  └──────┘  └──────┘  └──────┘  └──────┘                    │
└──────────────────────────────────────────────────────────────┘
```

Features a destacar (grid 2x4 ou 3x3):
1. **Visualizacao ao Vivo** — Mosaico de cameras em tempo real (HLS/WebRTC)
2. **Gravacao 24/7** — Gravacao continua com retencao configuravel
3. **Inteligencia Artificial** — Deteccao de pessoas, veiculos, placas
4. **Multi-Tenant** — Gerencie multiplos clientes isolados
5. **Controle PTZ** — Controle pan/tilt/zoom via ONVIF
6. **Exportacao Legal** — Clips com hash SHA-256 e cadeia de custodia
7. **Alertas em Tempo Real** — Push, email, WhatsApp, webhook
8. **Storage Flexivel** — Local, NAS, NFS, S3, MinIO, Azure

Cada card de feature:
```html
<div class="text-center p-6">
  <div class="mx-auto h-12 w-12 rounded-xl bg-primary-50 dark:bg-primary-500/10
              flex items-center justify-center mb-4">
    <VideoCameraIcon class="h-6 w-6 text-primary-600 dark:text-primary-400" />
  </div>
  <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Visualizacao ao Vivo</h3>
  <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
    Mosaico de cameras em tempo real com HLS e WebRTC para ultra baixa latencia.
  </p>
</div>
```

---

### 5. COMO FUNCIONA (id="como-funciona")

```
┌──────────────────────────────────────────────────────────────┐
│  Como o ZyraVision funciona?                                 │
│                                                              │
│  ① Cadastre        ② Configure       ③ Monitore             │
│  suas cameras      gravacao e IA      em tempo real          │
│                                                              │
│  [Screenshot       [Screenshot        [Screenshot            │
│   cadastro]         config]            dashboard]             │
└──────────────────────────────────────────────────────────────┘
```

3 passos simples com screenshots:
1. **Cadastre suas cameras** — Adicione cameras RTSP/ONVIF em segundos
2. **Configure gravacao e IA** — Defina retencao, deteccao, alertas
3. **Monitore em tempo real** — Dashboard web ou app mobile

---

### 6. VANTAGENS / DIFERENCIAIS (id="vantagens")

```
┌──────────────────────────────────────────────────────────────┐
│  Por que escolher o ZyraVision?                              │
│                                                              │
│  [Imagem app]  ✓ 100% Linux — sem dependencia Windows       │
│                ✓ Cloud-agnostic — on-premise ou nuvem        │
│                ✓ Escalavel — de 1 a 10.000 cameras          │
│                ✓ IA integrada — deteccao inteligente         │
│                ✓ Multi-tenant — ideal para integradores      │
│                ✓ API aberta — integre com qualquer sistema   │
│                ✓ App mobile — iOS e Android                  │
│                ✓ Suporte brasileiro — WhatsApp direto        │
└──────────────────────────────────────────────────────────────┘
```

Layout: imagem a esquerda, lista de vantagens a direita (ou alternado).

---

### 7. APP MOBILE (id="app")

```
┌──────────────────────────────────────────────────────────────┐
│  Monitore de qualquer lugar                                  │
│  com o app ZyraVision                                        │
│                                                              │
│  [Mockup iPhone]  [Mockup Android]                           │
│                                                              │
│  ✓ Live view no celular                                     │
│  ✓ Push notifications de alertas                            │
│  ✓ Playback de gravacoes                                    │
│  ✓ Controle PTZ remoto                                      │
│                                                              │
│  [App Store]  [Google Play]  (futuros badges)               │
└──────────────────────────────────────────────────────────────┘
```

- Mockups de celular com screenshots do app
- Lista de features mobile
- Badges de App Store / Google Play (quando disponivel)

---

### 8. PLANOS / PRECOS (id="precos") — Opcional MVP

```
┌──────────────────────────────────────────────────────────────┐
│  Planos para cada necessidade                                │
│                                                              │
│  ┌─────────┐  ┌──────────────┐  ┌─────────┐               │
│  │ Starter │  │ Professional │  │Entreprise│               │
│  │         │  │  POPULAR     │  │          │               │
│  │ 16 cam  │  │  64 cameras  │  │ Ilimitado│               │
│  │ 1TB     │  │  5TB         │  │ Custom   │               │
│  │ 7 dias  │  │  30 dias     │  │ 90 dias  │               │
│  │         │  │  + IA        │  │ + Tudo   │               │
│  │ R$X/mes │  │  R$X/mes     │  │ Consulte │               │
│  │[Iniciar]│  │ [Iniciar]    │  │[Contato] │               │
│  └─────────┘  └──────────────┘  └─────────┘               │
└──────────────────────────────────────────────────────────────┘
```

3 planos side-by-side, plano do meio destacado.

---

### 9. DEPOIMENTOS / TESTIMONIALS (id="depoimentos")

```
┌──────────────────────────────────────────────────────────────┐
│  O que nossos clientes dizem                                 │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ "Excelente   │  │ "Migrei do   │  │ "O app       │      │
│  │  plataforma" │  │  Intelbras"  │  │  mobile eh   │      │
│  │  — Joao,     │  │  — Maria,    │  │  incrivel"   │      │
│  │  Integrador  │  │  Empresa X   │  │  — Pedro     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└──────────────────────────────────────────────────────────────┘
```

Cards com foto, nome, empresa, depoimento.

---

### 10. FAQ (id="faq")

```
┌──────────────────────────────────────────────────────────────┐
│  Perguntas Frequentes                                        │
│                                                              │
│  ▸ O ZyraVision funciona com minhas cameras atuais?         │
│  ▸ Preciso de servidor dedicado?                            │
│  ▸ Como funciona o suporte?                                 │
│  ▸ Posso migrar do meu sistema atual?                       │
│  ▸ A IA funciona sem GPU?                                   │
│  ▸ Tem versao de demonstracao?                              │
└──────────────────────────────────────────────────────────────┘
```

Accordion (disclosure) com perguntas expandiveis.

---

### 11. CTA FINAL + FORMULARIO DE CONTATO (id="contato")

```
┌──────────────────────────────────────────────────────────────┐
│  Pronto para modernizar seu                                  │
│  monitoramento?                                              │
│                                                              │
│  [Nome]  [Email]  [Telefone]  [Empresa]                     │
│  [Mensagem                                        ]          │
│  [Enviar Mensagem]                                           │
│                                                              │
│  Ou fale conosco:                                           │
│  📱 WhatsApp: (64) 99625-7284                               │
│  📧 contato@ziravision.com.br                               │
└──────────────────────────────────────────────────────────────┘
```

Formulario de contato:
- Nome (obrigatorio)
- Email (obrigatorio)
- Telefone (opcional)
- Empresa (opcional)
- Numero de cameras (select: 1-16, 17-64, 65-256, 256+)
- Mensagem (obrigatorio)
- Botao "Enviar Mensagem" (primary)

Backend:
```php
// ContactController@store
// Salvar no banco + Enviar email para contato@ziravision.com.br
// Opcionalmente: notificar via WhatsApp API
```

---

### 12. FOOTER

```
┌──────────────────────────────────────────────────────────────┐
│  ZyraVision                                                  │
│  Plataforma Profissional     Recursos    Empresa    Legal    │
│  de Monitoramento CFTV       Live View   Sobre      Termos   │
│                              Gravacao    Blog       Priv.    │
│  📱 (64) 99625-7284         IA          Contato    LGPD     │
│  📧 contato@ziravision.com.br  App      Suporte             │
│                                                              │
│  © 2026 ZyraVision. Todos os direitos reservados.           │
└──────────────────────────────────────────────────────────────┘
```

---

### 13. WHATSAPP FLOATING BUTTON (Sempre visivel)

```html
<a href="https://wa.me/5564996257284?text=Olá! Gostaria de saber mais sobre o ZyraVision."
   target="_blank"
   rel="noopener noreferrer"
   class="fixed bottom-6 right-6 z-50
          h-14 w-14 rounded-full bg-[#25D366] text-white
          shadow-lg hover:shadow-xl hover:scale-110
          transition-all duration-200
          flex items-center justify-center
          group">
  <!-- WhatsApp SVG icon -->
  <svg class="h-7 w-7" ...>...</svg>

  <!-- Tooltip -->
  <span class="absolute right-full mr-3 px-3 py-1.5 rounded-lg
               bg-gray-900 text-white text-sm font-medium whitespace-nowrap
               opacity-0 group-hover:opacity-100 transition-opacity">
    Fale conosco no WhatsApp
  </span>
</a>
```

- Posicao: bottom-right, fixo
- Cor: verde WhatsApp (#25D366)
- Numero: 5564996257284
- Mensagem padrao: "Olá! Gostaria de saber mais sobre o ZyraVision."
- Tooltip no hover
- Animacao sutil de pulse a cada 10s para chamar atencao

---

## Usuarios de Demonstracao

A plataforma de demonstracao (/demo) deve ter usuarios pre-configurados:

| Role | Email | Senha | Descricao |
|------|-------|-------|-----------|
| Admin | admin@demo.zyravision.com.br | demo2026 | Acesso total |
| Operador | operador@demo.zyravision.com.br | demo2026 | Monitoramento |
| Visualizador | viewer@demo.zyravision.com.br | demo2026 | Somente visualizacao |

A demo deve ter:
- Tenant "Empresa Demonstracao" pre-criado
- 8-16 cameras simuladas com thumbnails
- Dados de exemplo (gravacoes, alertas, eventos IA)
- Banner "MODO DEMONSTRACAO" no topo
- Reset automatico dos dados a cada 24h
- Rate limiting mais agressivo

---

## Imagens Necessarias

| Imagem | Formato | Uso |
|--------|---------|-----|
| logo.svg | SVG | Navbar, footer |
| logo-white.svg | SVG | Sobre fundo escuro |
| logo-icon.svg | SVG | Favicon, icone pequeno |
| dashboard-mockup.webp | WebP 1200x800 | Hero section |
| app-mockup-iphone.webp | WebP 400x800 | Secao mobile |
| app-mockup-android.webp | WebP 400x800 | Secao mobile |
| feature-live.webp | WebP 600x400 | Screenshot live view |
| feature-recording.webp | WebP 600x400 | Screenshot gravacao |
| feature-ai.webp | WebP 600x400 | Screenshot IA |
| og-image.jpg | JPG 1200x630 | Open Graph (social media) |

## SEO

```html
<title>ZyraVision — Plataforma Profissional de Monitoramento CFTV</title>
<meta name="description" content="Gerencie milhares de cameras com IA, gravacao 24/7, 
  multi-tenant e app mobile. 100% Linux, cloud-agnostic e escalavel." />
<meta property="og:title" content="ZyraVision — Monitoramento CFTV Inteligente" />
<meta property="og:description" content="..." />
<meta property="og:image" content="/images/og-image.jpg" />
<meta property="og:type" content="website" />
<link rel="canonical" href="https://zyravision.com.br" />
```

## Performance da Landing

- Lighthouse Performance: > 90
- LCP (Largest Contentful Paint): < 2.5s
- CLS (Cumulative Layout Shift): < 0.1
- Imagens: WebP com lazy loading
- Fontes: preload Inter
- CSS: Tailwind purge automatico
