# Design System — Feedback (ZyBadge, ZyToast, ZySkeleton, ZyEmptyState, ZyLoadingIndicator)

## ZyBadge

Badge/tag para status e labels.

### Variantes

| Variante | Background | Text | Uso |
|----------|-----------|------|-----|
| success | successLight | success | Online, Saudavel |
| danger | dangerLight | danger | Offline, Erro |
| warning | warningLight | warning | Degradado, Atencao |
| info | infoLight | info | Informacao |
| neutral | surfaceVariant | textSecondary | Default |
| primary | primaryLight | primary | Destaque |

### Props

```dart
class ZyBadge extends StatelessWidget {
  final String label;
  final ZyBadgeVariant variant;       // success, danger, warning, info, neutral, primary
  final ZyBadgeSize size;             // sm, md
  final IconData? icon;               // Icone antes do label
  final bool pulse;                   // Animacao pulse (para REC)
}
```

### Tamanhos

| Size | Altura | Padding H | Font |
|------|--------|----------|------|
| sm | 20px | 6px | 11px (labelSmall) |
| md | 24px | 8px | 12px (labelMedium) |

### Visual
- Border radius: radiusSm (6px)
- Font weight: Medium (500)
- Pulse: opacity animation 0.4-1.0, 1.5s cycle

---

## ZyToast

Mensagem temporaria (snackbar aprimorado).

### Variantes

| Variante | Icone | Background | Uso |
|----------|-------|-----------|-----|
| success | check_circle | success | Acao concluida |
| error | error | danger | Erro |
| warning | warning | warning | Atencao |
| info | info | info | Informacao |

### Props

```dart
class ZyToast {
  static void show(
    BuildContext context, {
    required String message,
    ZyToastVariant variant = ZyToastVariant.info,
    Duration duration = const Duration(seconds: 3),
    String? actionLabel,
    VoidCallback? onAction,
  });
}
```

### Visual
- Posicao: bottom, acima do bottom nav
- Margin: 16px horizontal
- Border radius: radiusMd (8px)
- Padding: 12px horizontal, 10px vertical
- Auto-dismiss: 3s (default)
- Swipe to dismiss
- Max 1 toast visivel por vez

---

## ZySkeleton

Placeholder de carregamento (shimmer effect).

### Tipos

```dart
// Retangulo generico
ZySkeleton.rect(width: 200, height: 20, borderRadius: 4)

// Circulo (avatar)
ZySkeleton.circle(size: 48)

// Camera card placeholder
ZySkeleton.cameraCard()

// Lista de itens
ZySkeleton.list(itemCount: 5, itemHeight: 80)

// Dashboard stat
ZySkeleton.statCard()
```

### Visual
- Background: surfaceVariant
- Shimmer: gradiente animado (200ms shift)
- Border radius: mesmo do componente que substitui
- Cor shimmer: surfaceVariant → border → surfaceVariant

---

## ZyEmptyState

Estado vazio quando nao ha dados.

### Layout

```
        ┌──────────┐
        │  📹 ❌   │  ← Ilustracao/icone grande
        └──────────┘

    Nenhuma camera encontrada

  Adicione sua primeira camera para
  comecar a monitorar.

      [ Adicionar Camera ]
```

### Props

```dart
class ZyEmptyState extends StatelessWidget {
  final String message;
  final String? description;
  final IconData? icon;               // Default: inbox_outlined
  final Widget? illustration;         // SVG/imagem custom
  final String? actionLabel;
  final VoidCallback? onAction;
}
```

### Visual
- Centralizado vertical e horizontal
- Icone: 64px, textTertiary
- Message: headingMedium, textPrimary
- Description: bodyMedium, textSecondary
- Botao: ZyButton primary (se acao definida)
- Padding: 32px horizontal

---

## ZyLoadingIndicator

Indicador de carregamento.

### Variantes

```dart
// Circular (padrao Flutter)
ZyLoadingIndicator.circular(size: 24, color: primary)

// Inline (dentro de botao ou lista)
ZyLoadingIndicator.inline()

// Full screen (overlay)
ZyLoadingIndicator.fullScreen(message: 'Conectando...')

// Pull-to-refresh header
ZyLoadingIndicator.refresh()
```

### Visual
- Circular: CircularProgressIndicator com cor primary
- Tamanho default: 24px
- Full screen: overlay semi-transparente + spinner + mensagem
- Cor: primary (light mode), primaryDark (dark mode)

---

## Regras de Feedback
- Skeleton durante carregamento inicial (NUNCA tela em branco)
- Empty state quando lista vazia (NUNCA lista vazia silenciosa)
- Toast para confirmacao de acoes (Salvo, Excluido, etc.)
- Toast error para falhas de rede
- Loading inline para botoes (NUNCA bloquear tela inteira para acoes simples)
- Full screen loading apenas para auth e sync pesado
