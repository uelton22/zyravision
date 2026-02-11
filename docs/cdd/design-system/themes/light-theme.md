# Tema Claro (Light) — ZyraVision Design System

## Visao Geral

O tema claro e o padrao da aplicacao. Utilizado em ambientes com boa iluminacao.

## Mapeamento de Cores — Light Mode

### Backgrounds

| Elemento | Classe Tailwind | Hex |
|----------|----------------|-----|
| Pagina (body) | bg-gray-50 | #F9FAFB |
| Sidebar | bg-white | #FFFFFF |
| Header | bg-white | #FFFFFF |
| Card | bg-white | #FFFFFF |
| Card hover | bg-gray-50 | #F9FAFB |
| Modal overlay | bg-black/50 | rgba(0,0,0,0.5) |
| Modal content | bg-white | #FFFFFF |
| Dropdown | bg-white | #FFFFFF |
| Input | bg-white | #FFFFFF |
| Input disabled | bg-gray-100 | #F3F4F6 |
| Table header | bg-gray-50 | #F9FAFB |
| Table row hover | bg-gray-50 | #F9FAFB |
| Table row striped | bg-gray-50/50 | rgba(249,250,251,0.5) |
| Toast | bg-white | #FFFFFF |
| Badge bg | bg-{color}-50 | variavel |
| Skeleton | bg-gray-200 | #E5E7EB |

### Textos

| Elemento | Classe | Hex |
|----------|--------|-----|
| Titulo principal | text-gray-900 | #111827 |
| Texto body | text-gray-700 | #374151 |
| Texto secundario | text-gray-500 | #6B7280 |
| Texto placeholder | text-gray-400 | #9CA3AF |
| Texto disabled | text-gray-300 | #D1D5DB |
| Link | text-primary-600 | #4F46E5 |
| Link hover | text-primary-700 | #4338CA |
| Texto erro | text-danger-600 | #DC2626 |
| Texto sucesso | text-success-600 | #059669 |

### Bordas

| Elemento | Classe | Hex |
|----------|--------|-----|
| Borda padrao | border-gray-200 | #E5E7EB |
| Borda sutil | border-gray-100 | #F3F4F6 |
| Borda forte | border-gray-300 | #D1D5DB |
| Borda focus | ring-primary-500 | #6366F1 |
| Divider | border-gray-200 | #E5E7EB |

### Sidebar

```html
<aside class="bg-white border-r border-gray-200 w-64">
  <!-- Logo -->
  <div class="px-4 py-5 border-b border-gray-200">
    <span class="text-xl font-bold text-primary-600">ZyraVision</span>
  </div>

  <!-- Nav items -->
  <nav class="px-3 py-4 space-y-1">
    <!-- Item ativo -->
    <a class="flex items-center px-3 py-2 text-sm font-medium rounded-lg
              bg-primary-50 text-primary-700">
      <Icon class="h-5 w-5 mr-3 text-primary-500" />
      Dashboard
    </a>

    <!-- Item inativo -->
    <a class="flex items-center px-3 py-2 text-sm font-medium rounded-lg
              text-gray-600 hover:bg-gray-50 hover:text-gray-900">
      <Icon class="h-5 w-5 mr-3 text-gray-400" />
      Cameras
    </a>
  </nav>
</aside>
```

### Header

```html
<header class="bg-white border-b border-gray-200 h-16 px-6
               flex items-center justify-between">
  <h1 class="text-lg font-semibold text-gray-900">Dashboard</h1>
  <div class="flex items-center gap-4">
    <button class="text-gray-400 hover:text-gray-500">
      <BellIcon class="h-6 w-6" />
    </button>
  </div>
</header>
```

### Cards

```html
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6
            hover:shadow-md transition-shadow">
  <h3 class="text-lg font-semibold text-gray-900">Titulo</h3>
  <p class="mt-1 text-sm text-gray-500">Descricao</p>
</div>
```

## Status de Camera (Light)

| Status | Badge Classes |
|--------|--------------|
| Online | bg-success-50 text-success-700 ring-1 ring-success-600/20 |
| Offline | bg-danger-50 text-danger-700 ring-1 ring-danger-600/20 |
| Alerta | bg-warning-50 text-warning-700 ring-1 ring-warning-600/20 |
| Gravando | bg-danger-50 text-danger-700 + pulse animation |
