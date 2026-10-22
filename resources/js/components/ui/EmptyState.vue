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
    badge: {
        type: String,
        default: 'Em construção',
    },
    headingLevel: {
        type: String,
        default: 'h1',
    },
});
</script>

<template>
    <section
        class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/40 p-8 text-center"
    >
        <p v-if="badge" class="text-xs font-semibold uppercase tracking-widest text-indigo-400">
            {{ badge }}
        </p>

        <component
            :is="headingLevel"
            class="mt-3 text-2xl font-semibold tracking-tight text-slate-100"
        >
            {{ title }}
        </component>

        <p class="mx-auto mt-2 max-w-prose text-sm leading-relaxed text-slate-400">
            {{ description }}
        </p>

        <div v-if="$slots.default" class="mt-6 flex flex-wrap justify-center gap-3">
            <slot />
        </div>
    </section>
</template>
