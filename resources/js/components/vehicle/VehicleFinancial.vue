<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseSelect from '../ui/BaseSelect.vue';
import DataCompletenessNotice from '../reporting/DataCompletenessNotice.vue';
import DonutChart from '../reporting/DonutChart.vue';
import KpiCard from '../reporting/KpiCard.vue';
import LineTrendChart from '../reporting/LineTrendChart.vue';
import { formatCurrency, formatDate, formatMonthYear } from '../../utils/format';

const props = defineProps({
    vehicle: { type: Object, required: true },
    financial: { type: Object, required: true },
});

const loading = ref(false);
const summary = computed(() => props.financial.summary);
const selection = computed(() => props.financial.selection);

const yearOptions = computed(() => props.financial.years.map((year) => ({ value: year.value, label: year.label })));
const monthOptions = computed(() => [
    { value: '', label: 'Ano inteiro' },
    ...Array.from({ length: 12 }, (_, index) => ({
        value: index + 1,
        label: formatMonthYear(`2000-${String(index + 1).padStart(2, '0')}`).split(' ')[0],
    })),
]);

const money = (cents) => (cents === null || cents === undefined ? null : formatCurrency(cents));
const perKm = (cents) => (cents === null || cents === undefined ? null : `${formatCurrency(Math.round(cents))}/km`);

const select = (key, value) => {
    const params = {
        year: key === 'year' ? value : selection.value.year,
        month: key === 'month' ? value : (key === 'year' ? '' : (selection.value.month ?? '')),
    };

    if (params.month === '') {
        delete params.month;
    }

    router.get(`/vehicles/${props.vehicle.id}`, params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

const monthly = computed(() => ({
    labels: props.financial.monthly.map((item) => item.month),
    series: [{ label: 'Despesas', values: props.financial.monthly.map((item) => item.total_cents) }],
}));
const categories = computed(() => ({
    labels: props.financial.categories.map((item) => item.name),
    values: props.financial.categories.map((item) => item.total_cents),
}));

const resultTone = computed(() => {
    const result = summary.value.sale_result_cents;

    return result === null ? 'default' : result >= 0 ? 'positive' : 'negative';
});

const resultHint = computed(() => {
    if (summary.value.status !== 'sold') {
        return 'Calculado quando o veículo for vendido';
    }

    return summary.value.sale_result_cents === null
        ? 'Informe os valores de compra e venda para calcular'
        : 'Venda − compra − despesas operacionais (sem FIPE)';
});

const selectedLabel = computed(() => {
    const { year, month } = selection.value;

    return month ? `${formatMonthYear(`${year}-${String(month).padStart(2, '0')}`)}` : String(year);
});

const ownership = computed(() => {
    const end = summary.value.ownership_end ? formatDate(summary.value.ownership_end) : 'hoje';

    return `${formatDate(summary.value.ownership_start)} a ${end} · ${summary.value.months_owned} ${summary.value.months_owned === 1 ? 'mês' : 'meses'}`;
});
</script>

<template>
    <section class="space-y-6" aria-labelledby="vehicle-financial-title">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="vehicle-financial-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Financeiro</h2>
                <p class="mt-1 text-sm text-slate-400">Propriedade: {{ ownership }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3" role="group" aria-label="Período dos gastos">
                <BaseSelect :model-value="selection.year" label="Ano" :options="yearOptions" @update:model-value="select('year', $event)" />
                <BaseSelect :model-value="selection.month ?? ''" label="Mês" :options="monthOptions" @update:model-value="select('month', $event)" />
            </div>
        </header>

        <DataCompletenessNotice :completeness="financial.completeness" />

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" :aria-busy="loading">
            <KpiCard
                label="Despesas desde a aquisição"
                :value="money(summary.operating_cents)"
                hint="Somente despesas operacionais; a compra não entra aqui"
            />
            <KpiCard
                label="Custo total de propriedade"
                :value="money(summary.tco_cents)"
                :hint="summary.purchase_known ? 'Valor de compra + despesas operacionais' : 'Valor de compra não informado: considera só as despesas'"
            />
            <KpiCard
                :label="`Despesas em ${financial.selection.year}`"
                :value="money(financial.year_totals.total_cents)"
                :hint="financial.year_totals.has_data ? `${financial.year_totals.count} lançamento(s)` : 'Sem lançamentos no ano'"
            />
            <KpiCard
                v-if="financial.month_totals"
                :label="`Despesas em ${selectedLabel}`"
                :value="money(financial.month_totals.total_cents)"
                :hint="financial.month_totals.has_data ? `${financial.month_totals.count} lançamento(s)` : 'Sem lançamentos no mês'"
            />
            <KpiCard
                label="Custo médio mensal"
                :value="money(summary.average_monthly_cost_cents)"
                :hint="`Operacional: ${formatCurrency(summary.average_monthly_operating_cents)} por mês`"
            />
            <KpiCard
                label="Custo por km"
                :value="perKm(summary.cost_per_km_cents)"
                empty-text="Dados insuficientes"
                :hint="summary.distance_km ? `Despesas ÷ ${summary.distance_km.toLocaleString('pt-BR')} km rodados` : 'Informe o odômetro de aquisição e mantenha o odômetro atualizado'"
            />
            <KpiCard
                label="Resultado financeiro"
                :value="money(summary.sale_result_cents)"
                :tone="resultTone"
                empty-text="—"
                :hint="resultHint"
            />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <LineTrendChart
                :title="`Evolução mensal em ${financial.selection.year}`"
                description="Despesas realizadas por mês (sem o valor de compra)"
                :labels="monthly.labels"
                :series="monthly.series"
                :loading="loading"
            />
            <DonutChart
                :title="`Por categoria em ${selectedLabel}`"
                description="Participação de cada categoria"
                :labels="categories.labels"
                :values="categories.values"
                :loading="loading"
            />
        </div>
    </section>
</template>
