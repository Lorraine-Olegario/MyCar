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
import { formatCurrency, formatDate } from '../../utils/format';

const props = defineProps({
    expenses: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    total_cents: { type: Number, default: 0 },
    editing: { type: Object, default: null },
    options: {
        type: Object,
        default: () => ({ vehicles: [], categories: [], payment_methods: [] }),
    },
});

const filterForm = reactive({
    vehicle_id: props.filters.vehicle_id ?? '',
    expense_category_id: props.filters.expense_category_id ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    search: props.filters.search ?? '',
});

const loading = ref(false);
const formOpen = ref(false);
const editing = ref(null);
const expenseToDelete = ref(null);
const deleting = ref(false);
const receiptInput = ref(null);
const receiptError = ref('');

const hasFilters = computed(() => Object.values(filterForm).some((value) => String(value).trim() !== ''));

const vehicleFilterOptions = computed(() => [{ value: '', label: 'Todos os veículos' }, ...props.options.vehicles]);
const categoryFilterOptions = computed(() => [{ value: '', label: 'Todas as categorias' }, ...props.options.categories]);

// Categorias inativas só aparecem no formulário quando já são a categoria da despesa editada.
const formCategoryOptions = computed(() =>
    props.options.categories
        .filter((category) => category.active || category.value === editing.value?.expense_category_id)
        .map((category) => ({
            value: category.value,
            label: category.active ? category.label : `${category.label} (inativa)`,
        })),
);

const paymentOptions = computed(() => props.options.payment_methods);
const paymentLabel = (value) => props.options.payment_methods.find((method) => method.value === value)?.label ?? '';

const trackingStart = (vehicleId) =>
    props.options.vehicles.find((vehicle) => vehicle.value === vehicleId)?.tracking_start_date ?? null;

// Aviso quando o período consultado começa antes do acompanhamento do(s) veículo(s) filtrado(s).
const incompleteHistoryVehicles = computed(() => {
    const from = props.filters.from;

    if (!from) {
        return [];
    }

    return props.options.vehicles.filter(
        (vehicle) =>
            vehicle.tracking_start_date &&
            vehicle.tracking_start_date > from &&
            (String(props.filters.vehicle_id ?? '') === '' || String(vehicle.value) === String(props.filters.vehicle_id)),
    );
});

const beforeTracking = (expense) => {
    const start = expense.vehicle?.tracking_start_date?.slice(0, 10);

    return Boolean(start) && expense.occurred_on < start;
};

const query = () => {
    const params = {};

    Object.entries(filterForm).forEach(([key, value]) => {
        if (String(value).trim() !== '') {
            params[key] = String(value).trim();
        }
    });

    return params;
};

