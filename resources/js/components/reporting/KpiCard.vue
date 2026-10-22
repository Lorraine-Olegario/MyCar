<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    label: { type: String, required: true },
    value: { type: String, default: null },
    hint: { type: String, default: '' },
    emptyText: { type: String, default: '—' },
    tone: { type: String, default: 'default' },
    href: { type: String, default: '' },
    linkLabel: { type: String, default: '' },
    loading: { type: Boolean, default: false },
});

const toneClass = {
    default: 'text-slate-100',
    positive: 'text-emerald-300',
    negative: 'text-rose-300',
    muted: 'text-slate-500',
};
</script>

<template>
    <article class="flex min-h-32 flex-col rounded-2xl border border-slate-800 bg-slate-900 p-5">
        <h3 class="text-sm text-slate-300">{{ label }}</h3>

        <div v-if="loading" class="mt-3 h-8 w-32 animate-pulse rounded bg-slate-800" role="status" aria-label="Carregando"></div>
        <p v-else class="mt-3 font-mono text-2xl font-bold" :class="value === null ? toneClass.muted : (toneClass[tone] ?? toneClass.default)">
            {{ value ?? emptyText }}
        </p>

        <p v-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>

        <Link v-if="href && linkLabel" :href="href" class="mt-auto pt-3 text-sm text-indigo-500 underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400">
            {{ linkLabel }}
        </Link>
    </article>
</template>
