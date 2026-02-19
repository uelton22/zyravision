<script setup lang="ts">
/**
 * AuthenticatedLayout — Layout principal da aplicação autenticada
 */

import { ref, computed, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { ZySidebar, ZyHeader, ZyToast } from '@design';
import {
    HomeIcon,
    VideoCameraIcon,
    UsersIcon,
    BuildingOfficeIcon,
    CreditCardIcon,
    Cog6ToothIcon,
    ChartBarIcon,
    ClockIcon,
    UserIcon,
    ClipboardDocumentListIcon,
    ServerStackIcon,
    CircleStackIcon,
    KeyIcon,
    BookOpenIcon,
    DevicePhoneMobileIcon,
    WrenchScrewdriverIcon,
    CpuChipIcon,
} from '@heroicons/vue/24/outline';
import type { PageProps } from '@/Types';

const page = usePage<PageProps>();
const sidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

// Close mobile sidebar on navigation
router.on('navigate', () => {
    mobileSidebarOpen.value = false;
});

const userRoles = computed(() => {
    const roles = (page.props.auth?.user as any)?.roles;
    if (!roles) return [];
    return roles.map((r: any) => (typeof r === 'string' ? r : r.name));
});

const hasRole = (role: string) => userRoles.value.includes(role);
const isSuperAdmin = computed(() => hasRole('super-admin'));
const isAdminOrSuper = computed(() => hasRole('super-admin') || hasRole('admin'));
const isOperatorOrAbove = computed(() => hasRole('super-admin') || hasRole('admin') || hasRole('operator'));

const navigation = computed(() => {
    const items: any[] = [
        { label: 'Dashboard', href: '/dashboard', icon: HomeIcon },
        { label: 'Cameras', href: '/cameras', icon: VideoCameraIcon },
    ];

    // Dispositivos: admin + super-admin
    if (isAdminOrSuper.value) {
        items.push(
            { label: 'Dispositivos', href: '/devices', icon: ServerStackIcon },
        );
    }

    // Gravacoes: todos (recordings.view)
    items.push(
        { label: 'Gravacoes', href: '/recordings', icon: ClockIcon },
    );

    // Armazenamento: admin + super-admin
    if (isAdminOrSuper.value) {
        items.push(
            { label: 'Armazenamento', href: '/armazenamento', icon: CircleStackIcon },
        );
    }

    // Analises: operator + admin + super-admin (analytics.view)
    if (isOperatorOrAbove.value) {
        items.push(
            { label: 'Analises', href: '/analytics', icon: ChartBarIcon },
        );
    }

    // Usuarios: admin + super-admin
    if (isAdminOrSuper.value) {
        items.push(
            { label: 'Usuarios', href: '/users', icon: UsersIcon },
        );
    }

    // Tenants e Planos: super-admin only
    if (isSuperAdmin.value) {
        items.push(
            { label: 'Tenants', href: '/tenants', icon: BuildingOfficeIcon },
            { label: 'Planos', href: '/plans', icon: CreditCardIcon },
        );
    }

    // Auditoria: admin + super-admin
    if (isAdminOrSuper.value) {
        items.push(
            { label: 'Auditoria', href: '/audit', icon: ClipboardDocumentListIcon },
        );
    }

    // Meu Perfil: todos
    items.push(
        { label: 'Meu Perfil', href: '/profile', icon: UserIcon },
    );

    // Configuracoes: super-admin only (configuracoes do sistema)
    if (isSuperAdmin.value) {
        items.push({
            label: 'Configuracoes', href: '/settings/recording', icon: Cog6ToothIcon,
            children: [
                { label: 'Gravacao', href: '/settings/recording', icon: VideoCameraIcon },
                { label: 'Tokens de API', href: '/settings/api-tokens', icon: KeyIcon },
                { label: 'Dispositivos', href: '/settings/devices', icon: DevicePhoneMobileIcon },
                { label: 'Documentacao API', href: '/settings/api/docs', icon: BookOpenIcon },
                { label: 'Workers', href: '/settings/workers', icon: CpuChipIcon },
                { label: 'Sistema', href: '/settings/system', icon: WrenchScrewdriverIcon },
                { label: 'Armazenamento', href: '/settings/storage', icon: CircleStackIcon },
            ],
        });
    }

    return items;
});

function logout() {
    router.post('/logout');
}

const headerMenuItems = computed(() => {
    const items: any[] = [
        { label: 'Meu Perfil', href: '/profile' },
    ];

    if (isSuperAdmin.value) {
        items.push({ label: 'Configuracoes', href: '/settings/recording' });
    }

    items.push(
        { divider: true, label: '' },
        { label: 'Sair', action: logout, danger: true },
    );

    return items;
});
</script>

<template>
    <div class="flex h-screen bg-[var(--zy-surface-secondary)]">
        <!-- Sidebar Desktop -->
        <div class="hidden lg:flex">
            <ZySidebar
                :items="navigation"
                :collapsed="sidebarCollapsed"
                @update:collapsed="sidebarCollapsed = $event"
            />
        </div>

        <!-- Main Content -->
        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <ZyHeader
                :user="page.props.auth?.user ? {
                    name: page.props.auth.user.name,
                    email: page.props.auth.user.email,
                    avatar_url: page.props.auth.user.avatar_url,
                } : undefined"
                :menu-items="headerMenuItems"
                @toggle-sidebar="mobileSidebarOpen = !mobileSidebarOpen"
            />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto zy-scrollbar">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile Sidebar Overlay -->
        <Teleport to="body">
            <Transition name="zy-overlay">
                <div
                    v-if="mobileSidebarOpen"
                    class="fixed inset-0 z-40 bg-black/50 lg:hidden"
                    @click="mobileSidebarOpen = false"
                />
            </Transition>
            <Transition name="zy-slide">
                <div
                    v-if="mobileSidebarOpen"
                    class="fixed inset-y-0 left-0 z-50 w-64 lg:hidden"
                >
                    <ZySidebar
                        :items="navigation"
                        :collapsed="false"
                    />
                </div>
            </Transition>
        </Teleport>

        <!-- Toast notifications -->
        <ZyToast />
    </div>
</template>

<style scoped>
.zy-overlay-enter-active,
.zy-overlay-leave-active {
    transition: opacity 0.3s ease;
}
.zy-overlay-enter-from,
.zy-overlay-leave-to {
    opacity: 0;
}

.zy-slide-enter-active,
.zy-slide-leave-active {
    transition: transform 0.3s ease;
}
.zy-slide-enter-from,
.zy-slide-leave-to {
    transform: translateX(-100%);
}
</style>
