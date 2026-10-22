<script setup>
import { computed } from 'vue';
import { formatDate } from '../../utils/format';

const props = defineProps({
    completeness: { type: Object, default: null },
});

const vehicles = computed(() => props.completeness?.incomplete_vehicles ?? []);
const visible = computed(() => props.completeness !== null && props.completeness.is_complete === false);
</script>

<template>
    <aside v-if="visible" role="note" class="rounded-2xl border border-indigo-800 bg-indigo-950 px-5 py-4 text-sm leading-relaxed text-indigo-200">
        <strong class="font-semibold">Histórico incompleto.</strong>
        O período consultado inclui datas anteriores ao início do acompanhamento
        <template v-if="vehicles.length > 0">
            de
            <template v-for="(vehicle, index) in vehicles" :key="vehicle.id">
                {{ vehicle.name }}<template v-if="vehicle.tracking_start_date"> (desde {{ formatDate(vehicle.tracking_start_date) }})</template><template v-if="index < vehicles.length - 1">, </template>
            </template>
        </template>
        e pode não conter todos os gastos desse intervalo; os totais valem apenas para o que foi registrado.
    </aside>
</template>
