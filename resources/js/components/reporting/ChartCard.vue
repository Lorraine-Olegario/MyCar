<script setup>
import { computed, ref, useId } from 'vue';
import EmptyState from '../ui/EmptyState.vue';
import ErrorState from '../ui/ErrorState.vue';
import LoadingState from '../ui/LoadingState.vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    empty: { type: Boolean, default: false },
    emptyMessage: { type: String, default: 'Sem dados para o período e os filtros selecionados.' },
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});

const showTable = ref(false);
const id = useId();
const hasTable = computed(() => props.columns.length > 0);
</script>

<template>
    <section class="rounded-2xl border border-slate-800 bg-slate-900 p-5" :aria-labelledby="`${id}-title`">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 :id="`${id}-title`" class="text-base font-semibold text-slate-100">{{ title }}</h2>
                <p v-if="description" class="mt-0.5 text-xs text-slate-500">{{ description }}</p>
            </div>

            <div v-if="hasTable && !empty && !loading && !error" class="inline-flex rounded-lg border border-slate-700 p-0.5 text-xs" role="group" aria-label="Forma de exibição">
                <button
                    type="button"
                    class="rounded-md px-3 py-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    :class="!showTable ? 'bg-slate-800 font-medium text-slate-100' : 'text-slate-400'"
                    :aria-pressed="!showTable"
                    @click="showTable = false"
                >
                    Gráfico
                </button>
                <button
                    type="button"
                    class="rounded-md px-3 py-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    :class="showTable ? 'bg-slate-800 font-medium text-slate-100' : 'text-slate-400'"
                    :aria-pressed="showTable"
                    @click="showTable = true"
                >
                    Tabela
                </button>
            </div>
        </header>

        <div class="mt-4">
            <LoadingState v-if="loading" label="Carregando gráfico…" />
            <ErrorState v-else-if="error" :message="error" />
            <p v-else-if="empty" class="flex min-h-40 items-center justify-center rounded-xl bg-slate-950 px-4 text-center text-sm text-slate-500">
                {{ emptyMessage }}
            </p>

            <template v-else>
                <div :class="{ 'sr-only': showTable }" :aria-hidden="showTable || undefined">
                    <slot />
                </div>

                <!-- A tabela fica sempre no DOM: leitores de tela recebem os mesmos dados do gráfico. -->
                <div v-if="hasTable" :class="showTable ? 'overflow-x-auto' : 'sr-only'">
                    <table class="w-full text-sm">
                        <caption class="sr-only">{{ title }}</caption>
                        <thead class="text-left text-xs text-slate-500">
                            <tr>
                                <th v-for="(column, index) in columns" :key="column" scope="col" class="pb-2 font-medium" :class="index > 0 ? 'text-right' : ''">{{ column }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, rowIndex) in rows" :key="rowIndex" class="border-t border-slate-800">
                                <th scope="row" class="py-1.5 text-left font-normal text-slate-200">{{ row[0] }}</th>
                                <td v-for="(cell, cellIndex) in row.slice(1)" :key="cellIndex" class="py-1.5 text-right font-mono text-slate-100">{{ cell }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>
    </section>
</template>
