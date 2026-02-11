<script setup lang="ts">
/**
 * ZySidebar — Navegação lateral do Design System
 */

import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDoubleLeftIcon, ChevronDoubleRightIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

interface NavItem {
    label: string;
    href: string;
    icon?: object;
    badge?: string | number;
    children?: NavItem[];
    permission?: string;
}

interface Props {
    items: NavItem[];
    collapsed?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    collapsed: false,
});

const emit = defineEmits<{
    'update:collapsed': [value: boolean];
}>();

const isCollapsed = ref(props.collapsed);
const page = usePage();

const currentUrl = computed(() => page.url);

// Track which parent menus are expanded
const expandedMenus = ref<Set<string>>(new Set());

// Auto-expand parent if a child is active
function initExpandedMenus() {
    for (const item of props.items) {
        if (item.children?.length) {
            const childActive = item.children.some(c => isActive(c.href));
            if (childActive) {
                expandedMenus.value.add(item.label);
            }
        }
    }
}
initExpandedMenus();

function isActive(href: string): boolean {
    return currentUrl.value.startsWith(href);
}

function isParentActive(item: NavItem): boolean {
    if (!item.children?.length) return isActive(item.href);
    return item.children.some(c => isActive(c.href));
}

function toggleSubmenu(label: string) {
    if (expandedMenus.value.has(label)) {
        expandedMenus.value.delete(label);
    } else {
        expandedMenus.value.add(label);
    }
}

function isExpanded(label: string): boolean {
    return expandedMenus.value.has(label);
}

function toggleCollapse() {
    isCollapsed.value = !isCollapsed.value;
    emit('update:collapsed', isCollapsed.value);
}
</script>

<template>
    <aside
        :class="[
            'flex flex-col h-full bg-white dark:bg-dark-800 border-r border-dark-200 dark:border-dark-700 transition-all duration-300',
            isCollapsed ? 'w-16' : 'w-64',
        ]"
    >
        <!-- Logo -->
        <div class="flex items-center h-16 px-4 border-b border-dark-200 dark:border-dark-700">
            <Link href="/dashboard" class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-zy bg-primary-600 flex items-center justify-center shrink-0">
                    <span class="text-white font-bold text-sm">ZV</span>
                </div>
                <span
                    v-if="!isCollapsed"
                    class="text-heading-sm font-bold text-[var(--zy-text-primary)] whitespace-nowrap"
                >
                    ZyraVision
                </span>
            </Link>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto zy-scrollbar py-4 px-2 space-y-1">
            <template v-for="item in items" :key="item.href">
                <!-- Item with children (submenu) -->
                <div v-if="item.children && item.children.length > 0">
                    <!-- Parent button (toggles submenu) -->
                    <button
                        type="button"
                        :class="[
                            'flex items-center gap-3 px-3 py-2 rounded-zy text-body-sm font-medium transition-colors w-full text-left',
                            isParentActive(item)
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300'
                                : 'text-dark-600 hover:bg-dark-50 hover:text-dark-900 dark:text-dark-400 dark:hover:bg-dark-700 dark:hover:text-white',
                        ]"
                        :title="isCollapsed ? item.label : undefined"
                        @click="isCollapsed ? undefined : toggleSubmenu(item.label)"
                    >
                        <component v-if="item.icon" :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                        <span v-if="!isCollapsed" class="flex-1 truncate">{{ item.label }}</span>
                        <ChevronDownIcon
                            v-if="!isCollapsed"
                            :class="[
                                'h-4 w-4 shrink-0 transition-transform duration-200',
                                isExpanded(item.label) ? 'rotate-180' : '',
                            ]"
                        />
                    </button>

                    <!-- Children links -->
                    <div
                        v-if="!isCollapsed && isExpanded(item.label)"
                        class="mt-1 ml-4 pl-3 border-l border-dark-200 dark:border-dark-700 space-y-1"
                    >
                        <Link
                            v-for="child in item.children"
                            :key="child.href"
                            :href="child.href"
                            :class="[
                                'flex items-center gap-3 px-3 py-1.5 rounded-zy text-body-sm font-medium transition-colors',
                                isActive(child.href)
                                    ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300'
                                    : 'text-dark-600 hover:bg-dark-50 hover:text-dark-900 dark:text-dark-400 dark:hover:bg-dark-700 dark:hover:text-white',
                            ]"
                        >
                            <component v-if="child.icon" :is="child.icon" class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span class="flex-1 truncate">{{ child.label }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Regular item (no children) -->
                <Link
                    v-else
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-2 rounded-zy text-body-sm font-medium transition-colors',
                        isActive(item.href)
                            ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300'
                            : 'text-dark-600 hover:bg-dark-50 hover:text-dark-900 dark:text-dark-400 dark:hover:bg-dark-700 dark:hover:text-white',
                    ]"
                    :title="isCollapsed ? item.label : undefined"
                >
                    <component v-if="item.icon" :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
                    <span v-if="!isCollapsed" class="flex-1 truncate">{{ item.label }}</span>
                    <span
                        v-if="item.badge && !isCollapsed"
                        class="ml-auto inline-flex items-center rounded-full bg-primary-100 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/30 dark:text-primary-300"
                    >
                        {{ item.badge }}
                    </span>
                </Link>
            </template>
        </nav>

        <!-- Collapse toggle -->
        <div class="border-t border-dark-200 dark:border-dark-700 p-2">
            <button
                type="button"
                class="flex items-center justify-center w-full px-3 py-2 rounded-zy text-dark-500 hover:bg-dark-50 dark:hover:bg-dark-700 transition-colors"
                :aria-label="isCollapsed ? 'Expandir menu' : 'Recolher menu'"
                @click="toggleCollapse"
            >
                <ChevronDoubleRightIcon v-if="isCollapsed" class="h-5 w-5" />
                <ChevronDoubleLeftIcon v-else class="h-5 w-5" />
            </button>
        </div>
    </aside>
</template>
