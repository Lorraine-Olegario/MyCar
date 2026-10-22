<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import LineTrendChart from '../reporting/LineTrendChart.vue';
import BaseButton from '../ui/BaseButton.vue';
import BaseSelect from '../ui/BaseSelect.vue';
import { formatCurrency, formatDate, formatMonthYear } from '../../utils/format';

const props = defineProps({
    vehicle: { type: Object, required: true },
    fipe: { type: Object, required: true },
});

const page = usePage();
const base = computed(() => `/vehicles/${props.vehicle.id}/fipe`);
const serverError = computed(() => page.props.errors?.fipe ?? '');
const selectionErrors = computed(() => Object.entries(page.props.errors ?? {}).filter(([key]) => ['type', 'brand_id', 'model_id', 'year_id'].includes(key)).map(([, message]) => message));

const form = reactive({
    type: props.fipe.vehicle_type ?? '',
    brand: props.fipe.selection?.brand_id ?? '',
    model: props.fipe.selection?.model_id ?? '',
    year: props.fipe.selection?.year_id ?? '',
});

const catalog = reactive({
    brands: { items: [], loading: false, error: '' },
    models: { items: [], loading: false, error: '' },
    years: { items: [], loading: false, error: '' },
});

const submitting = ref(false);
const refreshing = ref(false);

const toOptions = (items) => items.map((item) => ({ value: item.code, label: item.name }));

