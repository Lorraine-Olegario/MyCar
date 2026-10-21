<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const appName = computed(() => page.props.appName ?? 'MyCar');
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div class="mx-auto flex min-h-screen max-w-3xl flex-col px-6 py-16">
            <header class="mb-10">
                <p class="text-sm font-medium uppercase tracking-widest text-indigo-400">
                    Laravel + Inertia + Vue 3
                </p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-white">
                    {{ appName }}
                </h1>
            </header>

            <main class="flex-1">
                <slot />
            </main>

            <footer class="mt-10 border-t border-slate-800 pt-6 text-sm text-slate-500">
                Laravel, Inertia.js, Vue 3, Vite e SQLite.
            </footer>
        </div>
    </div>
</template>
