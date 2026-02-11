# Tipografia — ZyraVision Design System

## Font Family

```
Primaria: 'Inter', system-ui, -apple-system, sans-serif
Mono:     'JetBrains Mono', 'Fira Code', monospace
```

Inter e a fonte principal — limpa, legivel e otimizada para interfaces.
JetBrains Mono para codigo, URLs RTSP, logs e dados tecnicos.

## Tailwind Config

```typescript
// tailwind.config.ts
fontFamily: {
  sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
  mono: ['JetBrains Mono', 'Fira Code', 'monospace'],
},
```

## Escala Tipografica

| Token | Tailwind | Size | Line Height | Peso | Uso |
|-------|----------|------|-------------|------|-----|
| display-lg | text-4xl | 36px | 40px | 700 (bold) | Titulos de pagina principal |
| display-md | text-3xl | 30px | 36px | 700 (bold) | Titulos de secao |
| heading-lg | text-2xl | 24px | 32px | 600 (semibold) | Titulos de card/modal |
| heading-md | text-xl | 20px | 28px | 600 (semibold) | Subtitulos |
| heading-sm | text-lg | 18px | 28px | 600 (semibold) | Labels de secao |
| body-lg | text-base | 16px | 24px | 400 (normal) | Texto principal |
| body-md | text-sm | 14px | 20px | 400 (normal) | Texto secundario, tabelas |
| body-sm | text-xs | 12px | 16px | 400 (normal) | Captions, timestamps |
| label | text-sm | 14px | 20px | 500 (medium) | Labels de formulario |
| mono | text-sm font-mono | 14px | 20px | 400 | URLs, IPs, codigo |

## Pesos

```
400 — Normal (body text)
500 — Medium (labels, navigation)
600 — Semibold (headings, buttons)
700 — Bold (display titles, emphasis)
```

## Regras de Uso

### Titulos de Pagina
```html
<h1 class="text-2xl font-bold text-gray-900 dark:text-white">
  Gerenciar Cameras
</h1>
```

### Subtitulos
```html
<h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
  Cameras Ativas
</h2>
```

### Texto Body
```html
<p class="text-sm text-gray-600 dark:text-gray-400">
  Descricao do recurso
</p>
```

### Labels de Formulario
```html
<label class="text-sm font-medium text-gray-700 dark:text-gray-300">
  Nome da Camera
</label>
```

### Dados Tecnicos (RTSP, IPs)
```html
<code class="text-sm font-mono text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
  rtsp://192.168.1.100/stream1
</code>
```

## Truncamento

- Textos longos: `truncate` (single line) ou `line-clamp-2` (multi line)
- Nomes de cameras: max 2 linhas
- URLs: sempre truncar com `truncate`

## Responsividade

| Breakpoint | Display | Heading | Body |
|------------|---------|---------|------|
| Mobile | text-2xl | text-lg | text-sm |
| Tablet | text-3xl | text-xl | text-sm |
| Desktop | text-4xl | text-2xl | text-base |
