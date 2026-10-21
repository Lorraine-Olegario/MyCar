<script setup>
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const appName = computed(() => page.props.appName ?? 'MyCar');
const currentPath = computed(() => page.url.split('?')[0]);

const navigation = [
    { label: 'Dashboard', href: '/' },
    { label: 'Veículos', href: '/vehicles' },
    { label: 'Despesas', href: '/expenses' },
    { label: 'Abastecimentos', href: '/fuelings' },
    { label: 'Relatórios', href: '/reports' },
    { label: 'Categorias', href: '/categories' },
];

const isActive = (href) =>
    href === '/' ? currentPath.value === '/' : currentPath.value.startsWith(href);

const currentSection = computed(
    () => navigation.find((item) => isActive(item.href))?.label ?? appName.value,
);

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
                class="fixed inset-0 z-40 bg-slate-950/70 lg:hidden"
                aria-hidden="true"
                @click="mobileOpen = false"
            ></div>

            <aside
                id="app-navigation"
                class="fixed inset-y-0 left-0 z-50 flex w-72 max-w-[85vw] -translate-x-full flex-col border-r border-slate-800 bg-slate-900 transition-transform duration-200 ease-out lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:max-w-none lg:translate-x-0"
                :class="mobileOpen ? 'translate-x-0' : ''"
            >
                <div class="flex items-center justify-between border-b border-slate-800 px-4 py-4">
                    <Link
                        href="/"
                        class="rounded text-lg font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    >
                        {{ appName }}
                    </Link>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 lg:hidden"
                        aria-label="Fechar menu de navegação"
                        @click="mobileOpen = false"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Navegação principal">
                    <ul class="space-y-1">
                        <li v-for="item in navigation" :key="item.href">
                            <Link
                                :href="item.href"
                                :aria-current="isActive(item.href) ? 'page' : null"
                                class="flex items-center rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                :class="
                                    isActive(item.href)
                                        ? 'bg-indigo-500/15 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100'
                                "
                            >
                                {{ item.label }}
                            </Link>
                        </li>
                    </ul>
                </nav>

                <p class="border-t border-slate-800 px-4 py-3 text-xs text-slate-500">
                    {{ appName }} · versão inicial
                </p>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header
                    class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur sm:px-6 lg:px-8"
                >
                    <button
                        type="button"
                        class="rounded-lg border border-slate-700 p-2 text-slate-300 hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 lg:hidden"
                        aria-label="Abrir menu de navegação"
                        aria-controls="app-navigation"
                        :aria-expanded="mobileOpen"
                        @click="mobileOpen = true"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                        </svg>
                    </button>

                    <p class="truncate text-sm font-medium text-slate-300">{{ currentSection }}</p>
                </header>

                <main id="main-content" tabindex="-1" class="flex-1 px-4 py-6 focus:outline-none sm:px-6 lg:px-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
