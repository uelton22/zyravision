# ADR-0028: Implementacao do Design System e Tokens

## Status
**Aprovado** — 2026-02-06

## Contexto

A ADR-0011 definiu a necessidade de um Design System. Esta ADR detalha a implementacao tecnica, incluindo design tokens, componentes Vue, temas (light/dark), e sincronizacao visual entre web (Vue) e mobile (Flutter).

### ADRs Relacionadas
- ADR-0011: Design System, UX/UI e Acessibilidade
- ADR-0015: Aplicacao Mobile com Flutter
- ADR-0026: Compatibilidade Mobile Completa

## Decisao

### 1. Design Tokens como Fonte da Verdade

Arquivo centralizado `docs/cdd/design-system/tokens/design-tokens.json` define TODOS os valores visuais. Este arquivo alimenta:

- `tailwind.config.ts` (web)
- `lib/core/theme/tokens.dart` (mobile Flutter)
- Documentacao automatica

### 2. Estrutura de Componentes Vue

```
resources/js/Components/
  UI/                          # Design System Components
    ZyButton.vue               # Botoes
    ZyInput.vue                # Campos de texto
    ZySelect.vue               # Selects
    ZyCheckbox.vue             # Checkboxes
    ZySwitch.vue               # Toggles
    ZyTable.vue                # Tabelas paginadas
    ZyPagination.vue           # Paginacao
    ZyCard.vue                 # Cards
    ZyModal.vue                # Modais
    ZyConfirmDialog.vue        # Dialog de confirmacao
    ZyAlert.vue                # Alertas inline
    ZyToast.vue                # Notificacoes flutuantes
    ZyBadge.vue                # Badges e status
    ZyDropdown.vue             # Menus dropdown
    ZyAvatar.vue               # Avatares
    ZySkeleton.vue             # Loading skeletons
    ZyEmptyState.vue           # Empty states
    index.ts                   # Barrel export
  Layout/
    ZySidebar.vue              # Sidebar navegacao
    ZyHeader.vue               # Header com breadcrumbs
    ZyBreadcrumb.vue           # Breadcrumbs
    ZyPageHeader.vue           # Titulo de pagina + acoes
  Camera/
    CameraCard.vue             # Card de camera no mosaico
    CameraPlayer.vue           # Player HLS/WebRTC
    CameraTimeline.vue         # Timeline de gravacoes
    CameraStatusBadge.vue      # Badge de status
  Stream/
    LivePlayer.vue             # Player ao vivo
    PlaybackPlayer.vue         # Player de gravacao
    MosaicGrid.vue             # Grid de mosaico
```

### 3. Tailwind Config com Tokens

```typescript
// tailwind.config.ts
import tokens from './docs/cdd/design-system/tokens/design-tokens.json'

export default {
  darkMode: 'class',
  content: [
    './resources/js/**/*.{vue,ts}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: tokens.colors,
      fontFamily: tokens.typography.fontFamily,
      borderRadius: tokens.borderRadius,
      boxShadow: tokens.shadows,
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

### 4. Tema Light/Dark

- Toggle via classe `dark` no `<html>`
- Persistencia em `localStorage`
- Deteccao automatica via `prefers-color-scheme`
- TODA classe visual deve ter variante `dark:`

```typescript
// composables/useTheme.ts
export function useTheme() {
  const isDark = ref(false)

  function init() {
    isDark.value = localStorage.getItem('theme') === 'dark'
      || (!localStorage.getItem('theme')
          && window.matchMedia('(prefers-color-scheme: dark)').matches)
    apply()
  }

  function toggle() {
    isDark.value = !isDark.value
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light')
    apply()
  }

  function apply() {
    document.documentElement.classList.toggle('dark', isDark.value)
  }

  return { isDark, toggle, init }
}
```

### 5. Componentes Flutter (Espelho do Web)

```dart
// lib/shared/widgets/
//   zy_button.dart
//   zy_input.dart
//   zy_card.dart
//   zy_badge.dart
//   zy_alert.dart

// lib/core/theme/
//   app_theme.dart        # ThemeData para light e dark
//   app_colors.dart       # Cores do design-tokens.json
//   app_typography.dart   # Tipografia
//   app_spacing.dart      # Espacamentos
```

Os componentes Flutter devem ter aparencia visual identica aos componentes Vue, usando os mesmos tokens.

### 6. Governanca

- Alteracoes visuais SOMENTE via Design System
- Aprovar mudancas no design-tokens.json antes de implementar
- Manter parity visual web ↔ mobile
- Documentar todo novo componente antes de implementar
- Code review especifico para componentes UI

## Alternativas Consideradas

1. **Headless UI + Tailwind** — Parcialmente adotado (para modais, dropdowns)
2. **Vuetify/PrimeVue** — Descartado: dificil customizacao profunda
3. **Storybook** — Futuro: considerar para documentacao interativa

## Consequencias

### Positivas
- Consistencia visual absoluta
- IA segue padroes definidos sem divergir
- Troca de tema (light/dark) com zero retrabalho
- Componentes reutilizaveis reduzem desenvolvimento

### Negativas
- Investimento inicial em criar todos os componentes
- Manter sincronia entre web e mobile requer disciplina
- Design tokens JSON nao e formato padrao universal

## Metricas

- 0 componentes com CSS inline
- 100% das telas usando componentes Zy*
- Paridade visual web ↔ mobile > 95%
- Lighthouse Accessibility score > 90
