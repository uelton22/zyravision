# Padrao de Paginas CRUD — ZyraVision Design System

## Principio

TODAS as paginas de listagem, criacao, edicao e visualizacao seguem o MESMO padrao. Nenhuma pagina CRUD deve ter layout ou comportamento diferente.

## Index (Listagem)

```vue
<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ZyTable, ZyButton, ZyBadge, ZyInput } from '@/Components/UI'
import { PlusIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline'
import { router } from '@inertiajs/vue3'
import type { Camera, PaginatedResponse } from '@/Types'

defineOptions({ layout: AuthenticatedLayout })

const props = defineProps<{
  cameras: PaginatedResponse<Camera>
  filters: { search?: string; status?: string }
}>()

const columns = [
  { key: 'name', label: 'Nome', sortable: true },
  { key: 'rtsp_url', label: 'URL', class: 'font-mono' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Criado em', sortable: true },
  { key: 'actions', label: '', align: 'right' },
]
</script>

<template>
  <!-- Page header -->
  <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cameras</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        {{ cameras.meta.total }} cameras cadastradas
      </p>
    </div>
    <ZyButton :href="route('cameras.create')">
      <PlusIcon class="h-5 w-5 mr-1.5" />
      Nova Camera
    </ZyButton>
  </div>

  <!-- Tabela -->
  <ZyTable
    :columns="columns"
    :data="cameras.data"
    :pagination="cameras.meta"
    :filters="filters"
    searchable
    search-placeholder="Buscar cameras..."
  >
    <template #cell-status="{ row }">
      <ZyBadge :variant="row.status === 'online' ? 'success' : 'danger'" dot>
        {{ row.status }}
      </ZyBadge>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex items-center justify-end gap-1">
        <ZyButton variant="ghost" size="sm" :href="route('cameras.show', row.id)">
          <EyeIcon class="h-4 w-4" />
        </ZyButton>
        <ZyButton variant="ghost" size="sm" :href="route('cameras.edit', row.id)">
          <PencilIcon class="h-4 w-4" />
        </ZyButton>
        <ZyButton variant="ghost" size="sm" class="text-danger-500" @click="confirmDelete(row)">
          <TrashIcon class="h-4 w-4" />
        </ZyButton>
      </div>
    </template>

    <template #empty>
      <div class="py-12 text-center">
        <VideoCameraIcon class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Nenhuma camera</h3>
        <p class="mt-1 text-sm text-gray-500">Comece adicionando uma camera.</p>
        <ZyButton class="mt-4" size="sm" :href="route('cameras.create')">
          <PlusIcon class="h-4 w-4 mr-1" /> Adicionar
        </ZyButton>
      </div>
    </template>
  </ZyTable>
</template>
```

## Create (Criacao)

```vue
<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: AuthenticatedLayout })

const form = useForm({
  name: '',
  rtsp_url: '',
  location: '',
  description: '',
})

function submit() {
  form.post(route('cameras.store'))
}
</script>

<template>
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nova Camera</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Adicione uma nova camera ao sistema</p>
  </div>

  <ZyCard class="max-w-2xl">
    <form @submit.prevent="submit" class="space-y-6">
      <ZyInput v-model="form.name" label="Nome" :error="form.errors.name"
               placeholder="Ex: Entrada Principal" required />

      <ZyInput v-model="form.rtsp_url" label="URL RTSP" :error="form.errors.rtsp_url"
               placeholder="rtsp://192.168.1.100/stream1" class="font-mono" required />

      <ZyInput v-model="form.location" label="Localizacao" :error="form.errors.location"
               placeholder="Ex: Portaria" />

      <ZyTextarea v-model="form.description" label="Descricao" :error="form.errors.description"
                  placeholder="Descricao opcional da camera" />

      <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <ZyButton variant="secondary" :href="route('cameras.index')">Cancelar</ZyButton>
        <ZyButton type="submit" :loading="form.processing">Salvar Camera</ZyButton>
      </div>
    </form>
  </ZyCard>
</template>
```

## Edit (Edicao)

Mesmo layout do Create, mas com `form.put(route('cameras.update', camera.id))` e campos pre-preenchidos.

## Show (Detalhes)

```vue
<template>
  <div class="mb-6 flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ camera.name }}</h1>
      <ZyBadge :variant="camera.status === 'online' ? 'success' : 'danger'" dot class="mt-2">
        {{ camera.status }}
      </ZyBadge>
    </div>
    <div class="flex gap-3">
      <ZyButton variant="secondary" :href="route('cameras.edit', camera.id)">Editar</ZyButton>
      <ZyButton variant="danger" @click="confirmDelete">Excluir</ZyButton>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Player (2/3) -->
    <div class="lg:col-span-2">
      <ZyCard padding="none">
        <div class="aspect-video bg-black rounded-t-lg">
          <!-- HLS Player -->
        </div>
        <div class="p-4">
          <!-- Timeline controls -->
        </div>
      </ZyCard>
    </div>

    <!-- Info (1/3) -->
    <div class="space-y-6">
      <ZyCard title="Informacoes">
        <dl class="space-y-3">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">URL RTSP</dt>
            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-200">{{ camera.rtsp_url }}</dd>
          </div>
          <!-- mais campos -->
        </dl>
      </ZyCard>
    </div>
  </div>
</template>
```

## Regras CRUD

1. **Header**: Sempre titulo + descricao + acao principal
2. **Index**: Sempre ZyTable com paginacao e busca
3. **Create/Edit**: Formulario em ZyCard, max-w-2xl
4. **Show**: Grid 2/3 + 1/3 para recursos com midia
5. **Delete**: Sempre com ZyConfirmDialog
6. **Feedback**: Toast apos sucesso, Alert inline para erros
7. **Navigation**: Breadcrumbs em todas as paginas
8. **Mobile**: Acoes em dropdown no mobile, inline no desktop
