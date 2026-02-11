# ZyBadge e ZyStatusIndicator — Design System ZyraVision

## ZyBadge

### Variantes

| Variante | Light Classes | Dark Classes |
|----------|--------------|-------------|
| success | bg-success-50 text-success-700 ring-1 ring-success-600/20 | dark:bg-success-500/10 dark:text-success-400 dark:ring-success-500/20 |
| danger | bg-danger-50 text-danger-700 ring-1 ring-danger-600/20 | dark:bg-danger-500/10 dark:text-danger-400 dark:ring-danger-500/20 |
| warning | bg-warning-50 text-warning-700 ring-1 ring-warning-600/20 | dark:bg-warning-500/10 dark:text-warning-400 dark:ring-warning-500/20 |
| info | bg-info-50 text-info-700 ring-1 ring-info-600/20 | dark:bg-info-500/10 dark:text-info-400 dark:ring-info-500/20 |
| neutral | bg-gray-100 text-gray-700 ring-1 ring-gray-600/20 | dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-500/20 |
| primary | bg-primary-50 text-primary-700 ring-1 ring-primary-600/20 | dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20 |

### Tamanhos

| Size | Classes |
|------|---------|
| xs | px-1.5 py-0.5 text-[10px] |
| sm | px-2 py-0.5 text-xs |
| md | px-2.5 py-1 text-xs |

### Com Dot (Indicador)

```html
<span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium
             rounded-md bg-success-50 text-success-700 ring-1 ring-success-600/20
             dark:bg-success-500/10 dark:text-success-400">
  <span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>
  Online
</span>
```

### Com Pulse (Live/Recording)

```html
<span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-xs font-medium
             rounded-md bg-danger-50 text-danger-700 ring-1 ring-danger-600/20">
  <span class="h-1.5 w-1.5 rounded-full bg-danger-500 animate-pulse"></span>
  LIVE
</span>
```

## ZyStatusIndicator

Indicador visual de status para cameras e streams.

### Status de Camera

| Status | Cor | Icone | Badge |
|--------|-----|-------|-------|
| Online | success | CheckCircleIcon | "Online" |
| Offline | danger | XCircleIcon | "Offline" |
| Connecting | warning | ArrowPathIcon (spin) | "Conectando" |
| Recording | danger (pulse) | - | "REC" |
| Error | danger | ExclamationCircleIcon | "Erro" |
| Maintenance | neutral | WrenchIcon | "Manutencao" |

### Status de Stream

| Status | Cor | Badge |
|--------|-----|-------|
| Live | danger (pulse) | "LIVE" |
| Buffering | warning | "Carregando" |
| Paused | neutral | "Pausado" |
| Stopped | neutral | "Parado" |
| Error | danger | "Erro" |

### Status de Storage

| Status | Cor | Badge |
|--------|-----|-------|
| Healthy (< 70%) | success | "Normal" |
| Warning (70-85%) | warning | "Atencao" |
| Critical (85-95%) | danger | "Critico" |
| Full (> 95%) | danger | "Cheio" |

### Uso em Tabela

```html
<td class="px-6 py-4">
  <ZyBadge :variant="statusVariant" dot>
    {{ statusLabel }}
  </ZyBadge>
</td>
```

## Props

```typescript
interface BadgeProps {
  variant: 'success' | 'danger' | 'warning' | 'info' | 'neutral' | 'primary'
  size?: 'xs' | 'sm' | 'md'
  dot?: boolean
  pulse?: boolean
  removable?: boolean
}
```

## Regras
- Status SEMPRE representado por ZyBadge com cor semantica
- LIVE e REC: usar pulse animation
- Cores semanticas consistentes em todo o sistema
- NUNCA usar cores arbitrarias para status
