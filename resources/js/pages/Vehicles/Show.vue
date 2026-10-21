<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import VehicleFinancial from '../../components/vehicle/VehicleFinancial.vue';
import { formatCurrency, formatDate, formatKm } from '../../utils/format';
import { optionLabel } from '../../utils/options';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
    financial: {
        type: Object,
        required: true,
    },
    options: {
        type: Object,
        default: () => ({ statuses: [], categories: [], fuels: [] }),
    },
});

const photoFailed = ref(false);
const confirmingArchive = ref(false);
const archiving = ref(false);

const statusClass = {
    active: 'bg-emerald-500/15 text-emerald-300',
    sold: 'bg-indigo-500/15 text-indigo-300',
};

const mainDetails = computed(() => [
    { label: 'Marca', value: props.vehicle.brand },
    { label: 'Modelo', value: props.vehicle.model },
    { label: 'Versão', value: props.vehicle.version },
    {
        label: 'Ano',
        value: `${props.vehicle.manufacture_year}/${props.vehicle.model_year}`,
    },
    { label: 'Categoria', value: optionLabel(props.options.categories, props.vehicle.category) },
    { label: 'Cor', value: props.vehicle.color },
    { label: 'Combustível', value: optionLabel(props.options.fuels, props.vehicle.fuel) },
    { label: 'Placa', value: props.vehicle.formatted_plate ?? 'Sem placa' },
    { label: 'Odômetro atual', value: formatKm(props.vehicle.odometer) },
]);

const acquisitionDetails = computed(() => [
    { label: 'Data de aquisição', value: formatDate(props.vehicle.acquisition_date) },
    { label: 'Valor de compra', value: formatCurrency(props.vehicle.purchase_price_cents) },
    {
        label: 'Odômetro na aquisição',
        value:
            props.vehicle.acquisition_odometer === null
                ? ''
                : formatKm(props.vehicle.acquisition_odometer),
    },
]);

const hasSale = computed(
    () => props.vehicle.sale_date !== null || props.vehicle.sale_price_cents !== null,
);

const saleDetails = computed(() => [
    { label: 'Data de venda', value: formatDate(props.vehicle.sale_date) },
    { label: 'Valor de venda', value: formatCurrency(props.vehicle.sale_price_cents) },
]);

const saleResultClass = computed(() => {
    const result = props.vehicle.sale_result_cents;

    if (result === null || result === 0) {
        return 'text-slate-100';
    }

    return result > 0 ? 'text-emerald-300' : 'text-rose-300';
});

const tabs = [
    {
        id: 'expenses',
        label: 'Despesas',
        placeholder: 'Despesas avulsas, recorrentes e manutenções entram na Fase 3.',
    },
    {
        id: 'fuelings',
        label: 'Abastecimentos',
        placeholder: 'Abastecimentos, consumo e custo por quilômetro entram na Fase 3.',
    },
    {
        id: 'maintenances',
        label: 'Manutenções',
        placeholder: 'O histórico de manutenções entra na Fase 3.',
    },
    {
        id: 'fipe',
        label: 'FIPE',
        placeholder: 'A consulta e o histórico de valores FIPE entram na Fase 5.',
    },
];

const activeTab = ref(tabs[0].id);

const activePlaceholder = computed(
    () => tabs.find((tab) => tab.id === activeTab.value)?.placeholder ?? '',
);

const confirmArchive = () => {
    archiving.value = true;

    router.delete(`/vehicles/${props.vehicle.id}`, {
        onFinish: () => {
            archiving.value = false;
            confirmingArchive.value = false;
        },
    });
};
</script>

