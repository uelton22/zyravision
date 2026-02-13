---
name: create-widget
description: Cria um widget do Design System ZyraVision (prefixo Zy). Use quando o usuario pedir para criar componente, widget, botao, card, input.
---
# Criar Widget Zy* — Design System ZyraVision Mobile

## Regras Fundamentais
1. SEMPRE prefixo `Zy` no nome
2. SEMPRE StatelessWidget ou ConsumerWidget
3. SEMPRE parametros tipados com required/optional
4. SEMPRE suportar dark mode (via Theme.of)
5. NUNCA hardcodar cores — usar ZyColors
6. NUNCA hardcodar spacing — usar ZySpacing
7. SEMPRE incluir semanticLabel para acessibilidade

## Template

```dart
import 'package:flutter/material.dart';
import 'package:zyravision/core/theme/zy_colors.dart';
import 'package:zyravision/core/theme/zy_spacing.dart';

enum ZyButtonVariant { primary, secondary, danger, ghost, outline }
enum ZyButtonSize { sm, md, lg }

class ZyButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final ZyButtonVariant variant;
  final ZyButtonSize size;
  final bool loading;
  final bool disabled;
  final IconData? icon;

  const ZyButton({
    super.key,
    required this.label,
    this.onPressed,
    this.variant = ZyButtonVariant.primary,
    this.size = ZyButtonSize.md,
    this.loading = false,
    this.disabled = false,
    this.icon,
  });

  @override
  Widget build(BuildContext context) {
    // Implementation using theme tokens
  }
}
```

## Onde Criar

| Tipo | Pasta |
|------|-------|
| Botoes | lib/ui/widgets/buttons/ |
| Inputs | lib/ui/widgets/inputs/ |
| Cards | lib/ui/widgets/cards/ |
| Feedback | lib/ui/widgets/feedback/ |
| Navegacao | lib/ui/widgets/navigation/ |
| Dialogs | lib/ui/widgets/dialogs/ |
| Media | lib/ui/widgets/media/ |
| Layout | lib/ui/widgets/layout/ |

## Checklist
- [ ] Prefixo `Zy` no nome
- [ ] Parametros tipados
- [ ] Suporta dark mode
- [ ] Usa ZyColors e ZySpacing
- [ ] Acessibilidade (semanticLabel)
- [ ] Estados: enabled, disabled, loading
- [ ] Responsivo
- [ ] Documentacao de parametros
