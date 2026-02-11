# ZyTable — Design System ZyraVision

## Principio

TODA listagem de dados usa `ZyTable`. Nenhuma tabela customizada e permitida.

## Estrutura Visual

```html
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
  <!-- Header com titulo e acoes -->
  <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
    <div>
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cameras</h3>
      <p class="text-sm text-gray-500 dark:text-gray-400">24 cameras cadastradas</p>
    </div>
    <div class="flex items-center gap-3">
      <!-- Filtros e acoes -->
      <ZyInput placeholder="Buscar..." icon="search" size="sm" />
      <ZyButton size="sm"><PlusIcon class="h-4 w-4 mr-1" /> Adicionar</ZyButton>
    </div>
  </div>

  <!-- Tabela -->
  <table class="w-full">
    <thead>
      <tr class="bg-gray-50 dark:bg-gray-800/50">
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
          Nome
        </th>
        <!-- ... mais colunas -->
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
      <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
          Entrada Principal
        </td>
        <!-- ... mais celulas -->
        <td class="px-6 py-4 text-right">
          <!-- Acoes -->
          <div class="flex items-center justify-end gap-2">
            <ZyButton variant="ghost" size="sm" aria-label="Ver">
              <EyeIcon class="h-4 w-4" />
            </ZyButton>
            <ZyButton variant="ghost" size="sm" aria-label="Editar">
              <PencilIcon class="h-4 w-4" />
            </ZyButton>
            <ZyButton variant="ghost" size="sm" aria-label="Excluir" class="text-danger-500">
              <TrashIcon class="h-4 w-4" />
            </ZyButton>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <!-- Paginacao -->
  <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
    <p class="text-sm text-gray-500 dark:text-gray-400">
      Mostrando 1 a 25 de 100 resultados
    </p>
    <ZyPagination :current="1" :total="4" />
  </div>
</div>
```

## Props ZyTable

```typescript
interface TableProps {
  columns: Array<{
    key: string
    label: string
    sortable?: boolean
    align?: 'left' | 'center' | 'right'
    width?: string
  }>
  data: Array<Record<string, any>>
  loading?: boolean
  selectable?: boolean
  striped?: boolean
  hoverable?: boolean
  emptyMessage?: string
  emptyIcon?: Component
}
```

## Funcionalidades Obrigatorias

### Ordenacao
- Clique no header para ordenar
- Icone de seta indicando direcao
- Suporte a ordenacao server-side

### Paginacao
- SEMPRE presente quando > 25 itens
- Server-side pagination
- Opcao de mudar items por pagina

### Filtros
- Campo de busca global
- Filtros por coluna (quando necessario)
- Filtros salvos em URL (query params)

### Selecao
- Checkbox na primeira coluna
- Selecionar todos
- Acoes em lote

### Empty State
```html
<div class="py-12 text-center">
  <VideoCameraIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
  <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
    Nenhuma camera encontrada
  </h3>
  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
    Comece adicionando uma camera ao sistema.
  </p>
  <ZyButton class="mt-4" size="sm">
    <PlusIcon class="h-4 w-4 mr-1" /> Adicionar Camera
  </ZyButton>
</div>
```

### Loading State (Skeleton)
```html
<tr v-for="i in 5" :key="i">
  <td class="px-6 py-4">
    <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4"></div>
  </td>
</tr>
```

## Acoes Padrao

| Acao | Icone | Variante | Permissao |
|------|-------|----------|-----------|
| Visualizar | EyeIcon | ghost | view |
| Editar | PencilIcon | ghost | edit |
| Excluir | TrashIcon | ghost/danger | delete |
| Ativar/Desativar | toggle | ghost | manage |

## Regras
- Coluna de acoes SEMPRE a direita
- Status SEMPRE com ZyBadge colorido
- Datas formatadas: "21 jan 2026, 14:30"
- IPs/URLs em font-mono
- Max 8 colunas visiveis em desktop
- Responsivo: esconder colunas secundarias em mobile
