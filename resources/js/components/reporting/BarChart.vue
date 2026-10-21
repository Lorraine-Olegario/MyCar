<script setup>
import { computed } from 'vue';
import { baseOptions, colorAt, isEmptyChart, tableRows } from '../../utils/chartData';
import { formatCurrency } from '../../utils/format';
import ChartCanvas from './ChartCanvas.vue';
import ChartCard from './ChartCard.vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    labels: { type: Array, required: true },
    series: { type: Array, required: true },
    rowHeader: { type: String, default: 'Item' },
    horizontal: { type: Boolean, default: false },
    formatter: { type: Function, default: formatCurrency },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const data = computed(() => ({
    labels: props.labels,
    datasets: props.series.map((serie, index) => ({
        label: serie.label,
        data: serie.values,
        backgroundColor: colorAt(index),
        borderRadius: 4,
    })),
}));

const options = computed(() => {
    const options = baseOptions({ formatter: props.formatter, legend: props.series.length > 1 });

    return props.horizontal ? { ...options, indexAxis: 'y' } : options;
});

const columns = computed(() => [props.rowHeader, ...props.series.map((serie) => serie.label)]);
const rows = computed(() => tableRows(props.labels, props.series, props.formatter));
</script>

<template>
    <ChartCard :title="title" :description="description" :loading="loading" :error="error" :empty="isEmptyChart(series)" :columns="columns" :rows="rows">
        <ChartCanvas type="bar" :data="data" :options="options" :aria-label="`${title}: gráfico de barras`" />
    </ChartCard>
</template>
