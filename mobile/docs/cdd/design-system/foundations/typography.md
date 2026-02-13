# Design System — Tipografia (ZyTypography)

## Fonte Principal
- **Familia:** Inter (Google Fonts)
- **Fallback:** SF Pro Display (iOS), Roboto (Android)
- **Pesos usados:** 400 (Regular), 500 (Medium), 600 (SemiBold), 700 (Bold)

## Escala Tipografica

### Display
| Token | Tamanho | Peso | Line Height | Uso |
|-------|---------|------|-------------|-----|
| `displayLarge` | 32px | Bold (700) | 40px | Titulo principal (dashboard) |
| `displayMedium` | 28px | Bold (700) | 36px | Titulos de secao |
| `displaySmall` | 24px | SemiBold (600) | 32px | Sub-titulos |

### Heading
| Token | Tamanho | Peso | Line Height | Uso |
|-------|---------|------|-------------|-----|
| `headingLarge` | 20px | SemiBold (600) | 28px | AppBar title |
| `headingMedium` | 18px | SemiBold (600) | 24px | Card titles |
| `headingSmall` | 16px | SemiBold (600) | 22px | Section headers |

### Body
| Token | Tamanho | Peso | Line Height | Uso |
|-------|---------|------|-------------|-----|
| `bodyLarge` | 16px | Regular (400) | 24px | Texto principal |
| `bodyMedium` | 14px | Regular (400) | 20px | Texto padrao |
| `bodySmall` | 12px | Regular (400) | 16px | Texto auxiliar, captions |

### Label
| Token | Tamanho | Peso | Line Height | Uso |
|-------|---------|------|-------------|-----|
| `labelLarge` | 14px | Medium (500) | 20px | Botoes, tabs |
| `labelMedium` | 12px | Medium (500) | 16px | Badges, tags |
| `labelSmall` | 11px | Medium (500) | 14px | Timestamps, metadata |

### Mono
| Token | Tamanho | Peso | Uso |
|-------|---------|------|-----|
| `codeMedium` | 13px | Regular | IPs, URLs, logs |
| `codeSmall` | 11px | Regular | IDs, hashes |

**Familia mono:** JetBrains Mono ou Fira Code

---

## Implementacao Dart

```dart
// lib/core/theme/zy_typography.dart

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class ZyTypography {
  static TextTheme get textTheme => GoogleFonts.interTextTheme(
    const TextTheme(
      displayLarge: TextStyle(fontSize: 32, fontWeight: FontWeight.w700, height: 1.25),
      displayMedium: TextStyle(fontSize: 28, fontWeight: FontWeight.w700, height: 1.29),
      displaySmall: TextStyle(fontSize: 24, fontWeight: FontWeight.w600, height: 1.33),
      headlineLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.w600, height: 1.4),
      headlineMedium: TextStyle(fontSize: 18, fontWeight: FontWeight.w600, height: 1.33),
      headlineSmall: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, height: 1.375),
      bodyLarge: TextStyle(fontSize: 16, fontWeight: FontWeight.w400, height: 1.5),
      bodyMedium: TextStyle(fontSize: 14, fontWeight: FontWeight.w400, height: 1.43),
      bodySmall: TextStyle(fontSize: 12, fontWeight: FontWeight.w400, height: 1.33),
      labelLarge: TextStyle(fontSize: 14, fontWeight: FontWeight.w500, height: 1.43),
      labelMedium: TextStyle(fontSize: 12, fontWeight: FontWeight.w500, height: 1.33),
      labelSmall: TextStyle(fontSize: 11, fontWeight: FontWeight.w500, height: 1.27),
    ),
  );
}
```

## Regras
- SEMPRE usar tokens de tipografia, NUNCA hardcodar tamanhos
- Texto principal: bodyMedium (14px)
- Botoes: labelLarge (14px Medium)
- Labels de form: bodySmall (12px)
- Numeros grandes (stats): displayLarge ou displayMedium
- IPs e URLs: usar fonte mono
