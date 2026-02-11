# Padrao de Dashboard — ZyraVision Design System

## Dashboard Principal

```
+----------------------------------------------+
| [Stats Cards - Grid 4 colunas]               |
| Cameras | Online | Storage | Alertas         |
+----------------------------------------------+
|                        |                      |
| [Camera Grid]          | [Alertas Recentes]   |
| Mosaico 2x2           | Lista dos ultimos     |
| cameras prioritarias   | alertas               |
|                        |                      |
+----------------------------------------------+
| [Graficos]                                    |
| Uso de banda | Storage timeline               |
+----------------------------------------------+
```

## Stats Cards

```vue
<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    <ZyStatCard
      title="Total Cameras"
      :value="stats.totalCameras"
      icon="VideoCameraIcon"
      color="primary"
    />
    <ZyStatCard
      title="Online"
      :value="stats.onlineCameras"
      :change="+2"
      icon="SignalIcon"
      color="success"
    />
    <ZyStatCard
      title="Storage Usado"
      :value="stats.storageUsed"
      suffix="TB"
      :progress="stats.storagePercent"
      icon="ServerStackIcon"
      color="info"
    />
    <ZyStatCard
      title="Alertas Hoje"
      :value="stats.alertsToday"
      icon="BellAlertIcon"
      color="warning"
    />
  </div>
</template>
```

## Camera Grid (Mosaico no Dashboard)

```vue
<template>
  <ZyCard title="Cameras Prioritarias" class="mb-6">
    <template #header-actions>
      <ZyButton variant="ghost" size="sm" :href="route('live')">
        Ver Todas
      </ZyButton>
    </template>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div v-for="camera in priorityCameras" :key="camera.id"
           class="aspect-video bg-black rounded-lg overflow-hidden relative group cursor-pointer"
           @click="openCamera(camera)">
        <!-- Thumbnail ou stream -->
        <img :src="camera.thumbnailUrl" class="w-full h-full object-cover" />

        <!-- Overlay info -->
        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/80 to-transparent p-3">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-white truncate">{{ camera.name }}</span>
            <ZyBadge variant="success" size="xs" pulse>LIVE</ZyBadge>
          </div>
        </div>
      </div>
    </div>
  </ZyCard>
</template>
```

## Alertas Recentes

```vue
<template>
  <ZyCard title="Alertas Recentes">
    <div class="space-y-3">
      <div v-for="alert in recentAlerts" :key="alert.id"
           class="flex items-start gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50">
        <div :class="[
          'mt-0.5 h-2 w-2 rounded-full shrink-0',
          alert.severity === 'critical' ? 'bg-danger-500' :
          alert.severity === 'warning' ? 'bg-warning-500' : 'bg-info-500'
        ]" />
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white">
            {{ alert.title }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ alert.camera }} · {{ alert.timeAgo }}
          </p>
        </div>
      </div>
    </div>
  </ZyCard>
</template>
```

## Graficos

Usar biblioteca: **Chart.js** com `vue-chartjs`

```vue
<ZyCard title="Uso de Banda (ultimas 24h)">
  <LineChart :data="bandwidthData" :options="chartOptions" class="h-64" />
</ZyCard>
```

### Cores dos Graficos

```typescript
const chartColors = {
  primary: 'rgb(79, 70, 229)',    // primary-600
  success: 'rgb(16, 185, 129)',   // success-500
  danger: 'rgb(239, 68, 68)',     // danger-500
  warning: 'rgb(245, 158, 11)',   // warning-500
  info: 'rgb(59, 130, 246)',      // info-500
  grid: 'rgb(229, 231, 235)',     // gray-200
  gridDark: 'rgb(55, 65, 81)',    // gray-700
}
```

## Widgets Disponiveis

| Widget | Uso |
|--------|-----|
| ZyStatCard | Metricas numericas com comparacao |
| CameraGrid | Mosaico de cameras ao vivo |
| AlertList | Lista de alertas recentes |
| BandwidthChart | Grafico de uso de banda |
| StorageChart | Grafico de uso de disco |
| CameraStatusPie | Pizza de status das cameras |
| EventTimeline | Timeline de eventos IA |

## Responsividade Dashboard

| Breakpoint | Stats | Camera Grid | Graficos |
|-----------|-------|-------------|----------|
| Mobile | 1 col | 1 col | 1 col, full |
| sm | 2 col | 2 col | 1 col |
| lg | 4 col | 2 col | 2 col |
| xl | 4 col | 2 col | 2 col |

## Auto-Refresh

- Stats: a cada 30s
- Camera thumbnails: a cada 10s
- Alertas: via WebSocket (real-time)
- Graficos: a cada 60s