const applyFilters = () => {
    router.get('/expenses', query(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => {
            loading.value = true;
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const clearFilters = () => {
    Object.keys(filterForm).forEach((key) => {
        filterForm[key] = '';
    });
    applyFilters();
};

const form = useForm({
    vehicle_id: '',
    expense_category_id: '',
    description: '',
    amount_cents: null,
    occurred_on: '',
    odometer: '',
    vendor: '',
    payment_method: '',
    notes: '',
    receipt: null,
    remove_receipt: false,
});

const todayIso = () => {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    receiptError.value = '';
    form.vehicle_id = filterForm.vehicle_id !== '' ? Number(filterForm.vehicle_id) : (props.options.vehicles[0]?.value ?? '');
    form.occurred_on = todayIso();
    formOpen.value = true;
};

const openEdit = (expense) => {
    editing.value = expense;
    form.clearErrors();
    receiptError.value = '';
    form.vehicle_id = expense.vehicle_id;
    form.expense_category_id = expense.expense_category_id;
    form.description = expense.description;
    form.amount_cents = expense.amount_cents;
    form.occurred_on = expense.occurred_on;
    form.odometer = expense.odometer ?? '';
    form.vendor = expense.vendor ?? '';
    form.payment_method = expense.payment_method ?? '';
    form.notes = expense.notes ?? '';
    form.receipt = null;
    form.remove_receipt = false;
    formOpen.value = true;
};

// Link de edição vindo de outra tela (?edit=ID): abre o formulário existente, sem duplicá-lo.
onMounted(() => {
    if (props.editing) {
        openEdit(props.editing);
    }
});

const closeForm = () => {
    formOpen.value = false;
};

const onReceiptChange = (event) => {
    const file = event.target.files?.[0] ?? null;

    receiptError.value = '';
    form.receipt = null;

    if (file === null) {
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp', 'application/pdf'].includes(file.type)) {
        receiptError.value = 'Envie um PDF ou imagem JPG, PNG ou WebP.';
        event.target.value = '';

        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        receiptError.value = 'O comprovante deve ter no máximo 5 MB.';
        event.target.value = '';

        return;
    }

    form.receipt = file;
    form.remove_receipt = false;
};

const submit = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: closeForm };

    if (editing.value) {
        // PUT não aceita multipart no PHP: o `_method` permite enviar o comprovante na edição.
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/expenses/${editing.value.id}`, options);
    } else {
        form.transform((data) => data).post('/expenses', options);
    }
};

const confirmDelete = () => {
    deleting.value = true;

    router.delete(`/expenses/${expenseToDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            expenseToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Despesas" />

    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-100">Despesas</h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">
                    Registre gastos atuais ou de anos anteriores. Os períodos e totais usam a data em que a despesa aconteceu.
                </p>
            </div>

            <BaseButton :disabled="options.vehicles.length === 0" @click="openCreate">Nova despesa</BaseButton>
        </header>

        <p
            v-if="options.vehicles.length === 0"
            class="rounded-2xl border border-slate-800 bg-slate-900 px-5 py-4 text-sm text-slate-300"
        >
            Cadastre um veículo antes de registrar despesas.
        </p>

        <form class="rounded-2xl border border-slate-800 bg-slate-900 p-5" @submit.prevent="applyFilters">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <BaseSelect v-model="filterForm.vehicle_id" label="Veículo" :options="vehicleFilterOptions" />
                <BaseSelect v-model="filterForm.expense_category_id" label="Categoria" :options="categoryFilterOptions" />
                <BaseDateInput v-model="filterForm.from" label="De" />
                <BaseDateInput v-model="filterForm.to" label="Até" />
                <BaseInput v-model="filterForm.search" label="Busca" placeholder="Descrição ou fornecedor" />
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <BaseButton type="submit" :loading="loading">Filtrar</BaseButton>
                <BaseButton v-if="hasFilters" variant="secondary" :disabled="loading" @click="clearFilters">
                    Limpar filtros
                </BaseButton>
            </div>
        </form>

        <p
            v-if="incompleteHistoryVehicles.length > 0"
            role="status"
            class="rounded-2xl border border-indigo-800 bg-indigo-950 px-5 py-4 text-sm leading-relaxed text-indigo-200"
        >
            Histórico incompleto: o período consultado começa antes do início do acompanhamento de
            {{ incompleteHistoryVehicles.map((vehicle) => `${vehicle.label} (${formatDate(vehicle.tracking_start_date)})`).join(', ') }}.
            Gastos anteriores a essa data podem não estar completos.
        </p>

        <LoadingState v-if="loading" label="Carregando despesas…" />

        <template v-else>
            <EmptyState
                v-if="expenses.data.length === 0"
                badge="Nenhum resultado"
                heading-level="h2"
                :title="hasFilters ? 'Nenhuma despesa encontrada' : 'Nenhuma despesa registrada'"
                :description="
                    hasFilters
                        ? 'Nenhuma despesa corresponde aos filtros informados.'
                        : 'As despesas registradas aparecem aqui, da mais recente para a mais antiga.'
                "
            >
                <BaseButton v-if="hasFilters" variant="secondary" @click="clearFilters">Limpar filtros</BaseButton>
            </EmptyState>

            <template v-else>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="text-sm text-slate-400">
                        Mostrando {{ expenses.from }}–{{ expenses.to }} de {{ expenses.total }}
                        {{ expenses.total === 1 ? 'despesa' : 'despesas' }}
                    </p>
                    <p class="text-sm text-slate-300">
                        Total do filtro atual:
                        <strong class="font-mono text-lg text-slate-100">{{ formatCurrency(total_cents) }}</strong>
                    </p>
                </div>

                <ul class="divide-y divide-slate-800 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                    <li v-for="expense in expenses.data" :key="expense.id" class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-100">{{ expense.description }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                    <span>{{ formatDate(expense.occurred_on) }}</span>
                                    <span>·</span>
                                    <span class="inline-flex items-center gap-1.5">
                                        <span
                                            class="h-2.5 w-2.5 rounded-full"
                                            :style="{ backgroundColor: expense.category?.color ?? 'transparent' }"
                                            aria-hidden="true"
                                        ></span>
                                        {{ expense.category?.name }}
                                    </span>
                                    <span>·</span>
                                    <span>{{ expense.vehicle?.name }}</span>
                                    <template v-if="expense.vendor"><span>·</span><span>{{ expense.vendor }}</span></template>
                                    <template v-if="expense.payment_method">
                                        <span>·</span><span>{{ paymentLabel(expense.payment_method) }}</span>
                                    </template>
                                </p>

                                <p class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        v-if="expense.recurring_expense_id"
                                        class="rounded-full bg-indigo-950 px-2.5 py-0.5 text-xs font-medium text-indigo-200"
                                    >
                                        Recorrente
                                    </span>
                                    <span
                                        v-if="beforeTracking(expense)"
                                        class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-400"
                                    >
                                        Antes do acompanhamento ({{ formatDate(trackingStart(expense.vehicle_id)) }})
                                    </span>
                                    <a
                                        v-if="expense.has_receipt"
                                        :href="`/expenses/${expense.id}/receipt`"
                                        target="_blank"
                                        rel="noopener"
                                        class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-300 hover:underline"
                                    >
                                        Ver comprovante
                                    </a>
                                    <a
                                        v-if="expense.has_receipt"
                                        :href="`/expenses/${expense.id}/receipt?download=1`"
                                        class="rounded-full bg-slate-800 px-2.5 py-0.5 text-xs text-slate-300 hover:underline"
                                    >
                                        Baixar
                                    </a>
                                </p>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <p class="font-mono text-lg font-bold text-slate-100">{{ formatCurrency(expense.amount_cents) }}</p>
                                <div class="flex gap-1">
                                    <BaseButton variant="secondary" size="sm" @click="openEdit(expense)">Editar</BaseButton>
                                    <BaseButton variant="ghost" size="sm" @click="expenseToDelete = expense">Excluir</BaseButton>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>

                <BasePagination :links="expenses.links ?? []" label="Paginação de despesas" class="pt-2" />
            </template>
        </template>

        <BaseModal :show="formOpen" size="lg" labelled-by="expense-form-title" @close="closeForm">
            <form class="space-y-4" novalidate @submit.prevent="submit">
                <h2 id="expense-form-title" class="text-lg font-semibold text-slate-100">
                    {{ editing ? 'Editar despesa' : 'Nova despesa' }}
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <BaseSelect
                        v-model="form.vehicle_id"
                        label="Veículo"
                        placeholder="Selecione o veículo"
                        :options="options.vehicles"
                        required
                        :error="form.errors.vehicle_id"
                    />
                    <BaseSelect
                        v-model="form.expense_category_id"
                        label="Categoria"
                        placeholder="Selecione a categoria"
                        :options="formCategoryOptions"
                        required
                        :error="form.errors.expense_category_id"
                    />
                    <div class="sm:col-span-2">
                        <BaseInput v-model="form.description" label="Descrição" required :error="form.errors.description" />
                    </div>
                    <BaseCurrencyInput v-model="form.amount_cents" label="Valor" required :error="form.errors.amount_cents" />
                    <BaseDateInput
                        v-model="form.occurred_on"
                        label="Data da despesa"
                        hint="Aceita datas passadas, para registrar gastos antigos."
                        required
                        :error="form.errors.occurred_on"
                    />
                    <BaseInput
                        :model-value="form.odometer"
                        label="Odômetro (km)"
                        :error="form.errors.odometer"
                        @update:model-value="form.odometer = String($event).replace(/\D/g, '')"
                    />
                    <BaseInput v-model="form.vendor" label="Fornecedor" :error="form.errors.vendor" />
                    <BaseSelect
                        v-model="form.payment_method"
                        label="Forma de pagamento"
                        placeholder="Não informada"
                        :options="paymentOptions"
                        :error="form.errors.payment_method"
                    />

                    <div>
                        <label for="expense-receipt" class="block text-sm font-medium text-slate-300">Comprovante</label>
                        <input
                            id="expense-receipt"
                            ref="receiptInput"
                            type="file"
                            accept="application/pdf,image/jpeg,image/png,image/webp"
                            class="mt-1 block w-full cursor-pointer rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-300 file:mr-3 file:rounded-md file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-sm file:text-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            @change="onReceiptChange"
                        >
                        <p class="mt-1 text-xs text-slate-500">PDF, JPG, PNG ou WebP, até 5 MB.</p>
                        <p v-if="receiptError || form.errors.receipt" class="mt-1 text-xs text-rose-400" role="alert">
                            {{ receiptError || form.errors.receipt }}
                        </p>
                        <label v-if="editing?.has_receipt && !form.receipt" class="mt-2 flex items-center gap-2 text-sm text-slate-300">
                            <input v-model="form.remove_receipt" type="checkbox" class="h-4 w-4 rounded border-slate-600 text-indigo-500">
                            Remover comprovante atual
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <BaseTextarea v-model="form.notes" label="Observações" :rows="3" :error="form.errors.notes" />
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <BaseButton variant="secondary" :disabled="form.processing" @click="closeForm">Cancelar</BaseButton>
                    <BaseButton type="submit" :loading="form.processing">Salvar</BaseButton>
                </div>
            </form>
        </BaseModal>

        <ConfirmDialog
            :show="expenseToDelete !== null"
            title="Excluir despesa"
            :message="`A despesa ${expenseToDelete?.description ?? ''} (${formatCurrency(expenseToDelete?.amount_cents ?? 0)}) será excluída, junto com o comprovante. Esta ação não pode ser desfeita.`"
            confirm-label="Excluir"
            :processing="deleting"
            @confirm="confirmDelete"
            @cancel="expenseToDelete = null"
        />
    </div>
</template>
