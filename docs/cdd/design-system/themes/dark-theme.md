# Tema Escuro (Dark) — ZyraVision Design System

## Visao Geral

O tema escuro e **essencial para operacoes CFTV 24/7**. Operadores em salas de monitoramento usam tela por horas — o dark mode reduz fadiga visual.

## Ativacao

```html
<!-- Classe no html root -->
<html class="dark">
```

Deteccao automatica via `prefers-color-scheme: dark` + toggle manual salvo em localStorage.

## Mapeamento de Cores — Dark Mode

### Backgrounds

| Elemento | Classe Tailwind | Hex |
|----------|----------------|-----|
| Pagina (body) | dark:bg-gray-900 | #111827 |
| Sidebar | dark:bg-gray-950 | #030712 |
| Header | dark:bg-gray-900 | #111827 |
| Card | dark:bg-gray-800 | #1F2937 |
| Card hover | dark:bg-gray-750 | #1a2332 |
| Modal overlay | dark:bg-black/70 | rgba(0,0,0,0.7) |
| Modal content | dark:bg-gray-800 | #1F2937 |
| Dropdown | dark:bg-gray-800 | #1F2937 |
| Input | dark:bg-gray-900 | #111827 |
| Input disabled | dark:bg-gray-800 | #1F2937 |
| Table header | dark:bg-gray-800/50 | rgba(31,41,55,0.5) |
| Table row hover | dark:bg-gray-700/50 | rgba(55,65,81,0.5) |
| Toast | dark:bg-gray-800 | #1F2937 |
| Badge bg | dark:bg-{color}-500/10 | variavel |
| Skeleton | dark:bg-gray-700 | #374151 |

### Textos

| Elemento | Classe | Hex |
|----------|--------|-----|
| Titulo principal | dark:text-white | #FFFFFF |
| Texto body | dark:text-gray-200 | #E5E7EB |
| Texto secundario | dark:text-gray-400 | #9CA3AF |
| Texto placeholder | dark:text-gray-500 | #6B7280 |
| Texto disabled | dark:text-gray-600 | #4B5563 |
| Link | dark:text-primary-400 | #818CF8 |
| Link hover | dark:text-primary-300 | #A5B4FC |
| Texto erro | dark:text-danger-400 | #F87171 |
| Texto sucesso | dark:text-success-400 | #34D399 |

### Bordas

| Elemento | Classe | Hex |
|----------|--------|-----|
| Borda padrao | dark:border-gray-700 | #374151 |
| Borda sutil | dark:border-gray-800 | #1F2937 |
| Borda forte | dark:border-gray-600 | #4B5563 |
| Borda focus | dark:ring-primary-400 | #818CF8 |
| Divider | dark:border-gray-700 | #374151 |

### Sidebar (Dark)

```html
<aside class="bg-white dark:bg-gray-950 border-r border-gray-200 dark:border-gray-800 w-64">
  <div class="px-4 py-5 border-b border-gray-200 dark:border-gray-800">
    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">ZyraVision</span>
  </div>

  <nav class="px-3 py-4 space-y-1">
    <!-- Item ativo -->
    <a class="flex items-center px-3 py-2 text-sm font-medium rounded-lg
              bg-primary-50 text-primary-700
              dark:bg-primary-500/10 dark:text-primary-400">
      <Icon class="h-5 w-5 mr-3" />
      Dashboard
    </a>

    <!-- Item inativo -->
    <a class="flex items-center px-3 py-2 text-sm font-medium rounded-lg
              text-gray-600 hover:bg-gray-50 hover:text-gray-900
              dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
      <Icon class="h-5 w-5 mr-3" />
      Cameras
    </a>
  </nav>
</aside>
```

### Video Player (Dark)

O player de video sempre usa fundo escuro, independente do tema:

```html
<div class="bg-black rounded-lg overflow-hidden aspect-video">
  <!-- Player HLS/WebRTC -->
  <div class="absolute top-2 left-2 flex items-center gap-1">
    <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
    <span class="text-xs font-medium text-white">LIVE</span>
  </div>
</div>
```

## Status de Camera (Dark)

| Status | Badge Classes |
|--------|--------------|
| Online | dark:bg-success-500/10 dark:text-success-400 |
| Offline | dark:bg-danger-500/10 dark:text-danger-400 |
| Alerta | dark:bg-warning-500/10 dark:text-warning-400 |
| Gravando | dark:bg-danger-500/10 dark:text-danger-400 + pulse |

## Sombras no Dark Mode

No dark mode, sombras sao menos efetivas. Usar bordas sutis:

```
Light: shadow-sm
Dark:  shadow-none border border-gray-700 (ou border-gray-800)
```

## Toggle de Tema

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue'

const isDark = ref(false)

onMounted(() => {
  isDark.value = localStorage.getItem('theme') === 'dark'
    || (!localStorage.getItem('theme')
        && window.matchMedia('(prefers-color-scheme: dark)').matches)
  updateTheme()
})

function toggleTheme() {
  isDark.value = !isDark.value
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
  updateTheme()
}

function updateTheme() {
  document.documentElement.classList.toggle('dark', isDark.value)
}
</script>
```

## Regras Dark Mode

- TODA classe visual deve ter variante `dark:`
- Video players SEMPRE em fundo preto (independente do tema)
- Sombras substituidas por bordas no dark
- Ring-offset deve usar `dark:ring-offset-gray-900`
- NUNCA usar branco puro (#FFF) em textos dark — usar gray-200
