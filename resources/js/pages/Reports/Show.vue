<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BarChart from '../../components/reporting/BarChart.vue';
import CategoryFilter from '../../components/reporting/CategoryFilter.vue';
import DataCompletenessNotice from '../../components/reporting/DataCompletenessNotice.vue';
import DonutChart from '../../components/reporting/DonutChart.vue';
import PeriodFilter from '../../components/reporting/PeriodFilter.vue';
import VehicleFilter from '../../components/reporting/VehicleFilter.vue';
import BasePagination from '../../components/ui/BasePagination.vue';
import { useUrlFilters } from '../../composables/useUrlFilters';
import { formatCell, isNumericType } from '../../utils/reportFormat';

const props = defineProps({
    report: { type: Object, required: true },
    filters: { type: Object, required: true },
    applied: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    total_rows: { type: Number, default: 0 },
    totals: { type: Object, default: null },
    chart: { type: Object, default: null },
    pagination: { type: Object, default: null },
    completeness: { type: Object, default: null },
    options: { type: Object, default: () => ({ vehicles: [], categories: [] }) },
});

const { filters: form, loading, apply, set } = useUrlFilters(`/reports/${props.report.key}`, {
    period: props.filters.period,
    from: props.filters.from,
    to: props.filters.to,
    vehicle_id: props.filters.vehicle_id,
    category_id: props.filters.category_id,
    include_sold: props.filters.include_sold,
    include_archived: props.filters.include_archived,
});

const onPeriod = ({ period, from, to }) => {
    form.period = period;
    form.from = period === 'custom' ? (from ?? '') : '';
    form.to = period === 'custom' ? (to ?? '') : '';

    if (period !== 'custom' || (form.from && form.to)) {
        apply();
    }
};

const toggle = (key, checked) => set(key, checked ? '1' : '');

const csvHref = computed(() => {
    const params = new URLSearchParams();

    Object.entries(form).forEach(([key, value]) => {
        if (String(value) !== '') {
            params.set(key, value);
        }
    });

    const query = params.toString();

    return `/reports/${props.report.key}/csv${query ? `?${query}` : ''}`;
});

const chartData = computed(() => {
    if (!props.chart) {
        return null;
    }

    return {
        labels: props.chart.labels,
        series: props.chart.series,
        values: props.chart.series[0]?.values ?? [],
    };
});

const cell = (column, row) => formatCell(column.type, row[column.key]);
const align = (column) => (isNumericType(column.type) ? 'text-right font-mono' : 'text-left');
</script>

<template>
    <Head :title="report.title" />

    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <Link href="/reports" class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-500 hover:underline">← Relatórios</Link>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-100">{{ report.title }}</h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">{{ report.description }}</p>
            </div>

            <!-- Link comum (não Inertia): o navegador baixa o arquivo com os filtros atuais. -->
            <a
                :href="csvHref"
                class="rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm font-medium text-slate-100 transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            >
                Exportar CSV
            </a>
        </header>

        <form class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-5" aria-label="Filtros do relatório" @submit.prevent="apply">
            <div class="grid gap-4 md:grid-cols-2">
                <VehicleFilter :model-value="form.vehicle_id" :vehicles="options.vehicles" @update:model-value="set('vehicle_id', $event)" />
                <CategoryFilter v-if="report.supports.category" :model-value="form.category_id" :categories="options.categories" @update:model-value="set('category_id', $event)" />
            </div>

            <PeriodFilter v-if="report.supports.period" :period="form.period" :from="form.from" :to="form.to" @change="onPeriod" />

            <div v-if="report.supports.scope" class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-300">
                <label class="flex items-center gap-2">
                    <input :checked="form.include_sold === '1'" type="checkbox" class="h-4 w-4 rounded border-slate-600 text-indigo-500" @change="toggle('include_sold', $event.target.checked)">
                    Incluir veículos vendidos
                </label>
                <label class="flex items-center gap-2">
                    <input :checked="form.include_archived === '1'" type="checkbox" class="h-4 w-4 rounded border-slate-600 text-indigo-500" @change="toggle('include_archived', $event.target.checked)">
                    Incluir veículos arquivados
                </label>
            </div>
        </form>

        <section aria-label="Filtros aplicados" class="flex flex-wrap gap-2 text-sm">
            <span v-for="item in applied" :key="item.label" class="rounded-full border border-slate-800 bg-slate-900 px-3 py-1 text-slate-300">
                <span class="text-slate-500">{{ item.label }}:</span> {{ item.value }}
            </span>
        </section>

        <DataCompletenessNotice :completeness="completeness" />

        <template v-if="chartData">
            <DonutChart v-if="chart.type === 'donut'" :title="report.title" :labels="chartData.labels" :values="chartData.values" :loading="loading" />
            <BarChart v-else :title="report.title" :labels="chartData.labels" :series="chartData.series" :row-header="columns[0].label" :loading="loading" />
        </template>

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900" :aria-busy="loading">
            <p v-if="rows.length === 0" class="px-5 py-10 text-center text-sm text-slate-500">
                Sem dados para o período e os filtros selecionados.
            </p>

            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[32rem] text-sm">
                    <caption class="sr-only">{{ report.title }}</caption>
                    <thead class="bg-slate-950 text-xs text-slate-500">
                        <tr>
                            <th v-for="column in columns" :key="column.key" scope="col" class="px-4 py-3 font-medium" :class="isNumericType(column.type) ? 'text-right' : 'text-left'">
                                {{ column.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in rows" :key="index" class="border-t border-slate-800">
                            <td v-for="column in columns" :key="column.key" class="px-4 py-2.5 text-slate-200" :class="align(column)">
                                {{ cell(column, row) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="totals">
                        <tr class="border-t-2 border-slate-700 bg-slate-950 font-semibold">
                            <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-slate-100" :class="align(column)">
                                {{ totals[column.key] === undefined ? '' : cell(column, totals) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div v-if="pagination" class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-400">Mostrando {{ pagination.from }}–{{ pagination.to }} de {{ pagination.total }}</p>
            <BasePagination :links="pagination.links" label="Paginação do relatório" />
        </div>
    </div>
</template>
