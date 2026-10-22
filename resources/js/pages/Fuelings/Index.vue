<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseCurrencyInput from '../../components/ui/BaseCurrencyInput.vue';
import BaseDateInput from '../../components/ui/BaseDateInput.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseModal from '../../components/ui/BaseModal.vue';
import BasePagination from '../../components/ui/BasePagination.vue';
import BaseSelect from '../../components/ui/BaseSelect.vue';
import BaseTextarea from '../../components/ui/BaseTextarea.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import LoadingState from '../../components/ui/LoadingState.vue';
import { formatCurrency, formatDate, formatKm, formatMonthYear } from '../../utils/format';

const props = defineProps({
    fuelings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    summary: { type: Object, required: true },
    editing: { type: Object, default: null },
    monthly: { type: Array, default: () => [] },
    yearly: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({ vehicles: [], fuel_types: [] }) },
});

const filterForm = reactive({
    vehicle_id: props.filters.vehicle_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

const loading = ref(false);
const formOpen = ref(false);
const editing = ref(null);
const fuelingToDelete = ref(null);
const deleting = ref(false);
const totalTouched = ref(false);

const hasFilters = computed(() => Object.values(filterForm).some((value) => String(value) !== ''));
const vehicleFilterOptions = computed(() => [{ value: '', label: 'Todos os veículos' }, ...props.options.vehicles]);

const NO_DATA = 'Sem dados suficientes';
const number = (value, digits = 2) => Number(value).toLocaleString('pt-BR', { minimumFractionDigits: digits, maximumFractionDigits: digits });
const liters = (value) => `${number(value, 3)} L`;
const perLiter = (cents) => (cents === null || cents === undefined ? NO_DATA : `${formatCurrency(cents)}/L`);
const kmPerLiter = (value) => (value === null || value === undefined ? NO_DATA : `${number(value)} km/l`);
const costPerKm = (cents) => (cents === null || cents === undefined ? NO_DATA : `${formatCurrency(Math.round(cents))}/km`);

const applyFilters = () => {
    const params = {};

    Object.entries(filterForm).forEach(([key, value]) => {
        if (String(value) !== '') {
            params[key] = value;
        }
    });

    router.get('/fuelings', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
    });
};

const clearFilters = () => {
    Object.keys(filterForm).forEach((key) => (filterForm[key] = ''));
    applyFilters();
};

const form = useForm({
    vehicle_id: '',
    fueled_on: '',
    odometer: '',
    liters: '',
    price_per_liter: '',
    total_cents: null,
    fuel_type: '',
    station: '',
    full_tank: true,
    notes: '',
    confirm_duplicate: false,
});

const todayIso = () => {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
};

// O total acompanha litros × preço enquanto o usuário não o ajustar manualmente.
const suggestedTotal = computed(() => {
    const l = parseFloat(String(form.liters).replace(',', '.'));
    const p = parseFloat(String(form.price_per_liter).replace(',', '.'));

    return Number.isFinite(l) && Number.isFinite(p) && l > 0 && p > 0 ? Math.round(l * p * 100) : null;
});

const syncTotal = () => {
    if (!totalTouched.value) {
        form.total_cents = suggestedTotal.value;
    }
};

const onNumberInput = (field, value) => {
    form[field] = String(value ?? '').replace(/[^0-9.,]/g, '');
    syncTotal();
};

const onTotalInput = (value) => {
    totalTouched.value = true;
    form.total_cents = value;
};

const onVehicleChange = (value) => {
    form.vehicle_id = value;
    const vehicle = props.options.vehicles.find((item) => String(item.value) === String(value));

    if (vehicle && !editing.value) {
        form.fuel_type = vehicle.fuel ?? form.fuel_type;
    }
};

const openCreate = () => {
    editing.value = null;
    totalTouched.value = false;
    form.reset();
    form.clearErrors();
    form.fueled_on = todayIso();
    form.vehicle_id = filterForm.vehicle_id !== '' ? Number(filterForm.vehicle_id) : (props.options.vehicles[0]?.value ?? '');
    onVehicleChange(form.vehicle_id);
    form.full_tank = true;
    formOpen.value = true;
};

const openEdit = (fueling) => {
    editing.value = fueling;
    totalTouched.value = true;
    form.clearErrors();
    form.vehicle_id = fueling.vehicle_id;
    form.fueled_on = fueling.fueled_on;
    form.odometer = fueling.odometer;
    form.liters = String(fueling.liters);
    form.price_per_liter = String(fueling.price_per_liter);
    form.total_cents = fueling.total_cents;
    form.fuel_type = fueling.fuel_type;
    form.station = fueling.station ?? '';
    form.full_tank = fueling.full_tank;
    form.notes = fueling.notes ?? '';
    form.confirm_duplicate = false;
    formOpen.value = true;
};

// Link de edição vindo de outra tela (?edit=ID): abre o formulário existente, sem duplicá-lo.
onMounted(() => {
    if (props.editing) {
        openEdit(props.editing);
    }
});

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (formOpen.value = false) };

    form.transform((data) => ({
        ...data,
        price_per_liter: data.price_per_liter === '' ? null : String(data.price_per_liter).replace(',', '.'),
        liters: String(data.liters).replace(',', '.'),
    }));

    if (editing.value) {
        form.put(`/fuelings/${editing.value.id}`, options);
    } else {
        form.post('/fuelings', options);
    }
};

