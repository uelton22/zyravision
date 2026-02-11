# ZyPagination — Design System ZyraVision

## Estrutura Visual

```html
<nav class="flex items-center justify-between" aria-label="Paginacao">
  <!-- Info -->
  <p class="text-sm text-gray-500 dark:text-gray-400">
    Mostrando <span class="font-medium text-gray-700 dark:text-gray-200">1</span>
    a <span class="font-medium text-gray-700 dark:text-gray-200">25</span>
    de <span class="font-medium text-gray-700 dark:text-gray-200">100</span> resultados
  </p>

  <!-- Botoes -->
  <div class="flex items-center gap-1">
    <!-- Anterior -->
    <button :disabled="currentPage === 1"
      class="px-3 py-2 text-sm font-medium rounded-lg
             text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800
             disabled:opacity-50 disabled:cursor-not-allowed">
      <ChevronLeftIcon class="h-5 w-5" />
    </button>

    <!-- Numeros -->
    <button class="px-3.5 py-2 text-sm font-medium rounded-lg
                   bg-primary-600 text-white dark:bg-primary-500">
      1
    </button>
    <button class="px-3.5 py-2 text-sm font-medium rounded-lg
                   text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
      2
    </button>
    <button class="px-3.5 py-2 text-sm font-medium rounded-lg
                   text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
      3
    </button>
    <span class="px-2 text-gray-400">...</span>
    <button class="px-3.5 py-2 text-sm font-medium rounded-lg
                   text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
      10
    </button>

    <!-- Proximo -->
    <button class="px-3 py-2 text-sm font-medium rounded-lg
                   text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">
      <ChevronRightIcon class="h-5 w-5" />
    </button>
  </div>
</nav>
```

## Props

```typescript
interface PaginationProps {
  currentPage: number
  totalPages: number
  totalItems: number
  perPage: number
  perPageOptions?: number[]  // [10, 25, 50, 100]
  showInfo?: boolean
  showPerPage?: boolean
}
```

## Logica de Exibicao

| Total paginas | Exibicao |
|--------------|----------|
| <= 7 | Todos os numeros |
| > 7 | 1 ... 4 5 6 ... 10 |

Sempre mostrar: primeira, ultima, atual, e 1 vizinho de cada lado.

## Items por Pagina

```html
<div class="flex items-center gap-2">
  <label class="text-sm text-gray-500 dark:text-gray-400">Exibir</label>
  <ZySelect v-model="perPage" :options="[
    { value: 10, label: '10' },
    { value: 25, label: '25' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
  ]" size="sm" />
  <span class="text-sm text-gray-500 dark:text-gray-400">por pagina</span>
</div>
```

## Padrao
- Per page default: 25
- SEMPRE server-side pagination
- Preservar pagina na URL (?page=2&per_page=25)
- Resetar para pagina 1 ao mudar filtros
