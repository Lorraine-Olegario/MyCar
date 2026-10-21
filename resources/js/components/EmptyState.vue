<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: 'Esta seção ainda está em construção e será implementada em uma próxima fase.',
    },
});
</script>

<template>
    <section
        class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/40 p-8 text-center"
    >
        <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400">
            Em construção
        </p>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-white">{{ title }}</h1>
        <p class="mx-auto mt-2 max-w-prose text-sm leading-relaxed text-slate-400">
            {{ description }}
        </p>
    </section>
</template>