const confirmDelete = () => {
    deleting.value = true;

    router.delete(`/fuelings/${fuelingToDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            fuelingToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Abastecimentos" />

    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-100">Abastecimentos</h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">
                    O consumo é calculado entre tanques cheios consecutivos; quando faltam dados, nada é estimado.
                </p>
            </div>

            <BaseButton :disabled="options.vehicles.length === 0" @click="openCreate">Novo abastecimento</BaseButton>
        </header>

        <form class="rounded-2xl border border-slate-800 bg-slate-900 p-5" @submit.prevent="applyFilters">
            <div class="grid gap-4 md:grid-cols-3">
                <BaseSelect v-model="filterForm.vehicle_id" label="Veículo" :options="vehicleFilterOptions" />
                <BaseDateInput v-model="filterForm.from" label="De" />
                <BaseDateInput v-model="filterForm.to" label="Até" />
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
                <BaseButton type="submit" :loading="loading">Filtrar</BaseButton>
                <BaseButton v-if="hasFilters" variant="secondary" :disabled="loading" @click="clearFilters">Limpar filtros</BaseButton>
            </div>
        </form>

        <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <dt class="text-sm text-slate-300">Litros</dt>
                <dd class="mt-2 font-mono text-2xl font-bold text-slate-100">{{ liters(summary.total_liters) }}</dd>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <dt class="text-sm text-slate-300">Valor gasto</dt>
                <dd class="mt-2 font-mono text-2xl font-bold text-slate-100">{{ formatCurrency(summary.total_cents) }}</dd>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <dt class="text-sm text-slate-300">Preço médio por litro</dt>
                <dd class="mt-2 font-mono text-2xl font-bold text-slate-100">{{ perLiter(summary.average_price_cents_per_liter) }}</dd>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <dt class="text-sm text-slate-300">Consumo médio</dt>
                <dd class="mt-2 font-mono text-2xl font-bold text-slate-100">{{ kmPerLiter(summary.average_km_per_liter) }}</dd>
                <p class="mt-1 text-xs text-slate-500">Custo por km: {{ costPerKm(summary.cost_per_km_cents) }}</p>
            </div>
        </dl>

        <LoadingState v-if="loading" label="Carregando abastecimentos…" />

        <template v-else>
            <EmptyState
                v-if="fuelings.data.length === 0"
                badge="Nenhum resultado"
                heading-level="h2"
                :title="hasFilters ? 'Nenhum abastecimento encontrado' : 'Nenhum abastecimento registrado'"
                :description="hasFilters ? 'Ajuste ou limpe os filtros.' : 'Registre o primeiro abastecimento para acompanhar consumo e custo por km.'"
            >
                <BaseButton v-if="hasFilters" variant="secondary" @click="clearFilters">Limpar filtros</BaseButton>
            </EmptyState>

            <template v-else>
                <section class="grid gap-4 lg:grid-cols-2" aria-label="Totais por período">
                    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Por mês</h2>
                        <table class="mt-3 w-full text-sm">
                            <caption class="sr-only">Litros e valor gasto por mês</caption>
                            <thead class="text-left text-xs text-slate-500">
                                <tr><th scope="col" class="pb-1 font-medium">Mês</th><th scope="col" class="pb-1 text-right font-medium">Litros</th><th scope="col" class="pb-1 text-right font-medium">Valor</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in monthly" :key="row.period" class="border-t border-slate-800">
                                    <td class="py-1.5 text-slate-200">{{ formatMonthYear(row.period) }}</td>
                                    <td class="py-1.5 text-right font-mono text-slate-300">{{ liters(row.liters) }}</td>
                                    <td class="py-1.5 text-right font-mono text-slate-100">{{ formatCurrency(row.total_cents) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Por ano</h2>
                        <table class="mt-3 w-full text-sm">
                            <caption class="sr-only">Litros e valor gasto por ano</caption>
                            <thead class="text-left text-xs text-slate-500">
                                <tr><th scope="col" class="pb-1 font-medium">Ano</th><th scope="col" class="pb-1 text-right font-medium">Litros</th><th scope="col" class="pb-1 text-right font-medium">Valor</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in yearly" :key="row.period" class="border-t border-slate-800">
                                    <td class="py-1.5 text-slate-200">{{ row.period }}</td>
                                    <td class="py-1.5 text-right font-mono text-slate-300">{{ liters(row.liters) }}</td>
                                    <td class="py-1.5 text-right font-mono text-slate-100">{{ formatCurrency(row.total_cents) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <p class="text-sm text-slate-400">
                    Mostrando {{ fuelings.from }}–{{ fuelings.to }} de {{ fuelings.total }}
                    {{ fuelings.total === 1 ? 'abastecimento' : 'abastecimentos' }}
                </p>

                <ul class="divide-y divide-slate-800 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                    <li v-for="fueling in fuelings.data" :key="fueling.id" class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-100">
                                    {{ formatDate(fueling.fueled_on) }} · {{ fueling.vehicle?.name }}
                                    <span
                                        class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="fueling.full_tank ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-800 text-slate-400'"
                                    >
                                        {{ fueling.full_tank ? 'Tanque cheio' : 'Parcial' }}
                                    </span>
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ formatKm(fueling.odometer) }} · {{ liters(fueling.liters) }} · R$ {{ number(fueling.price_per_liter, 3) }}/L
                                    <template v-if="fueling.station"> · {{ fueling.station }}</template>
                                </p>

                                <p class="mt-2 text-xs text-slate-400" data-testid="fueling-stats">
                                    <template v-if="fueling.stats?.km_per_liter != null">
                                        {{ formatKm(fueling.stats.distance_km) }} percorridos ·
                                        <strong class="text-slate-200">{{ kmPerLiter(fueling.stats.km_per_liter) }}</strong> ·
                                        {{ costPerKm(fueling.stats.cost_per_km_cents) }}
                                    </template>
                                    <template v-else>
                                        {{ NO_DATA }}<span v-if="fueling.stats?.reason"> — {{ fueling.stats.reason }}</span>
                                    </template>
                                </p>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <p class="font-mono text-lg font-bold text-slate-100">{{ formatCurrency(fueling.total_cents) }}</p>
                                <div class="flex gap-1">
                                    <BaseButton variant="secondary" size="sm" @click="openEdit(fueling)">Editar</BaseButton>
                                    <BaseButton variant="ghost" size="sm" @click="fuelingToDelete = fueling">Excluir</BaseButton>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>

                <BasePagination :links="fuelings.links ?? []" label="Paginação de abastecimentos" class="pt-2" />
            </template>
        </template>

        <BaseModal :show="formOpen" size="lg" labelled-by="fueling-form-title" @close="formOpen = false">
            <form class="space-y-4" novalidate @submit.prevent="submit">
                <h2 id="fueling-form-title" class="text-lg font-semibold text-slate-100">
                    {{ editing ? 'Editar abastecimento' : 'Novo abastecimento' }}
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <BaseSelect
                        :model-value="form.vehicle_id"
                        label="Veículo"
                        :options="options.vehicles"
                        required
                        :error="form.errors.vehicle_id"
                        @update:model-value="onVehicleChange"
                    />
                    <BaseDateInput v-model="form.fueled_on" label="Data" required :error="form.errors.fueled_on" />
                    <BaseInput
                        :model-value="form.odometer"
                        label="Odômetro (km)"
                        required
                        :error="form.errors.odometer"
                        @update:model-value="form.odometer = String($event).replace(/\D/g, '')"
                    />
                    <BaseSelect v-model="form.fuel_type" label="Combustível" placeholder="Selecione" :options="options.fuel_types" required :error="form.errors.fuel_type" />
                    <BaseInput :model-value="form.liters" label="Litros" required hint="Até 3 casas decimais." :error="form.errors.liters" @update:model-value="onNumberInput('liters', $event)" />
                    <BaseInput :model-value="form.price_per_liter" label="Preço por litro (R$)" :error="form.errors.price_per_liter" @update:model-value="onNumberInput('price_per_liter', $event)" />
                    <BaseCurrencyInput
                        :model-value="form.total_cents"
                        label="Valor total"
                        hint="Calculado por litros × preço; edite para ajustar."
                        :error="form.errors.total_cents"
                        @update:model-value="onTotalInput"
                    />
                    <BaseInput v-model="form.station" label="Posto" :error="form.errors.station" />
                    <label class="flex items-center gap-2 text-sm text-slate-300 sm:col-span-2">
                        <input v-model="form.full_tank" type="checkbox" class="h-4 w-4 rounded border-slate-600 text-indigo-500">
                        Tanque cheio (necessário para calcular o consumo)
                    </label>
                    <div class="sm:col-span-2">
                        <BaseTextarea v-model="form.notes" label="Observações" :rows="2" :error="form.errors.notes" />
                    </div>
                </div>

                <div v-if="form.errors.duplicate" class="rounded-lg border border-rose-800 bg-rose-950 px-4 py-3 text-sm text-rose-200" role="alert">
                    <p>{{ form.errors.duplicate }}</p>
                    <label class="mt-2 flex items-center gap-2">
                        <input v-model="form.confirm_duplicate" type="checkbox" class="h-4 w-4 rounded border-slate-600 text-indigo-500">
                        Registrar mesmo assim
                    </label>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <BaseButton variant="secondary" :disabled="form.processing" @click="formOpen = false">Cancelar</BaseButton>
                    <BaseButton type="submit" :loading="form.processing">Salvar</BaseButton>
                </div>
            </form>
        </BaseModal>

        <ConfirmDialog
            :show="fuelingToDelete !== null"
            title="Excluir abastecimento"
            :message="`O abastecimento de ${formatDate(fuelingToDelete?.fueled_on)} (${formatCurrency(fuelingToDelete?.total_cents ?? 0)}) será excluído e o consumo será recalculado.`"
            confirm-label="Excluir"
            :processing="deleting"
            @confirm="confirmDelete"
            @cancel="fuelingToDelete = null"
        />
    </div>
</template>
