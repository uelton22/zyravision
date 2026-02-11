# Layouts — ZyraVision Design System

## Layout Principal (Authenticated)

Usado em TODAS as paginas autenticadas.

```
+--------------------------------------------------+
| ZySidebar (w-64) | ZyHeader (h-16)               |
|                  |--------------------------------|
|  - Dashboard     |                                |
|  - Cameras       |   Content Area                 |
|  - Live View     |   (p-6 md:p-8)                |
|  - Gravacoes     |                                |
|  - Alertas       |                                |
|  - IA Analytics  |                                |
|  ────────────    |                                |
|  - Usuarios      |                                |
|  - Storage       |                                |
|  - Config        |                                |
|                  |                                |
|  [Avatar]        |                                |
+--------------------------------------------------+
```

### Vue Template

```vue
<!-- Layouts/AuthenticatedLayout.vue -->
<script setup lang="ts">
import ZySidebar from '@/Components/Layout/ZySidebar.vue'
import ZyHeader from '@/Components/Layout/ZyHeader.vue'

defineProps<{
  title?: string
  breadcrumbs?: Array<{ label: string; href?: string }>
}>()
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <ZySidebar />

    <div class="lg:pl-64 flex flex-col min-h-screen">
      <ZyHeader :title="title" :breadcrumbs="breadcrumbs" />

      <main class="flex-1 p-6 md:p-8">
        <slot />
      </main>
    </div>
  </div>
</template>
```

## Layout Auth (Guest)

Login, Registro, Esqueci Senha.

```
+--------------------------------------------------+
|                                                    |
|   +------------------+   +--------------------+   |
|   |                  |   |                    |   |
|   |   ZyraVision     |   |   Formulario       |   |
|   |   Logo + desc    |   |   de Login          |   |
|   |                  |   |                    |   |
|   +------------------+   +--------------------+   |
|                                                    |
+--------------------------------------------------+
```

### Vue Template

```vue
<!-- Layouts/GuestLayout.vue -->
<template>
  <div class="min-h-screen flex bg-gray-50 dark:bg-gray-900">
    <!-- Lado esquerdo: branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-primary-600 dark:bg-primary-800
                items-center justify-center p-12">
      <div class="text-center">
        <img src="/logo-white.svg" class="h-16 mx-auto mb-6" />
        <h1 class="text-3xl font-bold text-white">ZyraVision</h1>
        <p class="mt-3 text-primary-200 text-lg">
          Plataforma Profissional de Monitoramento CFTV
        </p>
      </div>
    </div>

    <!-- Lado direito: formulario -->
    <div class="flex-1 flex items-center justify-center p-6">
      <div class="w-full max-w-md">
        <slot />
      </div>
    </div>
  </div>
</template>
```

## Layout Fullscreen (Player)

Para visualizacao de cameras em tela cheia ou mosaico.

```vue
<!-- Layouts/FullscreenLayout.vue -->
<template>
  <div class="min-h-screen bg-black flex flex-col">
    <!-- Toolbar minimalista -->
    <div class="h-12 bg-gray-900/80 backdrop-blur flex items-center justify-between px-4">
      <div class="flex items-center gap-3">
        <button @click="goBack" class="text-gray-400 hover:text-white">
          <ArrowLeftIcon class="h-5 w-5" />
        </button>
        <span class="text-sm font-medium text-white">{{ title }}</span>
      </div>
      <div class="flex items-center gap-2">
        <slot name="toolbar" />
      </div>
    </div>

    <!-- Area do player -->
    <div class="flex-1">
      <slot />
    </div>
  </div>
</template>
```

## Breakpoints

| Breakpoint | Largura | Comportamento |
|-----------|---------|---------------|
| Mobile | < 640px | Sidebar overlay, 1 coluna |
| sm | >= 640px | 2 colunas camera grid |
| md | >= 768px | Sidebar visible, 3 col grid |
| lg | >= 1024px | Sidebar fixa, 4 col grid |
| xl | >= 1280px | 6 col grid, dashboard expandido |
| 2xl | >= 1536px | Layout otimizado para monitores |

## Pagina Padrao

Toda pagina autenticada segue:

```vue
<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

defineOptions({ layout: AuthenticatedLayout })
</script>

<template>
  <!-- Page header -->
  <div class="mb-6 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cameras</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Gerencie todas as cameras do sistema
      </p>
    </div>
    <ZyButton>
      <PlusIcon class="h-5 w-5 mr-1.5" />
      Nova Camera
    </ZyButton>
  </div>

  <!-- Page content -->
  <ZyTable ... />
</template>
```
