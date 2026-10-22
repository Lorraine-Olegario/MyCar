<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    reports: { type: Array, required: true },
});
</script>

<template>
    <Head title="Relatórios" />

    <div class="mx-auto max-w-6xl space-y-6">
        <header>
            <h1 class="text-3xl font-bold tracking-tight text-slate-100">Relatórios</h1>
            <p class="mt-1 max-w-prose text-sm text-slate-400">
                Todos os relatórios usam as mesmas consultas do dashboard: para o mesmo filtro, os números são os mesmos.
            </p>
        </header>

        <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="report in reports" :key="report.key">
                <Link
                    :href="`/reports/${report.key}`"
                    class="flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900 p-5 transition hover:border-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    <span class="text-base font-semibold text-slate-100">{{ report.title }}</span>
                    <span class="mt-1 text-sm text-slate-500">{{ report.description }}</span>
                    <span class="mt-auto pt-4 text-sm font-medium text-indigo-500">Abrir relatório →</span>
                </Link>
            </li>
        </ul>
    </div>
</template>
