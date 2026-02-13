# Design System — Cores (ZyColors)

## Paleta Principal

### Brand Colors
| Token | Light Mode | Dark Mode | Uso |
|-------|-----------|-----------|-----|
| `primary` | #2563EB | #3B82F6 | Acoes principais, links, destaque |
| `primaryLight` | #DBEAFE | #1E3A5F | Background de badges, highlights |
| `primaryDark` | #1D4ED8 | #60A5FA | Hover, pressed states |
| `onPrimary` | #FFFFFF | #FFFFFF | Texto sobre primary |

### Semantic Colors
| Token | Light Mode | Dark Mode | Uso |
|-------|-----------|-----------|-----|
| `success` | #16A34A | #22C55E | Online, gravando, operacional |
| `successLight` | #DCFCE7 | #14532D | Background success |
| `warning` | #D97706 | #F59E0B | Degradado, atencao |
| `warningLight` | #FEF3C7 | #78350F | Background warning |
| `danger` | #DC2626 | #EF4444 | Offline, erro, critico |
| `dangerLight` | #FEE2E2 | #7F1D1D | Background danger |
| `info` | #0EA5E9 | #38BDF8 | Informacao, dica |
| `infoLight` | #E0F2FE | #0C4A6E | Background info |

### Neutrals (Surfaces & Text)
| Token | Light Mode | Dark Mode | Uso |
|-------|-----------|-----------|-----|
| `background` | #FFFFFF | #0F172A | Fundo principal |
| `surface` | #F8FAFC | #1E293B | Cards, areas elevadas |
| `surfaceVariant` | #F1F5F9 | #334155 | Agrupamentos, sidebars |
| `border` | #E2E8F0 | #475569 | Bordas, separadores |
| `borderLight` | #F1F5F9 | #334155 | Bordas sutis |
| `textPrimary` | #0F172A | #F8FAFC | Texto principal |
| `textSecondary` | #64748B | #94A3B8 | Texto secundario |
| `textTertiary` | #94A3B8 | #64748B | Texto desabilitado, hints |
| `textOnColor` | #FFFFFF | #FFFFFF | Texto sobre cor |

### Status de Camera
| Token | Cor | Uso |
|-------|-----|-----|
| `statusOnline` | success (#16A34A) | Camera online |
| `statusOffline` | danger (#DC2626) | Camera offline |
| `statusRecording` | danger (#DC2626) | Gravando (pulse animation) |
| `statusDegraded` | warning (#D97706) | Instavel |
| `statusUnknown` | textTertiary (#94A3B8) | Desconhecido |

### Health Status
| Status | Cor | Badge |
|--------|-----|-------|
| healthy | success | Verde |
| degraded | warning | Amarelo |
| offline | danger | Vermelho |
| unknown | textTertiary | Cinza |

---

## Implementacao Dart

```dart
// lib/core/theme/zy_colors.dart

import 'package:flutter/material.dart';

class ZyColors {
  // Brand
  static const primary = Color(0xFF2563EB);
  static const primaryLight = Color(0xFFDBEAFE);
  static const primaryDark = Color(0xFF1D4ED8);
  static const onPrimary = Color(0xFFFFFFFF);

  // Semantic
  static const success = Color(0xFF16A34A);
  static const successLight = Color(0xFFDCFCE7);
  static const warning = Color(0xFFD97706);
  static const warningLight = Color(0xFFFEF3C7);
  static const danger = Color(0xFFDC2626);
  static const dangerLight = Color(0xFFFEE2E2);
  static const info = Color(0xFF0EA5E9);
  static const infoLight = Color(0xFFE0F2FE);

  // Neutrals (Light)
  static const backgroundLight = Color(0xFFFFFFFF);
  static const surfaceLight = Color(0xFFF8FAFC);
  static const surfaceVariantLight = Color(0xFFF1F5F9);
  static const borderLight = Color(0xFFE2E8F0);
  static const textPrimaryLight = Color(0xFF0F172A);
  static const textSecondaryLight = Color(0xFF64748B);
  static const textTertiaryLight = Color(0xFF94A3B8);

  // Neutrals (Dark)
  static const backgroundDark = Color(0xFF0F172A);
  static const surfaceDark = Color(0xFF1E293B);
  static const surfaceVariantDark = Color(0xFF334155);
  static const borderDark = Color(0xFF475569);
  static const textPrimaryDark = Color(0xFFF8FAFC);
  static const textSecondaryDark = Color(0xFF94A3B8);
  static const textTertiaryDark = Color(0xFF64748B);
}
```

## Regras
- NUNCA usar cores hardcoded nos widgets
- SEMPRE referenciar ZyColors ou Theme.of(context)
- Contraste minimo: 4.5:1 para texto, 3:1 para elementos grandes
- Teste AMBOS os temas antes de finalizar
