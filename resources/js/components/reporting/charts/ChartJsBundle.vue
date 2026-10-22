<script setup>
// Este arquivo concentra chart.js e vue-chartjs e só é carregado sob demanda (import dinâmico).
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';
import { Bar, Doughnut, Line } from 'vue-chartjs';

ChartJS.register(ArcElement, BarElement, CategoryScale, Filler, Legend, LinearScale, LineElement, PointElement, Tooltip);

defineProps({
    type: { type: String, required: true },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});
</script>

<template>
    <Line v-if="type === 'line'" :data="data" :options="options" />
    <Bar v-else-if="type === 'bar'" :data="data" :options="options" />
    <Doughnut v-else :data="data" :options="options" />
</template>
