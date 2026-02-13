# Design System — Botoes (ZyButton, ZyIconButton, ZyFab)

## ZyButton

### Variantes

| Variante | Background | Texto | Borda | Uso |
|----------|-----------|-------|-------|-----|
| `primary` | primary (#2563EB) | white | none | Acao principal (Salvar, Entrar) |
| `secondary` | surfaceVariant | textPrimary | border | Acao secundaria (Cancelar) |
| `danger` | danger (#DC2626) | white | none | Acao destrutiva (Excluir) |
| `ghost` | transparent | primary | none | Acao terciaria (Ver mais) |
| `outline` | transparent | primary | primary | Acao com enfase media |

### Tamanhos

| Tamanho | Altura | Padding H | Font | Icon |
|---------|--------|----------|------|------|
| `sm` | 32px | 12px | 12px Medium | 16px |
| `md` | 40px | 16px | 14px Medium | 20px |
| `lg` | 48px | 20px | 16px Medium | 24px |

### Estados

| Estado | Visual |
|--------|--------|
| Default | Cores normais |
| Hover/Pressed | Escurecer 10% |
| Disabled | Opacity 50%, cursor proibido |
| Loading | Spinner circular + texto "Carregando..." |

### Props

```dart
class ZyButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final ZyButtonVariant variant;     // primary, secondary, danger, ghost, outline
  final ZyButtonSize size;           // sm, md, lg
  final bool loading;                // Mostra spinner
  final bool disabled;               // Desabilita interacao
  final IconData? leadingIcon;       // Icone antes do texto
  final IconData? trailingIcon;      // Icone depois do texto
  final bool fullWidth;              // Ocupa largura total
}
```

### Acessibilidade
- `semanticsLabel` automatico do `label`
- Focus ring visivel
- Min touch target: 48x48px

---

## ZyIconButton

Botao apenas com icone (sem texto).

### Props

```dart
class ZyIconButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onPressed;
  final ZyButtonVariant variant;
  final ZyButtonSize size;
  final String semanticLabel;        // OBRIGATORIO
  final bool loading;
  final bool disabled;
  final Color? backgroundColor;      // Override opcional
}
```

### Tamanhos

| Tamanho | Area | Icon Size |
|---------|------|-----------|
| `sm` | 32x32 | 16px |
| `md` | 40x40 | 20px |
| `lg` | 48x48 | 24px |

---

## ZyFab (Floating Action Button)

### Props

```dart
class ZyFab extends StatelessWidget {
  final IconData icon;
  final String? label;               // Se presente, extended FAB
  final VoidCallback onPressed;
  final Color? backgroundColor;      // Default: primary
  final bool mini;                   // 40px ao inves de 56px
}
```

### Posicionamento
- Canto inferior direito
- Margem: 16px
- Acima do bottom navigation

---

## Exemplos de Uso

```dart
// Primario
ZyButton(label: 'Entrar', onPressed: () => login())

// Com icone
ZyButton(
  label: 'Iniciar Stream',
  leadingIcon: Icons.play_arrow,
  onPressed: () => startStream(),
)

// Loading
ZyButton(label: 'Salvando...', loading: true)

// Danger
ZyButton(
  label: 'Excluir Camera',
  variant: ZyButtonVariant.danger,
  leadingIcon: Icons.delete_outline,
  onPressed: () => confirmDelete(),
)

// Full width
ZyButton(
  label: 'Fazer Login',
  fullWidth: true,
  size: ZyButtonSize.lg,
  onPressed: () => login(),
)

// Icon button
ZyIconButton(
  icon: Icons.fullscreen,
  semanticLabel: 'Tela cheia',
  onPressed: () => toggleFullscreen(),
)
```
