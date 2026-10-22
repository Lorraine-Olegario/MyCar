<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import UpcomingDueList from '../recurring/UpcomingDueList.vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseDateInput from '../ui/BaseDateInput.vue';
import BasePagination from '../ui/BasePagination.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import { formatCurrency, formatDate, formatKm, formatMonthYear } from '../../utils/format';

const props = defineProps({
    vehicle: { type: Object, required: true },
    history: { type: Object, required: true },
    selection: { type: Object, required: true },
});

const NO_DATA = 'Sem dados suficientes';

const tabs = [
    { id: 'expenses', label: 'Despesas' },
    { id: 'fuelings', label: 'Abastecimentos' },
    { id: 'maintenances', label: 'Manutenções' },
    { id: 'recurring', label: 'Recorrências' },
    { id: 'dues', label: 'Documentos e vencimentos' },
];

const filterTabs = ['expenses', 'fuelings', 'maintenances'];
const categoryTabs = ['expenses', 'maintenances'];

const form = reactive({
    scope: props.history.filters.scope,
    from: props.history.filters.from,
    to: props.history.filters.to,
    category_id: props.history.filters.category_id,
});

const tab = computed(() => props.history.tab);
const data = computed(() => props.history.data);
const hasFilters = computed(() => filterTabs.includes(tab.value));

const selectedLabel = computed(() =>
    props.selection.month
        ? formatMonthYear(`${props.selection.year}-${String(props.selection.month).padStart(2, '0')}`)
        : String(props.selection.year),
);

const scopeOptions = computed(() => [
    { value: 'all', label: 'Todo o histórico' },
    { value: 'selected', label: `Período selecionado (${selectedLabel.value})` },
    { value: 'custom', label: 'Personalizado' },
]);
const categoryOptions = computed(() => [{ value: '', label: 'Todas as categorias' }, ...props.history.categories]);

// O ano e o mês escolhidos no bloco financeiro acompanham a navegação entre abas.
const visit = (overrides = {}) => {
    const params = {
        tab: tab.value,
        year: props.selection.year,
        month: props.selection.month ?? '',
        scope: form.scope,
        from: form.scope === 'custom' ? form.from : '',
        to: form.scope === 'custom' ? form.to : '',
        category_id: categoryTabs.includes(tab.value) ? form.category_id : '',
        ...overrides,
    };

    Object.keys(params).forEach((key) => {
        if (params[key] === '' || params[key] === null) {
            delete params[key];
        }
    });

    router.get(`/vehicles/${props.vehicle.id}`, params, { preserveScroll: true, preserveState: true, replace: true });
};

const setScope = (value) => {
    form.scope = value;

    if (value !== 'custom') {
        visit({ scope: value, from: '', to: '' });
    }
};

const applyCustom = () => {
    if (form.from && form.to) {
        visit();
    }
};

const goTab = (id) => visit({ tab: id, page: undefined, category_id: categoryTabs.includes(id) ? form.category_id : '' });

const number = (value, digits = 2) => Number(value).toLocaleString('pt-BR', { minimumFractionDigits: digits, maximumFractionDigits: digits });
const kmPerLiter = (value) => (value === null || value === undefined ? NO_DATA : `${number(value)} km/l`);
const costPerKm = (cents) => (cents === null || cents === undefined ? NO_DATA : `${formatCurrency(Math.round(cents))}/km`);

const dueBadge = {
    overdue: 'bg-rose-500/15 text-rose-300',
    due_soon: 'bg-amber-500/15 text-amber-700',
};
const dueLabel = { overdue: 'Atrasado', due_soon: 'Próximo' };
const statusLabel = { forecast: 'Previsto', paid: 'Pago', canceled: 'Cancelado', superseded: 'Substituído' };
const statusStyle = {
    forecast: 'bg-amber-500/15 text-amber-700',
    paid: 'bg-emerald-500/15 text-emerald-300',
    canceled: 'bg-slate-800 text-slate-400 line-through',
    superseded: 'bg-slate-800 text-slate-400',
};
const frequencyLabel = { monthly: 'Mensal', quarterly: 'Trimestral', semiannual: 'Semestral', annual: 'Anual' };
const typeLabel = { preventive: 'Preventiva', corrective: 'Corretiva' };

