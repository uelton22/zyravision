# ZyCard — Design System ZyraVision

## Card Padrao

```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm
            border border-gray-200 dark:border-gray-700
            hover:shadow-md transition-shadow">
  <!-- Header (opcional) -->
  <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700
              flex items-center justify-between">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Titulo</h3>
    <ZyButton variant="ghost" size="sm">Acao</ZyButton>
  </div>

  <!-- Body -->
  <div class="p-6">
    <slot />
  </div>

  <!-- Footer (opcional) -->
  <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50
              border-t border-gray-200 dark:border-gray-700
              rounded-b-lg">
    <slot name="footer" />
  </div>
</div>
```

## Variantes

### Card Estatistica (Dashboard)

```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
  <div class="flex items-center justify-between">
    <div>
      <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cameras Online</p>
      <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">24</p>
    </div>
    <div class="rounded-full bg-success-50 dark:bg-success-500/10 p-3">
      <VideoCameraIcon class="h-6 w-6 text-success-500" />
    </div>
  </div>
  <div class="mt-4 flex items-center text-sm">
    <ArrowUpIcon class="h-4 w-4 text-success-500 mr-1" />
    <span class="text-success-600 dark:text-success-400 font-medium">+2</span>
    <span class="text-gray-500 dark:text-gray-400 ml-1">desde ontem</span>
  </div>
</div>
```

### Card de Camera (Mosaico)

```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden group">
  <!-- Thumbnail/Player -->
  <div class="aspect-video bg-black relative">
    <img :src="thumbnail" class="w-full h-full object-cover" />
    <!-- Overlay LIVE -->
    <div class="absolute top-2 left-2 flex items-center gap-1 bg-black/60 rounded px-1.5 py-0.5">
      <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
      <span class="text-xs font-medium text-white">LIVE</span>
    </div>
    <!-- Overlay de controles (hover) -->
    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
      <PlayIcon class="h-12 w-12 text-white" />
    </div>
  </div>

  <!-- Info -->
  <div class="p-3">
    <div class="flex items-center justify-between">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
        Entrada Principal
      </h4>
      <ZyBadge variant="success" size="sm">Online</ZyBadge>
    </div>
    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 font-mono truncate">
      192.168.1.100
    </p>
  </div>
</div>
```

### Card de Alerta

```html
<div class="bg-danger-50 dark:bg-danger-500/10 border border-danger-200 dark:border-danger-500/20 rounded-lg p-4">
  <div class="flex items-start gap-3">
    <ExclamationTriangleIcon class="h-5 w-5 text-danger-500 mt-0.5" />
    <div>
      <h4 class="text-sm font-semibold text-danger-800 dark:text-danger-300">Camera Offline</h4>
      <p class="mt-1 text-sm text-danger-700 dark:text-danger-400">
        Camera "Estacionamento" perdeu conexao ha 5 minutos.
      </p>
    </div>
  </div>
</div>
```

## Props

```typescript
interface CardProps {
  padding?: 'none' | 'sm' | 'md' | 'lg'
  hoverable?: boolean
  clickable?: boolean
}
```

## Regras
- SEMPRE `rounded-lg` para cards
- SEMPRE borda + sombra sutil (light mode)
- SEMPRE borda sem sombra (dark mode)
- Card de camera: aspect-video para thumbnail
- Cards de estatistica: grid de 4 colunas no dashboard
