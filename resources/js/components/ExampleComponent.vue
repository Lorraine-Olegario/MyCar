<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
});

const count = ref(0);

const label = computed(() =>
    count.value === 1 ? '1 clique' : `${count.value} cliques`,
);
</script>

<template>
    <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-xl">
        <h2 class="text-lg font-semibold text-white">{{ title }}</h2>

        <p v-if="message" class="mt-2 text-sm leading-relaxed text-slate-400">
            {{ message }}
        </p>

        <div class="mt-6 flex items-center gap-4">
            <button
                type="button"
                class="rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
                @click="count++"
            >
                Incrementar
            </button>

            <span class="text-sm text-slate-400" data-testid="counter">{{ label }}</span>
        </div>
    </section>
</template>
