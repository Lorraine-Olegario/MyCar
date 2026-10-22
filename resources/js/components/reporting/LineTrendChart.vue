<script setup>
import { computed } from 'vue';
import { baseOptions, colorAt, isEmptyChart, shortMonth, tableRows } from '../../utils/chartData';
import { formatCurrency, formatMonthYear } from '../../utils/format';
import ChartCanvas from './ChartCanvas.vue';
import ChartCard from './ChartCard.vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    labels: { type: Array, required: true },
    series: { type: Array, required: true },
    formatter: { type: Function, default: formatCurrency },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const data = computed(() => ({
    labels: props.labels.map(shortMonth),
    datasets: props.series.map((serie, index) => ({
        label: serie.label,
        data: serie.values,
        borderColor: colorAt(index),
        backgroundColor: `${colorAt(index)}22`,
        fill: props.series.length === 1,
        tension: 0.3,
        pointRadius: 3,
    })),
}));

const options = computed(() => baseOptions({ formatter: props.formatter, legend: props.series.length > 1 }));
const columns = computed(() => ['Mês', ...props.series.map((serie) => serie.label)]);
const rows = computed(() => tableRows(props.labels.map((label) => formatMonthYear(label)), props.series, props.formatter));
</script>

<template>
    <ChartCard :title="title" :description="description" :loading="loading" :error="error" :empty="isEmptyChart(series)" :columns="columns" :rows="rows">
        <ChartCanvas type="line" :data="data" :options="options" :aria-label="`${title}: gráfico de linhas por mês`" />
    </ChartCard>
</template>
