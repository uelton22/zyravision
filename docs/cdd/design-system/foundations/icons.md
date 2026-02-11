# Icones — ZyraVision Design System

## Biblioteca

**Heroicons** (v2) — Biblioteca padrao do projeto.
Consistente com Tailwind CSS e mantida pela equipe do Tailwind.

- Estilo: Outline (24px) para navegacao e acoes
- Estilo: Solid (20px) para badges e status
- Estilo: Mini (20px) para dentro de botoes

## Instalacao

```bash
npm install @heroicons/vue
```

## Uso no Vue

```vue
<script setup lang="ts">
import { CameraIcon, PlayIcon } from '@heroicons/vue/24/outline'
import { VideoCameraIcon } from '@heroicons/vue/20/solid'
</script>

<template>
  <CameraIcon class="h-5 w-5 text-gray-500" />
</template>
```

## Tamanhos Padrao

| Contexto | Tamanho | Classes |
|----------|---------|---------|
| Dentro de botao | 20px | h-5 w-5 |
| Navegacao sidebar | 24px | h-6 w-6 |
| Status badge | 16px | h-4 w-4 |
| Card header | 24px | h-6 w-6 |
| Empty state | 48px | h-12 w-12 |
| Hero/destaque | 64px | h-16 w-16 |

## Mapeamento de Icones CFTV

| Conceito | Icone Heroicons |
|----------|----------------|
| Camera | VideoCameraIcon |
| Camera ao vivo | PlayCircleIcon |
| Gravacao | CircleStackIcon |
| Stream | SignalIcon |
| Usuarios | UsersIcon |
| Configuracoes | Cog6ToothIcon |
| Dashboard | ChartBarIcon |
| Alertas | BellAlertIcon |
| Storage | ServerStackIcon |
| Logs | DocumentTextIcon |
| PTZ | ArrowsPointingOutIcon |
| Online | CheckCircleIcon (success) |
| Offline | XCircleIcon (danger) |
| Alerta | ExclamationTriangleIcon (warning) |
| Busca | MagnifyingGlassIcon |
| Filtro | FunnelIcon |
| Exportar | ArrowDownTrayIcon |
| Excluir | TrashIcon |
| Editar | PencilSquareIcon |
| Visualizar | EyeIcon |
| Adicionar | PlusIcon |
| Fechar | XMarkIcon |
| Menu | Bars3Icon |
| Tenant | BuildingOfficeIcon |
| Mapa | MapPinIcon |
| IA/AI | SparklesIcon |
| GPU | CpuChipIcon |
| Notificacao | BellIcon |
| Mobile | DevicePhoneMobileIcon |

## Cores de Icones

| Contexto | Classes |
|----------|---------|
| Padrao | text-gray-400 dark:text-gray-500 |
| Ativo | text-primary-600 dark:text-primary-400 |
| Sucesso | text-success-500 |
| Perigo | text-danger-500 |
| Alerta | text-warning-500 |
| Info | text-info-500 |
| Live | text-danger-500 animate-pulse |

## Regras

- SOMENTE Heroicons — nao misturar bibliotecas
- SEMPRE usar outline (24px) para acoes
- SEMPRE usar solid (20px) para status
- NUNCA usar icones sem significado semantico
- SEMPRE ter `aria-hidden="true"` em icones decorativos
