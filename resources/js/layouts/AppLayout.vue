<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FlashMessages from '../components/ui/FlashMessages.vue';

const page = usePage();

const appName = computed(() => page.props.appName ?? 'MyCar');
const currentPath = computed(() => page.url.split('?')[0]);

const icons = {
    dashboard: 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
    vehicles: 'M5 16V11l1.6-4.2A2 2 0 0 1 8.5 5.5h7a2 2 0 0 1 1.9 1.3L19 11v5M3.5 16h17M7 16v2M17 16v2M7.5 12.5h.01M16.5 12.5h.01',
    expenses: 'M7 3h10a1 1 0 0 1 1 1v16l-2.5-1.5L13 20l-2.5-1.5L8 20l-2.5-1.5L5 20V4a1 1 0 0 1 1-1zM9 8h6M9 12h6',
    fuelings: 'M5 20V5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v15M4 20h11M5 10h9M14 8h2.5a1.5 1.5 0 0 1 1.5 1.5V16a1.5 1.5 0 0 0 3 0v-5l-2.5-2.5',
    reports: 'M4 20h16M7 20v-6M12 20V8M17 20v-9',
    categories: 'M3.5 12.5V5a1.5 1.5 0 0 1 1.5-1.5h7.5L21 12l-8.5 8.5zM8 8h.01',
};

const navigation = [
    { label: 'Dashboard', href: '/', icon: icons.dashboard },
    { label: 'Veículos', href: '/vehicles', icon: icons.vehicles },
    { label: 'Despesas', href: '/expenses', icon: icons.expenses },
    { label: 'Abastecimentos', href: '/fuelings', icon: icons.fuelings },
    { label: 'Relatórios', href: '/reports', icon: icons.reports },
    { label: 'Categorias', href: '/categories', icon: icons.categories },
];

const isActive = (href) =>
    href === '/' ? currentPath.value === '/' : currentPath.value.startsWith(href);

const mobileOpen = ref(false);

watch(currentPath, () => {
    mobileOpen.value = false;
});
</script>

<template>
    <div
        class="min-h-screen overflow-x-hidden bg-slate-950 text-slate-100"
        @keydown.escape="mobileOpen = false"
    >
        <a
            href="#main-content"
            class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-lg focus:bg-indigo-500 focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-white"
        >
            Pular para o conteúdo
        </a>

        <div class="lg:flex lg:min-h-screen">
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-40 bg-black/50 lg:hidden"
                aria-hidden="true"
                @click="mobileOpen = false"
            ></div>

            <aside
                id="app-navigation"
                class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col bg-sidebar text-slate-300 transition-transform duration-200 ease-out lg:sticky lg:top-0 lg:h-screen lg:w-60 lg:max-w-none lg:translate-x-0"
                :class="mobileOpen ? 'translate-x-0' : ''"
            >
                <div class="flex items-center justify-between px-5 py-6">
                    <Link
                        href="/"
                        class="flex items-center gap-3 rounded-lg text-lg font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    >
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-500" aria-hidden="true">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path :d="icons.vehicles" />
                            </svg>
                        </span>
                        {{ appName }}
                    </Link>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-600 hover:bg-sidebar-hover hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 lg:hidden"
                        aria-label="Fechar menu de navegação"
                        @click="mobileOpen = false"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-2" aria-label="Navegação principal">
                    <ul class="space-y-1">
                        <li v-for="item in navigation" :key="item.href">
                            <Link
                                :href="item.href"
                                :aria-current="isActive(item.href) ? 'page' : null"
                                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-sidebar-hover text-white'
                                        : 'text-slate-600 hover:bg-sidebar-hover hover:text-white'
                                "
                            >
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                    <path :d="item.icon" />
                                </svg>
                                {{ item.label }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <p class="px-5 py-5 text-xs leading-relaxed text-slate-500">
                    Versão inicial<br />
                    Dados salvos localmente
                </p>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur lg:hidden"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 bg-slate-900 p-2 text-slate-300 hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        aria-label="Abrir menu de navegação"
                        aria-controls="app-navigation"
                        :aria-expanded="mobileOpen"
                        @click="mobileOpen = true"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>

                    <p class="truncate text-sm font-semibold text-slate-200">{{ appName }}</p>
                </header>

                <main id="main-content" tabindex="-1" class="flex-1 px-4 py-6 focus:outline-none sm:px-6 lg:px-10 lg:py-10">
                    <FlashMessages class="mb-6" />
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
