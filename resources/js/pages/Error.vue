<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    status: {
        type: Number,
        default: 404,
    },
});
</script>

<template>
    <Head title="Página não encontrada" />

    <section
        class="mx-auto max-w-prose rounded-2xl border border-dashed border-slate-700 bg-slate-900/40 p-8 text-center"
    >
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
            {{ status }}
        </p>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-100">
            Página não encontrada
        </h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-400">
            O endereço acessado não existe ou foi movido.
        </p>
        <Link
            href="/"
            class="mt-6 inline-flex rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
        >
            Voltar ao dashboard
        </Link>
    </section>
</template>
