# Padroes Responsivos e Mobile — ZyraVision Design System

## Principio Mobile-First

Toda interface e construida para mobile primeiro, depois expandida para desktop.

## Breakpoints

| Token | Largura | Dispositivo |
|-------|---------|------------|
| (base) | < 640px | Mobile |
| sm | >= 640px | Mobile landscape / Tablet small |
| md | >= 768px | Tablet |
| lg | >= 1024px | Desktop |
| xl | >= 1280px | Desktop wide |
| 2xl | >= 1536px | Monitor grande / Operacao CFTV |

## Sidebar

| Breakpoint | Comportamento |
|-----------|---------------|
| Mobile/Tablet | Overlay com backdrop, toggle via hamburger |
| lg+ | Fixa, colapsavel (w-64 / w-16) |

## Tabelas Responsivas

### Desktop: Tabela completa
Todas as colunas visiveis.

### Tablet: Colunas priorizadas
Esconder colunas secundarias com `hidden md:table-cell`.

### Mobile: Card list
Converter tabela em lista de cards:

```vue
<template>
  <!-- Desktop -->
  <ZyTable v-if="isDesktop" ... />

  <!-- Mobile -->
  <div v-else class="space-y-3">
    <div v-for="item in data" :key="item.id"
         class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
      <div class="flex items-center justify-between mb-2">
        <span class="font-medium text-gray-900 dark:text-white">{{ item.name }}</span>
        <ZyBadge :variant="item.statusVariant" size="sm" dot>{{ item.status }}</ZyBadge>
      </div>
      <p class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate">{{ item.rtsp_url }}</p>
      <div class="mt-3 flex items-center gap-2">
        <ZyButton variant="ghost" size="sm">Ver</ZyButton>
        <ZyButton variant="ghost" size="sm">Editar</ZyButton>
      </div>
    </div>
  </div>
</template>
```

## Formularios Responsivos

```
Mobile:  Campos full-width, stacked
Tablet:  Grid 2 colunas para campos relacionados
Desktop: Grid 2 colunas, max-w-2xl
```

```html
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
  <ZyInput label="Nome" />
  <ZyInput label="Sobrenome" />
</div>
```

## Grid de Cameras (Mosaico)

```html
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
  <!-- Camera cards -->
</div>
```

## Dashboard Responsivo

```html
<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

<!-- Content grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2"><!-- Camera grid --></div>
  <div><!-- Alertas --></div>
</div>
```

## Navegacao Mobile

### Bottom Navigation (Mobile)

Para o app mobile web, considerar bottom nav:

```html
<nav class="fixed bottom-0 inset-x-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800
            flex items-center justify-around h-14 sm:hidden z-50">
  <a class="flex flex-col items-center gap-0.5 text-primary-600">
    <ChartBarIcon class="h-5 w-5" />
    <span class="text-[10px] font-medium">Dashboard</span>
  </a>
  <a class="flex flex-col items-center gap-0.5 text-gray-400">
    <VideoCameraIcon class="h-5 w-5" />
    <span class="text-[10px]">Cameras</span>
  </a>
  <a class="flex flex-col items-center gap-0.5 text-gray-400">
    <PlayCircleIcon class="h-5 w-5" />
    <span class="text-[10px]">Live</span>
  </a>
  <a class="flex flex-col items-center gap-0.5 text-gray-400">
    <BellIcon class="h-5 w-5" />
    <span class="text-[10px]">Alertas</span>
  </a>
</nav>
```

## Touch Targets

- Minimo 44x44px para areas clicaveis (WCAG)
- Espacamento minimo 8px entre targets
- Botoes de acao: py-2.5 minimo em mobile

## Tipografia Responsiva

```html
<h1 class="text-xl sm:text-2xl lg:text-3xl font-bold">Titulo</h1>
```

## Video Player

| Breakpoint | Comportamento |
|-----------|---------------|
| Mobile | Full-width, aspect-video |
| Tablet | 2 colunas |
| Desktop | Mosaico customizavel |
| Fullscreen | Player dedicado |

## Regras
- SEMPRE mobile-first nos estilos
- SEMPRE testar em 320px (menor mobile)
- Touch targets >= 44px
- Sidebar overlay em mobile
- Tabelas viram cards em mobile
- Grids adaptativos com breakpoints
