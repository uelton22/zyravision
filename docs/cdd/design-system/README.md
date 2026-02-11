# ZyraVision — Design System

## Visao Geral

Este Design System e a **fonte unica da verdade visual** do ZyraVision. TODA interface deve seguir estas especificacoes rigorosamente.

## Estrutura

```
design-system/
  foundations/          # Tokens base: cores, tipografia, espacamento
    colors.md           # Paleta completa (light + dark)
    typography.md       # Fontes, pesos, tamanhos
    spacing.md          # Escala de espacamento
    shadows-borders.md  # Sombras, bordas, radius
    icons.md            # Biblioteca de icones
  themes/               # Temas da aplicacao
    light-theme.md      # Tema claro (padrao)
    dark-theme.md       # Tema escuro
  components/           # Especificacao de componentes
    buttons.md          # ZyButton
    inputs.md           # ZyInput, ZySelect, ZyCheckbox, ZySwitch
    tables.md           # ZyTable + ZyPagination
    cards.md            # ZyCard
    modals.md           # ZyModal, ZyDialog, ZyConfirm
    alerts-toasts.md    # ZyAlert, ZyToast
    navigation.md       # ZySidebar, ZyHeader, ZyBreadcrumb
    badges-status.md    # ZyBadge, ZyStatusIndicator
    pagination.md       # ZyPagination
  patterns/             # Padroes de layout e paginas
    landing-page.md     # Landing page comercial (13 secoes)
    layouts.md          # Layouts base (auth, dashboard, fullscreen)
    crud-pages.md       # Padrao de paginas CRUD
    forms.md            # Padrao de formularios
    dashboards.md       # Padrao de dashboards
    responsive-mobile.md # Padroes responsivos
  tokens/               # Design tokens em formato consumivel
    design-tokens.json  # Tokens exportados
```

## Principios

1. **Consistencia** — Toda tela usa os mesmos componentes
2. **Reutilizacao** — Componentes compartilhados, nunca duplicados
3. **Acessibilidade** — WCAG 2.1 AA minimo
4. **Responsividade** — Mobile-first, funcional em todos os tamanhos
5. **Tema dual** — Light e Dark mode nativos
6. **Performance** — Componentes otimizados, lazy loading

## Regras para IA

- Criar Design System ANTES de qualquer tela
- NUNCA criar CSS inline ou estilos ad-hoc
- SEMPRE usar componentes Zy* existentes
- Se precisar de novo componente, documentar aqui PRIMEIRO
- Alteracoes visuais SOMENTE via Design System
- Toda a aplicacao herda automaticamente as mudancas