const createLink = computed(
    () =>
        ({
            expenses: { href: `/expenses?vehicle_id=${props.vehicle.id}`, label: 'Registrar despesa' },
            fuelings: { href: `/fuelings?vehicle_id=${props.vehicle.id}`, label: 'Registrar abastecimento' },
            maintenances: { href: `/maintenances?vehicle_id=${props.vehicle.id}`, label: 'Registrar manutenção' },
            recurring: { href: '/recurring', label: 'Gerenciar recorrências' },
            dues: { href: '/recurring', label: 'Gerenciar vencimentos' },
        })[tab.value] ?? null,
);
</script>

<template>
    <section class="space-y-6" aria-labelledby="vehicle-history-title">
        <header>
            <h2 id="vehicle-history-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Históricos e vencimentos</h2>
            <p class="mt-1 text-sm text-slate-400">Registros antigos continuam acessíveis; use o filtro de período para recortar.</p>
        </header>

        <UpcomingDueList
            v-if="history.upcoming.length > 0"
            :items="history.upcoming"
            title="Vencimentos em destaque"
            :show-vehicle="false"
        />

        <div class="rounded-2xl border border-slate-800 bg-slate-900">
            <div role="tablist" aria-label="Históricos do veículo" class="flex flex-wrap gap-1 border-b border-slate-800 p-2">
                <button
                    v-for="item in tabs"
                    :id="`history-tab-${item.id}`"
                    :key="item.id"
                    type="button"
                    role="tab"
                    :aria-selected="tab === item.id"
                    :aria-controls="`history-panel-${item.id}`"
                    :tabindex="tab === item.id ? 0 : -1"
                    class="rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    :class="tab === item.id ? 'bg-slate-800 text-slate-100' : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200'"
                    @click="goTab(item.id)"
                >
                    {{ item.label }}
                </button>
            </div>

            <div :id="`history-panel-${tab}`" role="tabpanel" :aria-labelledby="`history-tab-${tab}`" tabindex="0" class="space-y-4 p-5 focus:outline-none">
                <div v-if="hasFilters" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <BaseSelect :model-value="form.scope" label="Período" :options="scopeOptions" @update:model-value="setScope" />
                    <template v-if="form.scope === 'custom'">
                        <BaseDateInput v-model="form.from" label="De" @update:model-value="applyCustom" />
                        <BaseDateInput v-model="form.to" label="Até" @update:model-value="applyCustom" />
                    </template>
                    <BaseSelect
                        v-if="categoryTabs.includes(tab)"
                        v-model="form.category_id"
                        label="Categoria"
                        :options="categoryOptions"
                        @update:model-value="(value) => { form.category_id = value; visit({ category_id: value, page: undefined }); }"
                    />
                </div>

                <div v-if="createLink" class="flex justify-end">
                    <Link :href="createLink.href" class="text-sm font-semibold text-indigo-500 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400">
                        {{ createLink.label }} →
                    </Link>
                </div>

                <!-- Despesas -->
                <template v-if="tab === 'expenses'">
                    <p class="text-sm text-slate-400">
                        {{ data.items.total }} {{ data.items.total === 1 ? 'despesa' : 'despesas' }} ·
                        total <strong class="font-mono text-slate-100">{{ formatCurrency(data.total_cents) }}</strong>
                    </p>
                    <p v-if="data.items.data.length === 0" class="rounded-xl bg-slate-950 px-4 py-6 text-center text-sm text-slate-500">Nenhuma despesa neste recorte.</p>
                    <ul v-else class="divide-y divide-slate-800">
                        <li v-for="expense in data.items.data" :key="expense.id" class="flex flex-wrap items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-100">{{ expense.description }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ formatDate(expense.occurred_on) }} · {{ expense.category?.name }}
                                    <span v-if="expense.recurring_expense_id" class="ml-1 rounded-full bg-indigo-950 px-2 py-0.5 text-indigo-200">Recorrente</span>
                                    <span v-if="expense.is_maintenance" class="ml-1 rounded-full bg-slate-800 px-2 py-0.5 text-slate-300">Manutenção</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-sm font-bold text-slate-100">{{ formatCurrency(expense.amount_cents) }}</p>
                                <Link :href="expense.is_maintenance ? `/maintenances?vehicle_id=${vehicle.id}` : `/expenses?edit=${expense.id}`" class="text-xs text-indigo-500 hover:underline">Editar</Link>
                            </div>
                        </li>
                    </ul>
                    <BasePagination :links="data.items.links ?? []" label="Paginação de despesas" />
                </template>

                <!-- Abastecimentos -->
                <template v-else-if="tab === 'fuelings'">
                    <dl class="grid gap-3 sm:grid-cols-4">
                        <div><dt class="text-xs text-slate-500">Litros</dt><dd class="font-mono text-sm font-bold text-slate-100">{{ number(data.summary.total_liters, 3) }} L</dd></div>
                        <div><dt class="text-xs text-slate-500">Valor</dt><dd class="font-mono text-sm font-bold text-slate-100">{{ formatCurrency(data.summary.total_cents) }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Consumo médio</dt><dd class="font-mono text-sm font-bold text-slate-100">{{ kmPerLiter(data.summary.average_km_per_liter) }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Custo por km</dt><dd class="font-mono text-sm font-bold text-slate-100">{{ costPerKm(data.summary.cost_per_km_cents) }}</dd></div>
                    </dl>
                    <p v-if="data.items.data.length === 0" class="rounded-xl bg-slate-950 px-4 py-6 text-center text-sm text-slate-500">Nenhum abastecimento neste recorte.</p>
                    <ul v-else class="divide-y divide-slate-800">
                        <li v-for="fueling in data.items.data" :key="fueling.id" class="flex flex-wrap items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-100">
                                    {{ formatDate(fueling.fueled_on) }} · {{ formatKm(fueling.odometer) }}
                                    <span class="ml-1 rounded-full px-2 py-0.5 text-xs" :class="fueling.full_tank ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-800 text-slate-400'">{{ fueling.full_tank ? 'Cheio' : 'Parcial' }}</span>
                                </p>
                                <p class="text-xs text-slate-500">{{ number(fueling.liters, 3) }} L · R$ {{ number(fueling.price_per_liter, 3) }}/L</p>
                                <p class="mt-1 text-xs text-slate-400">
                                    <template v-if="fueling.stats?.km_per_liter != null">
                                        {{ formatKm(fueling.stats.distance_km) }} · <strong class="text-slate-200">{{ kmPerLiter(fueling.stats.km_per_liter) }}</strong> · {{ costPerKm(fueling.stats.cost_per_km_cents) }}
                                    </template>
                                    <template v-else>{{ NO_DATA }}</template>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-sm font-bold text-slate-100">{{ formatCurrency(fueling.total_cents) }}</p>
                                <Link :href="`/fuelings?edit=${fueling.id}`" class="text-xs text-indigo-500 hover:underline">Editar</Link>
                            </div>
                        </li>
                    </ul>
                    <BasePagination :links="data.items.links ?? []" label="Paginação de abastecimentos" />
                </template>

                <!-- Manutenções -->
                <template v-else-if="tab === 'maintenances'">
                    <p v-if="data.items.data.length === 0" class="rounded-xl bg-slate-950 px-4 py-6 text-center text-sm text-slate-500">Nenhuma manutenção neste recorte.</p>
                    <ul v-else class="divide-y divide-slate-800">
                        <li v-for="item in data.items.data" :key="item.id" class="flex flex-wrap items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-100">
                                    {{ item.description }}
                                    <span class="ml-1 rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-300">{{ typeLabel[item.type] }}</span>
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ formatDate(item.occurred_on) }} · {{ item.category?.name }}
                                    <template v-if="item.odometer != null"> · {{ formatKm(item.odometer) }}</template>
                                </p>
                                <p v-if="item.next_due_on || item.next_due_odometer != null" class="mt-1 text-xs text-slate-400">
                                    Próxima troca: <template v-if="item.next_due_on">{{ formatDate(item.next_due_on) }}</template>
                                    <template v-if="item.next_due_on && item.next_due_odometer != null"> ou </template>
                                    <template v-if="item.next_due_odometer != null">{{ formatKm(item.next_due_odometer) }}</template>
                                    <span v-if="dueBadge[item.due.status]" class="ml-2 rounded-full px-2 py-0.5 font-medium" :class="dueBadge[item.due.status]">{{ dueLabel[item.due.status] }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-sm font-bold text-slate-100">{{ formatCurrency(item.amount_cents) }}</p>
                                <Link :href="`/maintenances?edit=${item.id}`" class="text-xs text-indigo-500 hover:underline">Editar</Link>
                            </div>
                        </li>
                    </ul>
                    <BasePagination :links="data.items.links ?? []" label="Paginação de manutenções" />
                </template>

                <!-- Recorrências -->
                <template v-else-if="tab === 'recurring'">
                    <p v-if="data.items.length === 0" class="rounded-xl bg-slate-950 px-4 py-6 text-center text-sm text-slate-500">Nenhuma despesa recorrente para este veículo.</p>
                    <ul v-else class="divide-y divide-slate-800">
                        <li v-for="item in data.items" :key="item.id" class="flex flex-wrap items-start justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold" :class="item.active ? 'text-slate-100' : 'text-slate-500'">{{ item.description }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ item.category?.name }} · {{ frequencyLabel[item.frequency] }} · vence dia {{ item.due_day }} ·
                                    próximo: {{ item.next_due_date ? formatDate(item.next_due_date) : '—' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-mono text-sm font-bold text-slate-100">{{ formatCurrency(item.expected_amount_cents) }} <span class="font-sans text-xs font-normal text-slate-500">previsto</span></p>
                                <p class="text-xs text-slate-500">≈ {{ formatCurrency(item.monthly_estimate_cents) }}/mês</p>
                                <Link :href="`/recurring?edit=${item.id}`" class="text-xs text-indigo-500 hover:underline">Editar</Link>
                            </div>
                        </li>
                    </ul>
                </template>

                <!-- Documentos e vencimentos -->
                <template v-else-if="tab === 'dues'">
                    <p class="text-xs text-slate-500">Vencimentos de recorrências (seguro, IPVA, licenciamento e outras) e próximas trocas de manutenção. Previsões não são gastos pagos.</p>
                    <p v-if="data.items.data.length === 0" class="rounded-xl bg-slate-950 px-4 py-6 text-center text-sm text-slate-500">Nenhum vencimento registrado.</p>
                    <ul v-else class="divide-y divide-slate-800">
                        <li v-for="item in data.items.data" :key="item.key" class="flex flex-wrap items-start justify-between gap-3 py-3" :class="item.highlight === 'overdue' ? 'rounded-lg bg-rose-500/5' : ''">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-100">
                                    {{ item.description }}
                                    <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-medium" :class="statusStyle[item.status]">{{ statusLabel[item.status] }}</span>
                                    <span v-if="item.highlight" class="ml-1 rounded-full px-2 py-0.5 text-xs font-medium" :class="dueBadge[item.highlight]">{{ dueLabel[item.highlight] }}</span>
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ item.kind === 'maintenance' ? 'Manutenção' : 'Recorrência' }} ·
                                    <template v-if="item.due_date">{{ formatDate(item.due_date) }}</template>
                                    <template v-if="item.due_date && item.due_odometer != null"> ou </template>
                                    <template v-if="item.due_odometer != null">{{ formatKm(item.due_odometer) }}</template>
                                </p>
                            </div>
                            <p v-if="item.amount_cents != null" class="font-mono text-sm font-bold text-slate-100">{{ formatCurrency(item.amount_cents) }}</p>
                        </li>
                    </ul>
                    <BasePagination :links="data.items.links ?? []" label="Paginação de vencimentos" />
                </template>
            </div>
        </div>
    </section>
</template>
