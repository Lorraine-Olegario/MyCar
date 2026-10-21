<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCurrency, formatDate, formatKm } from '../../utils/format';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
});

const details = computed(() => [
    { label: 'Marca', value: props.vehicle.brand },
    { label: 'Modelo', value: props.vehicle.model },
    { label: 'Versão', value: props.vehicle.version },
    { label: 'Ano', value: `${props.vehicle.manufacture_year}/${props.vehicle.model_year}` },
    { label: 'Placa', value: props.vehicle.formatted_plate ?? 'Sem placa' },
    { label: 'Cor', value: props.vehicle.color ?? '—' },
    { label: 'Odômetro', value: formatKm(props.vehicle.odometer) },
    { label: 'Aquisição', value: formatDate(props.vehicle.acquisition_date) },
    { label: 'Preço de compra', value: formatCurrency(props.vehicle.purchase_price_cents) },
]);
</script>

<template>
    <Head :title="vehicle.full_name" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400">
                    {{ vehicle.status === 'sold' ? 'Vendido' : 'Ativo' }}
                </p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                    {{ vehicle.full_name }}
                </h1>
            </div>

            <Link
                href="/vehicles"
                class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            >
                Voltar para veículos
            </Link>
        </div>

        <dl class="grid gap-4 rounded-2xl border border-slate-800 bg-slate-900/40 p-6 sm:grid-cols-2">
            <div v-for="detail in details" :key="detail.label">
                <dt class="text-xs uppercase tracking-wide text-slate-500">{{ detail.label }}</dt>
                <dd class="mt-1 text-sm text-slate-200">{{ detail.value || '—' }}</dd>
            </div>
        </dl>

        <p class="text-sm text-slate-500">
            Edição, fotos e histórico do veículo serão adicionados nas próximas tarefas desta fase.
        </p>
    </div>
</template>