<template>
    <Head :title="vehicle.full_name" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400">
                    Veículo
                </p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-100">
                    {{ vehicle.full_name }}
                </h1>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="statusClass[vehicle.status] ?? 'bg-slate-800 text-slate-300'"
                    >
                        {{ optionLabel(options.statuses, vehicle.status) }}
                    </span>

                    <span class="text-sm text-slate-400">
                        {{ vehicle.formatted_plate ?? 'Sem placa' }}
                    </span>

                    <span v-if="vehicle.ownership_label" class="text-sm text-slate-400">
                        Propriedade: {{ vehicle.ownership_label }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Link
                    href="/vehicles"
                    class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    Voltar para veículos
                </Link>

                <Link
                    :href="`/vehicles/${vehicle.id}/edit`"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    Editar
                </Link>

                <BaseButton variant="ghost" @click="confirmingArchive = true">
                    Arquivar
                </BaseButton>
            </div>
        </div>

        <p
            v-if="vehicle.status === 'sold'"
            role="status"
            class="rounded-2xl border border-indigo-800 bg-indigo-950 px-5 py-4 text-sm leading-relaxed text-indigo-200"
        >
            Este veículo foi vendido<template v-if="vehicle.sale_date"> em {{ formatDate(vehicle.sale_date) }}</template>
            e não conta como ativo.
            <template v-if="vehicle.ownership_label">
                Período de propriedade: {{ vehicle.ownership_label }}
                (de {{ formatDate(vehicle.acquisition_date) }}<template v-if="vehicle.sale_date"> até {{ formatDate(vehicle.sale_date) }}</template>).
            </template>
            Todo o histórico continua disponível.
        </p>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
            <div class="self-start overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">
                <div class="aspect-[4/3] w-full bg-slate-900">
                    <img
                        v-if="vehicle.photo_url && !photoFailed"
                        :src="vehicle.photo_url"
                        :alt="`Foto de ${vehicle.full_name}`"
                        class="h-full w-full object-cover"
                        @error="photoFailed = true"
                    >
                    <p
                        v-else
                        class="flex h-full w-full items-center justify-center px-4 text-center text-xs font-medium uppercase tracking-widest text-slate-600"
                    >
                        Sem foto cadastrada
                    </p>
                </div>
            </div>

            <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                    Dados principais
                </h2>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <div v-for="detail in mainDetails" :key="detail.label">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">
                            {{ detail.label }}
                        </dt>
                        <dd class="mt-1 text-sm text-slate-200">{{ detail.value || '—' }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                    Aquisição
                </h2>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div v-for="detail in acquisitionDetails" :key="detail.label">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">
                            {{ detail.label }}
                        </dt>
                        <dd class="mt-1 text-sm text-slate-200">{{ detail.value || '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section v-if="hasSale" class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                    Venda
                </h2>

                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div v-for="detail in saleDetails" :key="detail.label">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">
                            {{ detail.label }}
                        </dt>
                        <dd class="mt-1 text-sm text-slate-200">{{ detail.value || '—' }}</dd>
                    </div>

                    <div v-if="vehicle.sale_result_cents !== null" class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-wide text-slate-500">
                            Resultado da venda
                        </dt>
                        <dd class="mt-1 text-lg font-semibold" :class="saleResultClass">
                            {{ formatCurrency(vehicle.sale_result_cents) }}
                        </dd>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">
                            Calculado apenas com os valores de compra e venda. O valor de compra
                            não é contado como despesa, e as despesas operacionais (combustível,
                            manutenção e outras) entram nesse resultado nas Fases 3 e 4.
                        </p>
                    </div>
                </dl>
            </section>
        </div>

        <VehicleFinancial :vehicle="vehicle" :financial="financial" />

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                Observações
            </h2>

            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-300">
                {{ vehicle.notes || 'Sem observações registradas.' }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40">
            <div
                role="tablist"
                aria-label="Históricos do veículo"
                class="flex flex-wrap gap-1 border-b border-slate-800 p-2"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    :id="`vehicle-tab-${tab.id}`"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.id"
                    :aria-controls="`vehicle-panel-${tab.id}`"
                    :tabindex="activeTab === tab.id ? 0 : -1"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    :class="
                        activeTab === tab.id
                            ? 'bg-slate-800 text-slate-100'
                            : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200'
                    "
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div
                :id="`vehicle-panel-${activeTab}`"
                role="tabpanel"
                :aria-labelledby="`vehicle-tab-${activeTab}`"
                tabindex="0"
                class="p-6 focus:outline-none"
            >
                <p class="text-sm leading-relaxed text-slate-400">{{ activePlaceholder }}</p>
            </div>
        </section>

        <ConfirmDialog
            :show="confirmingArchive"
            title="Arquivar veículo"
            :message="`O veículo ${vehicle.full_name} será arquivado. Nada é apagado: despesas e histórico continuam guardados e o veículo pode ser restaurado depois.`"
            confirm-label="Arquivar"
            :processing="archiving"
            @confirm="confirmArchive"
            @cancel="confirmingArchive = false"
        />
    </div>
</template>
