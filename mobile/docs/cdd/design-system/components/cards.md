# Design System — Cards (ZyCard, ZyCameraCard, ZyStatCard, ZyAlertCard)

## ZyCard

Card base reutilizavel para agrupar conteudo.

### Props

```dart
class ZyCard extends StatelessWidget {
  final Widget child;
  final EdgeInsets? padding;          // Default: 16px all
  final VoidCallback? onTap;          // Se clicavel
  final Color? backgroundColor;       // Default: surface
  final double? borderRadius;         // Default: 12px
  final bool elevated;                // Default: true (shadow)
  final Widget? header;               // Titulo/acoes no topo
  final Widget? footer;               // Acoes no rodape
}
```

### Visual
- Background: surface (light: #F8FAFC, dark: #1E293B)
- Border: 1px border color
- Border radius: 12px
- Shadow: shadowMd (quando elevated=true)
- Padding interno: 16px

---

## ZyCameraCard

Card especializado para exibir camera na listagem.

### Layout

```
┌──────────────────────────────┐
│  ┌────────────────────────┐  │
│  │                        │  │
│  │     THUMBNAIL          │  │  ← 16:9 aspect ratio
│  │     (snapshot)         │  │
│  │                        │  │
│  │  ● LIVE    ⬤ REC      │  │  ← Badges sobrepostos
│  └────────────────────────┘  │
│                              │
│  Camera Estacionamento       │  ← Nome (headingSmall)
│  192.168.1.100               │  ← IP (bodySmall, mono, tertiary)
│                              │
│  ● Online  │  1080p  │ 30fps│  ← Status bar
└──────────────────────────────┘
```

### Props

```dart
class ZyCameraCard extends StatelessWidget {
  final Camera camera;
  final VoidCallback? onTap;
  final VoidCallback? onLongPress;
  final bool showStatus;              // Default: true
  final bool showThumbnail;           // Default: true
  final ZyCameraCardSize size;        // compact, normal, expanded
}
```

### Badges no Thumbnail

| Badge | Condicao | Cor | Posicao |
|-------|----------|-----|---------|
| LIVE | stream ativo | success | Top-left |
| REC | is_recording | danger (pulse) | Top-right |
| OFFLINE | !is_online | danger | Center |
| HD/FHD/4K | resolucao | surfaceVariant | Bottom-right |

### Tamanhos

| Size | Thumbnail Height | Uso |
|------|-----------------|-----|
| compact | 80px | Lista horizontal |
| normal | 120px | Grid 2 colunas |
| expanded | 180px | Grid 1 coluna, destaque |

---

## ZyStatCard

Card para exibir estatistica numerica no dashboard.

### Layout

```
┌──────────────────────┐
│  📹  Cameras Online  │  ← Icone + Label (bodySmall)
│                      │
│  42                  │  ← Valor (displayMedium)
│  de 50               │  ← Contexto (bodySmall, tertiary)
│                      │
│  ████████░░  84%     │  ← Progress bar (opcional)
└──────────────────────┘
```

### Props

```dart
class ZyStatCard extends StatelessWidget {
  final String label;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color? iconColor;
  final double? progress;             // 0.0 - 1.0 (se != null, mostra barra)
  final Color? progressColor;
  final VoidCallback? onTap;
}
```

---

## ZyAlertCard

Card para exibir alerta/evento na lista de alertas.

### Layout

```
┌──────────────────────────────────────┐
│  🔴  Camera Offline                  │  ← Severity icon + Title
│                                      │
│  Camera "Estacionamento" perdeu      │  ← Descricao
│  conexao as 14:32                    │
│                                      │
│  há 5 minutos         Ver Camera →   │  ← Timestamp + Acao
└──────────────────────────────────────┘
```

### Props

```dart
class ZyAlertCard extends StatelessWidget {
  final AnalyticsEvent event;
  final VoidCallback? onTap;
  final VoidCallback? onDismiss;
  final bool showTimestamp;
}
```

### Cores por Severidade

| Severidade | Icone | Borda Esquerda | Background |
|-----------|-------|----------------|-----------|
| critical | 🔴 danger | danger (4px) | dangerLight |
| warning | 🟡 warning | warning (4px) | warningLight |
| info | 🔵 info | info (4px) | infoLight |

---

## Regras Gerais para Cards
- Border radius: 12px (ZySpacing.radiusLg)
- Padding: 16px (ZySpacing.base)
- Spacing entre cards: 12px (ZySpacing.md)
- Thumbnail: cached_network_image com placeholder skeleton
- Skeleton loading para estados de carregamento
- Long press para acoes contextuais