const load = async (step, params) => {
    const state = catalog[step];

    state.loading = true;
    state.error = '';
    state.items = [];

    try {
        const query = new URLSearchParams(params).toString();
        const response = await fetch(`${base.value}/${step}?${query}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const body = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(body.message ?? 'Não foi possível carregar as opções da FIPE.');
        }

        state.items = toOptions(body.data);
    } catch (error) {
        state.error = error.message || 'Não foi possível carregar as opções da FIPE.';
    } finally {
        state.loading = false;
    }
};

const loadBrands = () => (form.type ? load('brands', { type: form.type }) : null);
const loadModels = () => (form.brand ? load('models', { type: form.type, brand: form.brand }) : null);
const loadYears = () => (form.model ? load('years', { type: form.type, brand: form.brand, model: form.model }) : null);

const resetBelow = (level) => {
    if (level <= 0) {
        form.brand = '';
        catalog.brands.items = [];
    }

    if (level <= 1) {
        form.model = '';
        catalog.models.items = [];
    }

    if (level <= 2) {
        form.year = '';
        catalog.years.items = [];
    }
};

const onType = (value) => {
    form.type = value;
    resetBelow(0);
    loadBrands();
};

const onBrand = (value) => {
    form.brand = value;
    resetBelow(1);
    loadModels();
};

const onModel = (value) => {
    form.model = value;
    resetBelow(2);
    loadYears();
};

onMounted(async () => {
    if (!form.type) {
        return;
    }

    await loadBrands();

    if (form.brand) {
        await loadModels();
    }

    if (form.model) {
        await loadYears();
    }
});

watch(() => props.fipe.selection, () => {}, { deep: true });

const canSubmit = computed(() => form.type && form.brand && form.model && form.year && !submitting.value);

const consult = () => {
    router.post(base.value, { type: form.type, brand_id: form.brand, model_id: form.model, year_id: form.year }, {
        preserveScroll: true,
        onStart: () => (submitting.value = true),
        onFinish: () => (submitting.value = false),
    });
};

const refresh = () => {
    router.post(`${base.value}/refresh`, {}, {
        preserveScroll: true,
        onStart: () => (refreshing.value = true),
        onFinish: () => (refreshing.value = false),
    });
};

const snapshot = computed(() => props.fipe.snapshot);
const history = computed(() => props.fipe.quotes);

const chart = computed(() => {
    const ordered = [...history.value].reverse();

    return {
        labels: ordered.map((quote) => quote.reference_month),
        series: [{ label: 'Valor FIPE', values: ordered.map((quote) => quote.value_cents) }],
    };
});

const consultedAt = (value) => formatDate(String(value).slice(0, 10));
</script>

<template>
    <section class="space-y-6" aria-labelledby="vehicle-fipe-title">
        <header>
            <h2 id="vehicle-fipe-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Tabela FIPE</h2>
            <p class="mt-1 text-sm text-slate-400">
                A FIPE é uma referência de mercado, não um preço garantido de venda, e nunca é somada aos gastos.
            </p>
        </header>

        <div v-if="serverError" class="rounded-2xl border border-rose-800 bg-rose-950 px-5 py-4 text-sm text-rose-200" role="alert">
            <p>{{ serverError }}</p>
            <p v-if="snapshot" class="mt-1 text-rose-300">
                O valor exibido abaixo é o da última consulta bem-sucedida ({{ consultedAt(snapshot.consulted_at) }}) e não foi alterado.
            </p>
            <BaseButton v-if="fipe.selection" class="mt-3" variant="secondary" size="sm" :loading="refreshing" @click="refresh">Tentar novamente</BaseButton>
        </div>

        <article v-if="snapshot" class="rounded-2xl border border-slate-800 bg-slate-900 p-5" aria-label="Cotação FIPE atual">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-300">Valor FIPE</p>
                    <p class="mt-2 font-mono text-3xl font-bold text-slate-100">{{ formatCurrency(snapshot.value_cents) }}</p>
                    <p class="mt-1 text-sm text-slate-400">Referência: {{ formatMonthYear(snapshot.reference_month) }}</p>
                </div>

                <BaseButton v-if="fipe.selection" :loading="refreshing" @click="refresh">Atualizar cotação</BaseButton>
            </div>

            <p
                v-if="snapshot.is_stale"
                class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                role="status"
            >
                Cotação desatualizada: a última consulta foi em {{ consultedAt(snapshot.consulted_at) }} (mais de {{ snapshot.stale_after_days }} dias). Atualize para ver o valor atual.
            </p>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                <div><dt class="text-xs text-slate-500">Marca</dt><dd class="text-slate-100">{{ snapshot.brand ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Modelo</dt><dd class="text-slate-100">{{ snapshot.model ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Ano do modelo</dt><dd class="text-slate-100">{{ snapshot.model_year ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Código FIPE</dt><dd class="font-mono text-slate-100">{{ snapshot.fipe_code }}</dd></div>
                <div><dt class="text-xs text-slate-500">Combustível</dt><dd class="text-slate-100">{{ snapshot.fuel ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Última consulta</dt><dd class="text-slate-100">{{ consultedAt(snapshot.consulted_at) }}</dd></div>
            </dl>
        </article>

        <p v-else class="rounded-2xl border border-dashed border-slate-700 bg-slate-900 px-5 py-6 text-sm text-slate-500">
            Valor FIPE não consultado. Escolha marca, modelo e ano/versão abaixo.
        </p>

        <form class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-5" aria-label="Seleção FIPE" @submit.prevent="consult">
            <h3 class="text-sm font-semibold text-slate-100">{{ snapshot ? 'Alterar seleção' : 'Selecionar veículo na FIPE' }}</h3>

            <div class="grid gap-4 md:grid-cols-2">
                <BaseSelect
                    v-if="fipe.needs_type || form.type"
                    :model-value="form.type"
                    label="Tipo de veículo"
                    placeholder="Selecione o tipo"
                    :options="fipe.types"
                    :disabled="!fipe.needs_type"
                    @update:model-value="onType"
                />

                <div>
                    <BaseSelect :model-value="form.brand" label="Marca" :placeholder="catalog.brands.loading ? 'Carregando…' : 'Selecione a marca'" :options="catalog.brands.items" @update:model-value="onBrand" />
                    <p v-if="catalog.brands.error" class="mt-1 text-xs text-rose-400" role="alert">
                        {{ catalog.brands.error }}
                        <button type="button" class="underline" @click="loadBrands">Tentar novamente</button>
                    </p>
                </div>

                <div>
                    <BaseSelect :model-value="form.model" label="Modelo" :placeholder="catalog.models.loading ? 'Carregando…' : 'Selecione o modelo'" :options="catalog.models.items" @update:model-value="onModel" />
                    <p v-if="catalog.models.error" class="mt-1 text-xs text-rose-400" role="alert">
                        {{ catalog.models.error }}
                        <button type="button" class="underline" @click="loadModels">Tentar novamente</button>
                    </p>
                </div>

                <div>
                    <BaseSelect v-model="form.year" label="Ano / versão" :placeholder="catalog.years.loading ? 'Carregando…' : 'Selecione o ano e combustível'" :options="catalog.years.items" hint="Escolha o ano do modelo e o combustível corretos do seu veículo." />
                    <p v-if="catalog.years.error" class="mt-1 text-xs text-rose-400" role="alert">
                        {{ catalog.years.error }}
                        <button type="button" class="underline" @click="loadYears">Tentar novamente</button>
                    </p>
                </div>
            </div>

            <ul v-if="selectionErrors.length" class="text-sm text-rose-400" role="alert">
                <li v-for="message in selectionErrors" :key="message">{{ message }}</li>
            </ul>

            <BaseButton type="submit" :disabled="!canSubmit" :loading="submitting">Consultar valor</BaseButton>
        </form>

        <div v-if="history.length > 0" class="space-y-4">
            <LineTrendChart
                title="Evolução do valor FIPE"
                description="Valor de cada consulta, por mês de referência"
                :labels="chart.labels"
                :series="chart.series"
            />

            <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900">
                <table class="w-full min-w-[28rem] text-sm">
                    <caption class="sr-only">Histórico de cotações FIPE</caption>
                    <thead class="bg-slate-950 text-xs text-slate-500">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left font-medium">Consulta</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium">Mês de referência</th>
                            <th scope="col" class="px-4 py-3 text-left font-medium">Modelo</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="quote in history" :key="quote.id" class="border-t border-slate-800">
                            <td class="px-4 py-2.5 text-slate-300">{{ consultedAt(quote.consulted_at) }}</td>
                            <td class="px-4 py-2.5 text-slate-300">{{ formatMonthYear(quote.reference_month) }}</td>
                            <td class="px-4 py-2.5 text-slate-300">{{ quote.model }} ({{ quote.model_year }})</td>
                            <td class="px-4 py-2.5 text-right font-mono text-slate-100">{{ formatCurrency(quote.value_cents) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>
