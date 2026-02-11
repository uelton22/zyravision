# Sombras e Bordas — ZyraVision Design System

## Sombras

| Token | Tailwind | Valor CSS | Uso |
|-------|----------|-----------|-----|
| shadow-none | shadow-none | none | Elementos flat |
| shadow-sm | shadow-sm | 0 1px 2px rgba(0,0,0,0.05) | Cards sutis, dropdowns |
| shadow | shadow | 0 1px 3px rgba(0,0,0,0.1) | Cards padrao |
| shadow-md | shadow-md | 0 4px 6px rgba(0,0,0,0.1) | Cards hover, modais |
| shadow-lg | shadow-lg | 0 10px 15px rgba(0,0,0,0.1) | Modais, popovers |
| shadow-xl | shadow-xl | 0 20px 25px rgba(0,0,0,0.1) | Dialogs importantes |

### Uso por Componente

| Componente | Light | Dark |
|-----------|-------|------|
| Card | shadow-sm | shadow-none (borda sutil) |
| Card hover | shadow-md | shadow-sm |
| Dropdown | shadow-lg | shadow-lg |
| Modal | shadow-xl | shadow-xl |
| Toast | shadow-lg | shadow-lg |
| Sidebar | shadow-sm | shadow-none |

## Bordas

### Radius

| Token | Tailwind | Valor | Uso |
|-------|----------|-------|-----|
| rounded-none | rounded-none | 0 | Sem arredondamento |
| rounded-sm | rounded-sm | 2px | Tags, badges pequenos |
| rounded | rounded | 4px | Inputs, selects |
| rounded-md | rounded-md | 6px | Botoes, badges |
| rounded-lg | rounded-lg | 8px | Cards, modais (PADRAO) |
| rounded-xl | rounded-xl | 12px | Cards grandes |
| rounded-full | rounded-full | 9999px | Avatares, pills |

### Padrao por Componente

| Componente | Radius |
|-----------|--------|
| Botao | rounded-lg |
| Input | rounded-lg |
| Card | rounded-lg |
| Modal | rounded-xl |
| Badge | rounded-md |
| Avatar | rounded-full |
| Toast | rounded-lg |
| Dropdown | rounded-lg |
| Sidebar | rounded-none (full height) |

### Border Width e Cor

```
Borda padrao:  border border-gray-200 dark:border-gray-700
Borda focus:   ring-2 ring-primary-500 ring-offset-2
Borda erro:    border-danger-500
Borda sucesso: border-success-500
Divider:       border-t border-gray-200 dark:border-gray-700
```

## Transicoes

```
Padrao:        transition-colors duration-150
Hover cards:   transition-shadow duration-200
Modal:         transition-opacity duration-200
Sidebar:       transition-all duration-300
```

## Ring (Focus)

```
Focus padrao:  focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
Focus erro:    focus:ring-2 focus:ring-danger-500 focus:ring-offset-2
Dark offset:   dark:ring-offset-gray-900
```

## Regras

- `rounded-lg` e o padrao para 90% dos componentes
- SEMPRE usar `transition-colors` em elementos interativos
- SEMPRE ter ring visible no focus (acessibilidade)
- Bordas em dark mode: gray-700 (NUNCA gray-600 ou gray-800)
- Sombras em dark mode: mais sutis ou substituir por bordas
