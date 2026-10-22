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
    values: { type: Array, required: true },
    rowHeader: { type: String, default: 'Categoria' },
    valueHeader: { type: String, default: 'Total' },
    formatter: { type: Function, default: formatCurrency },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

const data = computed(() => ({
    labels: props.labels,
    datasets: [
        {
            data: props.values,
            backgroundColor: props.labels.map((_, index) => colorAt(index)),
            borderWidth: 2,
            borderColor: '#ffffff',
        },
    ],
}));

const options = computed(() => ({ ...baseOptions({ formatter: props.formatter, axes: false }), cutout: '62%', interaction: { mode: 'nearest', intersect: true } }));
const series = computed(() => [{ label: props.valueHeader, values: props.values }]);
const rows = computed(() => tableRows(props.labels, series.value, props.formatter));
</script>

<template>
    <ChartCard :title="title" :description="description" :loading="loading" :error="error" :empty="isEmptyChart(series)" :columns="[rowHeader, valueHeader]" :rows="rows">
        <ChartCanvas type="doughnut" :data="data" :options="options" :aria-label="`${title}: gráfico de rosca`" />
    </ChartCard>
</template>
