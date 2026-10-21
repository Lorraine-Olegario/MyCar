<script setup>
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <Head title="Início" />

    <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-white">Aplicação inicial</h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-400">
            Página inicial renderizada pelo Inertia.js a partir de
            <code class="text-indigo-300">resources/js/pages/Home.vue</code>.
        </p>

        <p class="mt-4 text-sm text-slate-400">
            Prop compartilhada <code class="text-indigo-300">appName</code>:
            <span class="font-medium text-slate-200">{{ page.props.appName }}</span>
        </p>
    </section>
</template>
