# ZyButton — Design System ZyraVision

## Variantes

### Primary (Acao principal)
```
bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500
dark:bg-primary-500 dark:hover:bg-primary-400
```
Uso: Salvar, Criar, Confirmar

### Secondary (Acao secundaria)
```
bg-white text-gray-700 border border-gray-300 hover:bg-gray-50
dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700
```
Uso: Cancelar, Voltar, Filtrar

### Danger (Acao destrutiva)
```
bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500
dark:bg-danger-500 dark:hover:bg-danger-400
```
Uso: Excluir, Remover, Desativar

### Ghost (Sem fundo)
```
text-gray-600 hover:bg-gray-100 hover:text-gray-900
dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white
```
Uso: Acoes em tabelas, icones de acao

### Outline (Contorno)
```
border border-primary-600 text-primary-600 hover:bg-primary-50
dark:border-primary-400 dark:text-primary-400 dark:hover:bg-primary-500/10
```
Uso: Acoes alternativas, exportar

## Tamanhos

| Tamanho | Classes | Uso |
|---------|---------|-----|
| sm | px-3 py-1.5 text-xs | Acoes em tabela, badges |
| md | px-4 py-2 text-sm | Padrao para formularios |
| lg | px-6 py-3 text-base | CTAs, acoes destacadas |

## Estados

### Disabled
```
opacity-50 cursor-not-allowed pointer-events-none
```

### Loading
```
cursor-wait relative
<!-- Spinner interno -->
<svg class="animate-spin h-4 w-4" .../>
```

### Com Icone
```html
<ZyButton>
  <PlusIcon class="h-5 w-5 mr-1.5" />
  Adicionar Camera
</ZyButton>
```

### Icon Only
```html
<ZyButton variant="ghost" size="sm" aria-label="Editar">
  <PencilIcon class="h-5 w-5" />
</ZyButton>
```

## Implementacao Vue

```vue
<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  type?: 'button' | 'submit' | 'reset'
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  size: 'md',
  type: 'button',
})
</script>
```

## Regras
- SEMPRE ter `type` definido (prevenir submit acidental)
- Botao primario: maximo 1 por formulario/secao
- Icon-only buttons: SEMPRE ter `aria-label`
- Loading: desabilitar interacao e mostrar spinner
- Danger: confirmar acao com modal antes de executar
