# Design System — Navegacao (ZyBottomNav, ZyAppBar, ZyDrawer)

## ZyBottomNav

Barra de navegacao inferior principal.

### Layout

```
┌────────┬─────────┬──────────┬──────────┬──────────┐
│  🏠    │  📹     │  ▶️      │  🔔     │  ⚙️     │
│ Home   │ Cameras │ Gravacoes│ Alertas  │ Config   │
└────────┴─────────┴──────────┴──────────┴──────────┘
```

### Tabs

| Index | Label | Icone (inativo) | Icone (ativo) | Rota |
|-------|-------|----------------|---------------|------|
| 0 | Home | `Icons.dashboard_outlined` | `Icons.dashboard` | /dashboard |
| 1 | Cameras | `Icons.videocam_outlined` | `Icons.videocam` | /cameras |
| 2 | Gravacoes | `Icons.play_circle_outline` | `Icons.play_circle` | /recordings |
| 3 | Alertas | `Icons.notifications_outlined` | `Icons.notifications` | /alerts |
| 4 | Config | `Icons.settings_outlined` | `Icons.settings` | /settings |

### Props

```dart
class ZyBottomNav extends StatelessWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;
  final int alertBadgeCount;          // Badge vermelho em Alertas
}
```

### Visual
- Altura: 64px
- Background: surface
- Border top: 1px border
- Label ativo: primary, labelSmall Medium
- Label inativo: textTertiary, labelSmall
- Icone ativo: primary, filled
- Icone inativo: textTertiary, outlined
- Badge: circulo vermelho 18px com numero branco
- Animacao: suave (200ms) ao trocar tab
- Safe area: respeitar notch/home indicator

---

## ZyAppBar

Barra superior de navegacao.

### Variantes

**1. Main (tela principal)**
```
┌──────────────────────────────────────┐
│  ZyraVision    [🔍] [👤]            │
└──────────────────────────────────────┘
```

**2. Sub-page (tela interna)**
```
┌──────────────────────────────────────┐
│  ← Camera Estacionamento    [⋮]     │
└──────────────────────────────────────┘
```

**3. Live View (streaming)**
```
┌──────────────────────────────────────┐
│  ← Estacionamento  [HD] [PiP] [⛶]  │
└──────────────────────────────────────┘
```

### Props

```dart
class ZyAppBar extends StatelessWidget implements PreferredSizeWidget {
  final String title;
  final bool showBack;                // Default: auto (GoRouter)
  final List<Widget>? actions;        // Botoes a direita
  final Widget? leading;              // Override do botao voltar
  final bool transparent;             // Para overlay em video
  final PreferredSizeWidget? bottom;  // Tabs, search bar
}
```

### Visual
- Altura: 56px
- Background: surface (ou transparent para video)
- Title: headingLarge
- Back button: primary, 24px
- Actions: ZyIconButton
- Elevation: 0 (flat) ou shadowSm quando scrolled

---

## ZyDrawer

Menu lateral (para tablet ou configuracoes avancadas).

### Layout

```
┌────────────────────────┐
│  ┌────┐                │
│  │ AV │ Admin User     │  ← Avatar + Nome
│  │    │ admin@zyra.com │
│  └────┘                │
│                        │
│  ─────────────────     │
│                        │
│  🏠  Dashboard         │
│  📹  Cameras           │
│  ▶️  Gravacoes         │
│  🔔  Alertas           │
│  📊  Storage           │
│                        │
│  ─────────────────     │
│                        │
│  👤  Perfil            │
│  ⚙️  Configuracoes    │
│  🚪  Sair              │
│                        │
│  v1.0.0 • ZyraVision   │  ← Versao do app
└────────────────────────┘
```

### Props

```dart
class ZyDrawer extends ConsumerWidget {
  // Auto-injeta dados do usuario via Riverpod
}
```

### Visual
- Largura: 280px
- Background: background
- Avatar: ZyAvatar (circulo, iniciais ou foto)
- Menu items: 48px altura, icone + label
- Item ativo: primaryLight background, primary text
- Separador: Divider com 16px padding
- Versao: labelSmall, textTertiary, bottom

---

## Regras de Navegacao
- Usar GoRouter para toda navegacao
- Bottom nav: apenas nas 5 telas principais
- Sub-pages: sempre com botao voltar
- Deep links: suporte a `zyravision://cameras/{id}`
- Transicoes: slide horizontal para push, fade para tabs
- Preservar estado de tabs (nao recarregar ao voltar)
- Back gesture: nativo (swipe from edge)
