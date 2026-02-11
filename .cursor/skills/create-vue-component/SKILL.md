---
name: create-vue-component
description: Cria componentes Vue.js padronizados para o Design System do ZyraVision. Use quando o usuario pedir para criar componente, UI element, widget, ou parte visual reutilizavel.
---

# Criar Componente Vue — Design System ZyraVision

## Regras Fundamentais

1. SEMPRE usar Composition API + TypeScript
2. SEMPRE prefixo `Zy` para componentes do Design System
3. SOMENTE Tailwind CSS — NUNCA `<style>`
4. Props tipadas com interface/type
5. Emits tipados
6. Slots para customizacao
7. Suporte a dark mode (`dark:` classes)

## Template do Componente

```vue
<script setup lang="ts">
// 1. Imports
import { computed } from 'vue'

// 2. Types
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
}

// 3. Props com defaults
const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
})

// 4. Emits
const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

// 5. Computed classes
const classes = computed(() => [
  'inline-flex items-center justify-center font-medium rounded-lg transition-colors',
  // Variantes
  variantClasses[props.variant],
  // Tamanhos
  sizeClasses[props.size],
  // Estados
  props.disabled && 'opacity-50 cursor-not-allowed',
  props.loading && 'cursor-wait',
])
</script>

<template>
  <button
    :class="classes"
    :disabled="disabled || loading"
    @click="emit('click', $event)"
  >
    <slot />
  </button>
</template>
```

## Onde Criar

| Tipo | Pasta |
|------|-------|
| UI Base | `resources/js/Components/UI/` |
| Layout | `resources/js/Components/Layout/` |
| Dominio | `resources/js/Components/{Domain}/` |

## Consultar Design System

Antes de criar, verificar:
- `docs/cdd/design-system/components/` — specs dos componentes
- `docs/cdd/design-system/foundations/` — cores, tipografia, espacamento
- `docs/cdd/design-system/themes/` — light e dark mode

## Checklist

- [ ] Prefixo `Zy` no nome
- [ ] `<script setup lang="ts">`
- [ ] Props tipadas com defaults
- [ ] Emits tipados
- [ ] Somente Tailwind CSS
- [ ] Suporte dark mode
- [ ] Responsivo (mobile-first)
- [ ] Estados: hover, focus, disabled, loading
