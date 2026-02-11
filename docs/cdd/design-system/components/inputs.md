# ZyInput, ZySelect, ZyCheckbox, ZySwitch — Design System ZyraVision

## ZyInput (Campo de Texto)

### Classes Base
```
w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm
text-gray-900 placeholder:text-gray-400
focus:ring-2 focus:ring-primary-500 focus:border-primary-500
dark:bg-gray-900 dark:border-gray-600 dark:text-white
dark:placeholder:text-gray-500 dark:focus:ring-primary-400
```

### Estados

| Estado | Classes Adicionais |
|--------|-------------------|
| Default | (base) |
| Focus | ring-2 ring-primary-500 border-primary-500 |
| Error | border-danger-500 focus:ring-danger-500 |
| Disabled | bg-gray-100 dark:bg-gray-800 opacity-60 cursor-not-allowed |
| Readonly | bg-gray-50 dark:bg-gray-800/50 |

### Com Label

```html
<div>
  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
    Nome da Camera
  </label>
  <ZyInput v-model="form.name" placeholder="Ex: Entrada Principal" />
  <p v-if="error" class="mt-1 text-sm text-danger-600 dark:text-danger-400">
    {{ error }}
  </p>
</div>
```

### Com Icone
```html
<div class="relative">
  <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
  <ZyInput class="pl-10" placeholder="Buscar cameras..." />
</div>
```

### Props

```typescript
interface InputProps {
  modelValue: string | number
  type?: 'text' | 'email' | 'password' | 'number' | 'url' | 'search'
  placeholder?: string
  disabled?: boolean
  readonly?: boolean
  error?: string
  label?: string
  hint?: string
}
```

## ZySelect

### Classes Base
```
w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm
text-gray-900 bg-white
focus:ring-2 focus:ring-primary-500 focus:border-primary-500
dark:bg-gray-900 dark:border-gray-600 dark:text-white
```

### Props
```typescript
interface SelectProps {
  modelValue: string | number
  options: Array<{ value: string | number; label: string }>
  placeholder?: string
  disabled?: boolean
  error?: string
  label?: string
}
```

## ZyCheckbox

```html
<label class="flex items-center gap-2 cursor-pointer">
  <input type="checkbox"
    class="h-4 w-4 rounded border-gray-300 text-primary-600
           focus:ring-primary-500
           dark:border-gray-600 dark:bg-gray-900" />
  <span class="text-sm text-gray-700 dark:text-gray-300">
    Ativar gravacao
  </span>
</label>
```

## ZySwitch (Toggle)

```html
<button role="switch" :aria-checked="enabled"
  :class="[
    enabled ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700',
    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full',
    'transition-colors duration-200 focus:ring-2 focus:ring-primary-500'
  ]">
  <span :class="[
    enabled ? 'translate-x-5' : 'translate-x-0',
    'pointer-events-none inline-block h-5 w-5 transform rounded-full',
    'bg-white shadow ring-0 transition duration-200'
  ]" />
</button>
```

## ZyTextarea

### Classes Base
```
w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm
text-gray-900 placeholder:text-gray-400 resize-y min-h-[80px]
focus:ring-2 focus:ring-primary-500
dark:bg-gray-900 dark:border-gray-600 dark:text-white
```

## Regras para Formularios
- SEMPRE ter label visivel (acessibilidade)
- SEMPRE mostrar erro abaixo do campo
- Campos obrigatorios: indicar com asterisco ou texto
- Tab order logico
- Autocomplete quando aplicavel
- Validacao em tempo real (debounced)
