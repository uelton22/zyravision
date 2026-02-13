# Design System — Inputs (ZyTextField, ZySearchBar, ZySelect, ZySwitch)

## ZyTextField

Input de texto padrao.

### Layout

```
  Email *
┌──────────────────────────────┐
│  usuario@email.com           │
└──────────────────────────────┘
  Informe seu email de acesso
```

### Props

```dart
class ZyTextField extends StatelessWidget {
  final String? label;               // Label acima do input
  final String? hint;                // Placeholder
  final String? helperText;          // Texto auxiliar abaixo
  final String? errorText;           // Erro de validacao (vermelho)
  final TextEditingController? controller;
  final TextInputType keyboardType;  // text, email, number, etc.
  final bool obscureText;            // Para senhas
  final bool readOnly;
  final bool required;               // Mostra asterisco
  final IconData? prefixIcon;        // Icone a esquerda
  final Widget? suffixWidget;        // Widget a direita (toggle senha, clear)
  final int maxLines;                // Default: 1
  final ValueChanged<String>? onChanged;
  final VoidCallback? onTap;
  final FocusNode? focusNode;
  final TextInputAction? textInputAction;
  final List<String>? autofillHints;
}
```

### Visual

| Propriedade | Valor |
|------------|-------|
| Altura | 48px (input area) |
| Border | 1px border color |
| Border focus | 2px primary |
| Border error | 2px danger |
| Border radius | 8px (radiusMd) |
| Padding horizontal | 12px |
| Label | bodySmall, textSecondary |
| Input text | bodyMedium, textPrimary |
| Hint | bodyMedium, textTertiary |
| Error text | bodySmall, danger |
| Helper text | bodySmall, textTertiary |

### Estados
- **Default:** border color, hint visivel
- **Focused:** border primary 2px, label highlighted
- **Error:** border danger 2px, error text abaixo
- **Disabled:** opacity 50%, background surfaceVariant
- **Read-only:** background surfaceVariant, sem borda focus

---

## ZySearchBar

Barra de busca com debounce.

### Layout

```
┌──────────────────────────────┐
│  🔍  Buscar cameras...    ✕  │
└──────────────────────────────┘
```

### Props

```dart
class ZySearchBar extends StatelessWidget {
  final String hint;                 // Default: "Buscar..."
  final ValueChanged<String> onChanged;
  final VoidCallback? onClear;
  final Duration debounce;           // Default: 300ms
  final bool autofocus;
  final TextEditingController? controller;
}
```

### Visual
- Altura: 44px
- Background: surfaceVariant
- Border radius: radiusFull (9999px)
- Icone search: textTertiary
- Botao clear: aparece quando ha texto
- Debounce: 300ms antes de emitir onChanged

---

## ZySelect

Dropdown/select padrao.

### Layout

```
  Fabricante
┌──────────────────────────────┐
│  Hikvision                 ▼ │
└──────────────────────────────┘
```

### Props

```dart
class ZySelect<T> extends StatelessWidget {
  final String? label;
  final String? hint;
  final T? value;
  final List<ZySelectOption<T>> options;
  final ValueChanged<T?> onChanged;
  final String? errorText;
  final bool required;
  final bool disabled;
}

class ZySelectOption<T> {
  final T value;
  final String label;
  final String? description;
  final IconData? icon;
}
```

### Visual
- Mesmo estilo visual do ZyTextField
- Bottom sheet no mobile (nao dropdown nativo)
- Opcoes com search quando > 10 itens
- Checkmark na opcao selecionada

---

## ZySwitch

Toggle switch com label.

### Layout

```
┌────────────────────────────────────────┐
│  Gravacao Continua          [====●]    │
│  Gravar automaticamente ao conectar    │
└────────────────────────────────────────┘
```

### Props

```dart
class ZySwitch extends StatelessWidget {
  final String label;
  final String? description;         // Texto auxiliar
  final bool value;
  final ValueChanged<bool> onChanged;
  final bool disabled;
}
```

### Visual
- Track: 50x28px
- Thumb: 24px
- Active: primary
- Inactive: border color
- Label: bodyMedium
- Description: bodySmall, textTertiary

---

## Regras Gerais para Inputs
- Todos devem ter label visivel (acessibilidade)
- Autofill hints para inputs de login (email, password)
- Keyboard type correto (email, phone, number)
- TextInputAction correto (next, done, search)
- Focus traversal logico (tab order)
- Validacao visual imediata (onChange ou onBlur)
