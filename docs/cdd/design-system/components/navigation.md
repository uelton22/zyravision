# Navegacao — ZySidebar, ZyHeader, ZyBreadcrumb

## ZySidebar

### Estrutura

```html
<aside class="fixed inset-y-0 left-0 z-40 flex flex-col
              bg-white dark:bg-gray-950
              border-r border-gray-200 dark:border-gray-800
              transition-all duration-300"
       :class="expanded ? 'w-64' : 'w-16'">

  <!-- Logo -->
  <div class="flex items-center h-16 px-4 border-b border-gray-200 dark:border-gray-800">
    <img src="/logo-icon.svg" class="h-8 w-8" />
    <span v-if="expanded" class="ml-3 text-lg font-bold text-primary-600 dark:text-primary-400">
      ZyraVision
    </span>
  </div>

  <!-- Navegacao Principal -->
  <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
    <SidebarItem icon="ChartBarIcon" label="Dashboard" :to="route('dashboard')" />
    <SidebarItem icon="VideoCameraIcon" label="Cameras" :to="route('cameras.index')" />
    <SidebarItem icon="PlayCircleIcon" label="Live View" :to="route('live')" />
    <SidebarItem icon="CircleStackIcon" label="Gravacoes" :to="route('recordings.index')" />
    <SidebarItem icon="BellAlertIcon" label="Alertas" :to="route('alerts.index')" badge="3" />
    <SidebarItem icon="SparklesIcon" label="IA Analytics" :to="route('ai.index')" />

    <!-- Divider -->
    <div class="my-4 border-t border-gray-200 dark:border-gray-700"></div>

    <SidebarItem icon="UsersIcon" label="Usuarios" :to="route('users.index')" />
    <SidebarItem icon="ServerStackIcon" label="Storage" :to="route('storage.index')" />
    <SidebarItem icon="Cog6ToothIcon" label="Configuracoes" :to="route('settings')" />
  </nav>

  <!-- Footer (usuario) -->
  <div class="border-t border-gray-200 dark:border-gray-800 p-4">
    <div class="flex items-center">
      <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-500/20 flex items-center justify-center">
        <span class="text-sm font-medium text-primary-600 dark:text-primary-400">JD</span>
      </div>
      <div v-if="expanded" class="ml-3">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-200">John Doe</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">Admin</p>
      </div>
    </div>
  </div>
</aside>
```

### SidebarItem

```html
<!-- Ativo -->
<Link :href="to" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg
      bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400">
  <component :is="icon" class="h-5 w-5 shrink-0" />
  <span v-if="expanded" class="ml-3">{{ label }}</span>
  <span v-if="badge && expanded"
    class="ml-auto bg-danger-100 text-danger-600 dark:bg-danger-500/20 dark:text-danger-400
           text-xs font-medium px-2 py-0.5 rounded-full">
    {{ badge }}
  </span>
</Link>

<!-- Inativo -->
<Link :href="to" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg
      text-gray-600 hover:bg-gray-50 hover:text-gray-900
      dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white">
  <component :is="icon" class="h-5 w-5 shrink-0" />
  <span v-if="expanded" class="ml-3">{{ label }}</span>
</Link>
```

## ZyHeader

```html
<header class="sticky top-0 z-30 h-16 bg-white dark:bg-gray-900
               border-b border-gray-200 dark:border-gray-800
               flex items-center justify-between px-6">
  <!-- Left: Toggle + Breadcrumb -->
  <div class="flex items-center gap-4">
    <button @click="toggleSidebar" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 lg:hidden">
      <Bars3Icon class="h-6 w-6" />
    </button>
    <ZyBreadcrumb :items="breadcrumbs" />
  </div>

  <!-- Right: Acoes -->
  <div class="flex items-center gap-3">
    <!-- Theme toggle -->
    <button @click="toggleTheme" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
      <SunIcon v-if="isDark" class="h-5 w-5" />
      <MoonIcon v-else class="h-5 w-5" />
    </button>

    <!-- Notificacoes -->
    <button class="relative text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
      <BellIcon class="h-6 w-6" />
      <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-danger-500 text-white text-[10px] flex items-center justify-center">3</span>
    </button>

    <!-- Avatar -->
    <ZyDropdown>
      <template #trigger>
        <button class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-500/20">
          <span class="text-sm font-medium text-primary-600 dark:text-primary-400">JD</span>
        </button>
      </template>
      <DropdownItem :href="route('profile')">Meu Perfil</DropdownItem>
      <DropdownItem @click="logout">Sair</DropdownItem>
    </ZyDropdown>
  </div>
</header>
```

## ZyBreadcrumb

```html
<nav class="flex items-center gap-1.5 text-sm">
  <Link href="/" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
    <HomeIcon class="h-4 w-4" />
  </Link>
  <ChevronRightIcon class="h-4 w-4 text-gray-300 dark:text-gray-600" />
  <Link href="/cameras" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
    Cameras
  </Link>
  <ChevronRightIcon class="h-4 w-4 text-gray-300 dark:text-gray-600" />
  <span class="text-gray-700 dark:text-gray-200 font-medium">Entrada Principal</span>
</nav>
```

## Responsivo

### Mobile
- Sidebar: overlay com backdrop (w-64)
- Header: hamburger menu toggle
- Breadcrumb: esconder items intermediarios

### Desktop
- Sidebar: fixa, colapsavel
- Header: sem hamburger
- Breadcrumb: completo
