# ADR-006: Design System Zy* Nativo Flutter

## Status
Aceito

## Contexto
O ZyraVision web usa componentes prefixados com `Zy` (ZyButton, ZyCard, etc.)
implementados em Vue 3. O mobile precisa de consistencia visual mas com
componentes nativos Flutter.

## Decisao
Criar **Design System nativo Flutter** com prefixo `Zy`, espelhando a semantica
do web mas adaptado para mobile (gestos, tamanhos de toque, safe areas).

## Componentes

### Foundations (Tokens)
- `ZyColors` — Paleta (primary #2563EB, semantic, neutrals)
- `ZyTypography` — Escala tipografica (Inter font)
- `ZySpacing` — Escala de espacamento (base 4px)
- `ZyIcons` — Catalogo de icones Material

### Widgets (25+)
- Buttons: ZyButton, ZyIconButton, ZyFab
- Cards: ZyCard, ZyCameraCard, ZyStatCard, ZyAlertCard
- Inputs: ZyTextField, ZySearchBar, ZySelect, ZySwitch
- Navigation: ZyBottomNav, ZyAppBar, ZyDrawer
- Feedback: ZyBadge, ZyToast, ZySkeleton, ZyEmptyState, ZyLoadingIndicator
- Media: ZyVideoPlayer, ZyThumbnail, ZyTimeline, ZyPtzControls
- Layout: ZyScaffold, ZyAvatar, ZyDivider

### Temas
- Light Theme (padrao)
- Dark Theme (AMOLED-friendly)
- Switching via Riverpod + SharedPreferences

## Diferencas Web vs Mobile

| Aspecto | Web (Vue) | Mobile (Flutter) |
|---------|-----------|-----------------|
| Prefixo | Zy | Zy (mesmo) |
| Grid | CSS Grid/Flex | Column/Row/GridView |
| Cores | Tailwind tokens | ZyColors constants |
| Fonte | @import Inter | google_fonts package |
| Icones | Lucide Icons | Material Icons |
| Touch target | N/A | Minimo 48px |
| Safe area | N/A | SafeArea widget |
| Scroll | CSS overflow | ListView/CustomScrollView |

## Consequencias
- Manutencao em 2 design systems (web + mobile)
- Cores e semantica iguais, implementacao diferente
- Futuro: design tokens compartilhados via JSON/Figma
