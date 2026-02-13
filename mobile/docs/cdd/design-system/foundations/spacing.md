# Design System — Espacamento (ZySpacing)

## Escala de Espacamento

Base unit: 4px

| Token | Valor | Uso |
|-------|-------|-----|
| `xs` | 4px | Espacamento minimo (entre icone e texto) |
| `sm` | 8px | Padding interno compacto |
| `md` | 12px | Padding padrao de badges, tags |
| `base` | 16px | Padding padrao de cards, botoes |
| `lg` | 20px | Espacamento entre secoes menores |
| `xl` | 24px | Padding de containers |
| `2xl` | 32px | Espacamento entre secoes |
| `3xl` | 40px | Espacamento de pagina |
| `4xl` | 48px | Espacamento grande |
| `5xl` | 64px | Margem de topo/fundo de pagina |

## Border Radius

| Token | Valor | Uso |
|-------|-------|-----|
| `radiusSm` | 6px | Badges, tags |
| `radiusMd` | 8px | Botoes, inputs, padrao |
| `radiusLg` | 12px | Cards |
| `radiusXl` | 16px | Modals, bottom sheets |
| `radiusFull` | 9999px | Avatares, icones circulares |

## Shadows

| Token | Light Mode | Dark Mode | Uso |
|-------|-----------|-----------|-----|
| `shadowSm` | 0 1px 2px rgba(0,0,0,0.05) | 0 1px 2px rgba(0,0,0,0.3) | Botoes, badges |
| `shadowMd` | 0 4px 6px rgba(0,0,0,0.1) | 0 4px 6px rgba(0,0,0,0.4) | Cards |
| `shadowLg` | 0 10px 15px rgba(0,0,0,0.1) | 0 10px 15px rgba(0,0,0,0.5) | Modals, dropdowns |
| `shadowXl` | 0 20px 25px rgba(0,0,0,0.1) | 0 20px 25px rgba(0,0,0,0.5) | Dialogs |

## Screen Padding

| Contexto | Valor |
|----------|-------|
| Page horizontal padding | 16px (phone), 24px (tablet) |
| Page top padding | 16px |
| Section spacing | 24px |
| List item spacing | 12px |
| Card internal padding | 16px |
| AppBar height | 56px |
| Bottom nav height | 64px |
| FAB margin | 16px |

---

## Implementacao Dart

```dart
// lib/core/theme/zy_spacing.dart

class ZySpacing {
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double base = 16;
  static const double lg = 20;
  static const double xl = 24;
  static const double xxl = 32;
  static const double xxxl = 40;
  static const double xxxxl = 48;
  static const double xxxxxl = 64;

  // Border Radius
  static const double radiusSm = 6;
  static const double radiusMd = 8;
  static const double radiusLg = 12;
  static const double radiusXl = 16;
  static const double radiusFull = 9999;

  // Screen
  static const double screenPaddingPhone = 16;
  static const double screenPaddingTablet = 24;
  static const double sectionSpacing = 24;
  static const double listItemSpacing = 12;
  static const double cardPadding = 16;

  // EdgeInsets helpers
  static const screenPhone = EdgeInsets.symmetric(horizontal: 16);
  static const screenTablet = EdgeInsets.symmetric(horizontal: 24);
  static const cardInsets = EdgeInsets.all(16);
  static const listInsets = EdgeInsets.symmetric(horizontal: 16, vertical: 8);
}
```

## Regras
- SEMPRE usar tokens de spacing
- NUNCA usar valores magicos (7, 13, 22)
- Manter consistencia vertical com a escala base-4
- Cards: padding 16px, border-radius 12px
- Listas: spacing 12px entre itens
