# ZyAlert e ZyToast — Design System ZyraVision

## ZyAlert (Inline)

Alertas inline dentro do conteudo da pagina.

### Variantes

#### Success
```html
<div class="rounded-lg border border-success-200 dark:border-success-500/20
            bg-success-50 dark:bg-success-500/10 p-4">
  <div class="flex items-start gap-3">
    <CheckCircleIcon class="h-5 w-5 text-success-500 mt-0.5 shrink-0" />
    <div>
      <h4 class="text-sm font-medium text-success-800 dark:text-success-300">
        Camera adicionada com sucesso
      </h4>
      <p class="mt-1 text-sm text-success-700 dark:text-success-400">
        A camera "Entrada Principal" foi cadastrada e esta conectando.
      </p>
    </div>
    <button class="ml-auto text-success-500 hover:text-success-600">
      <XMarkIcon class="h-5 w-5" />
    </button>
  </div>
</div>
```

#### Danger
```
bg-danger-50 dark:bg-danger-500/10
border-danger-200 dark:border-danger-500/20
icon: ExclamationCircleIcon text-danger-500
title: text-danger-800 dark:text-danger-300
body: text-danger-700 dark:text-danger-400
```

#### Warning
```
bg-warning-50 dark:bg-warning-500/10
border-warning-200 dark:border-warning-500/20
icon: ExclamationTriangleIcon text-warning-500
title: text-warning-800 dark:text-warning-300
body: text-warning-700 dark:text-warning-400
```

#### Info
```
bg-info-50 dark:bg-info-500/10
border-info-200 dark:border-info-500/20
icon: InformationCircleIcon text-info-500
title: text-info-800 dark:text-info-300
body: text-info-700 dark:text-info-400
```

### Props ZyAlert

```typescript
interface AlertProps {
  variant: 'success' | 'danger' | 'warning' | 'info'
  title?: string
  dismissible?: boolean
}
```

## ZyToast (Notificacao Flutuante)

Notificacoes que aparecem no canto da tela e desaparecem automaticamente.

### Posicao
```
Posicao: top-right (canto superior direito)
Container: fixed top-4 right-4 z-[100] space-y-3
Max visiveis: 5 simultaneos
```

### Estrutura

```html
<div class="pointer-events-auto w-full max-w-sm rounded-lg
            bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700
            ring-1 ring-black/5 overflow-hidden">
  <div class="p-4">
    <div class="flex items-start gap-3">
      <!-- Icone -->
      <CheckCircleIcon class="h-5 w-5 text-success-500 mt-0.5" />

      <!-- Conteudo -->
      <div class="flex-1">
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          Camera conectada
        </p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          "Entrada Principal" esta online.
        </p>
      </div>

      <!-- Fechar -->
      <button class="text-gray-400 hover:text-gray-500">
        <XMarkIcon class="h-5 w-5" />
      </button>
    </div>
  </div>

  <!-- Progress bar (auto-dismiss) -->
  <div class="h-1 bg-success-500" :style="{ width: progress + '%' }"></div>
</div>
```

### Duracao

| Tipo | Duracao | Auto-dismiss |
|------|---------|-------------|
| Success | 3s | Sim |
| Info | 5s | Sim |
| Warning | 8s | Sim |
| Danger | - | Nao (manual) |

### Toasts CFTV Especificos

| Evento | Tipo | Mensagem |
|--------|------|----------|
| Camera online | success | Camera "X" conectada |
| Camera offline | danger | Camera "X" perdeu conexao |
| Gravacao iniciada | info | Gravacao iniciada em "X" |
| Disco quase cheio | warning | Storage atingiu 85% |
| Movimento detectado | info | Movimento em "X" |
| Alerta IA | warning | Pessoa detectada em "X" |

### Composable useToast

```typescript
const toast = useToast()

toast.success('Camera adicionada com sucesso')
toast.error('Falha ao conectar camera')
toast.warning('Disco quase cheio (85%)')
toast.info('Gravacao iniciada')
```

## Regras
- Toasts: maximo 5 visiveis
- Toasts de erro: NUNCA auto-dismiss
- Alerts inline: usar em formularios e paginas
- Toasts: usar para feedback de acoes
- SEMPRE ter icone indicando tipo
- SEMPRE ter botao de fechar
