<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import UpcomingDueList from '../../components/recurring/UpcomingDueList.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseCurrencyInput from '../../components/ui/BaseCurrencyInput.vue';
import BaseDateInput from '../../components/ui/BaseDateInput.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseModal from '../../components/ui/BaseModal.vue';
import BaseSelect from '../../components/ui/BaseSelect.vue';
import BaseTextarea from '../../components/ui/BaseTextarea.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import { formatCurrency, formatDate } from '../../utils/format';

const props = defineProps({
    recurring: { type: Array, required: true },
    occurrences: { type: [Object, Array], default: () => ({}) },
    editing_id: { type: Number, default: null },
    upcoming: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ estimated_cents: 0, paid_cents: 0, forecast_cents: 0 }) },
    options: {
        type: Object,
        default: () => ({ vehicles: [], categories: [], frequencies: [] }),
    },
});

const formOpen = ref(false);
const editing = ref(null);
const expanded = ref(null);
const paying = ref(null);
const canceling = ref(null);
const canceled = ref(false);

const statusStyle = {
    forecast: 'bg-amber-500/15 text-amber-600',
    paid: 'bg-emerald-500/15 text-emerald-300',
    canceled: 'bg-slate-800 text-slate-400 line-through',
};
const statusLabel = { forecast: 'Previsto', paid: 'Pago', canceled: 'Cancelado' };

const frequencyLabel = (value) => props.options.frequencies.find((item) => item.value === value)?.label ?? value;
const timeline = (id) => props.occurrences[id] ?? [];

const categoryOptions = computed(() =>
    props.options.categories
        .filter((category) => category.active || category.value === editing.value?.expense_category_id)
        .map((category) => ({ value: category.value, label: category.active ? category.label : `${category.label} (inativa)` })),
);

const form = useForm({
    vehicle_id: '',
    expense_category_id: '',
    description: '',
    expected_amount_cents: null,
    frequency: 'monthly',
    start_date: '',
    end_date: '',
    due_day: 10,
    active: true,
    notes: '',
});

const payForm = useForm({ paid_on: '', paid_amount_cents: null });

const todayIso = () => {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.vehicle_id = props.options.vehicles[0]?.value ?? '';
    form.start_date = todayIso();
    formOpen.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    form.clearErrors();
    form.vehicle_id = item.vehicle_id;
    form.expense_category_id = item.expense_category_id;
    form.description = item.description;
    form.expected_amount_cents = item.expected_amount_cents;
    form.frequency = item.frequency;
    form.start_date = item.start_date;
    form.end_date = item.end_date ?? '';
    form.due_day = item.due_day;
    form.active = item.active;
    form.notes = item.notes ?? '';
    formOpen.value = true;
};

// Link de edição vindo de outra tela (?edit=ID): abre o formulário existente, sem duplicá-lo.
onMounted(() => {
    const item = props.recurring.find((recurring) => recurring.id === props.editing_id);

    if (item) {
        openEdit(item);
    }
});

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (formOpen.value = false) };

    if (editing.value) {
        form.put(`/recurring/${editing.value.id}`, options);
    } else {
        form.post('/recurring', options);
    }
};

const openPay = (occurrence) => {
    paying.value = occurrence;
    payForm.clearErrors();
    payForm.paid_on = todayIso();
    payForm.paid_amount_cents = occurrence.expected_amount_cents;
};

const submitPay = () => {
    payForm.post(`/recurring/occurrences/${paying.value.id}/pay`, {
        preserveScroll: true,
        onSuccess: () => (paying.value = null),
    });
};

const confirmCancel = () => {
    canceled.value = true;

    router.patch(`/recurring/occurrences/${canceling.value.id}/cancel`, {}, {
        preserveScroll: true,
        onFinish: () => {
            canceled.value = false;
            canceling.value = null;
        },
    });
};
</script>

<template>
    <Head title="Despesas recorrentes" />

    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-100">Despesas recorrentes</h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">
                    Seguro, IPVA, licenciamento e outros gastos periódicos. Previsões só viram despesa quando o
                    pagamento é confirmado.
                </p>
            </div>

            <BaseButton :disabled="options.vehicles.length === 0" @click="openCreate">Nova recorrência</BaseButton>
        </header>

        <dl class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <dt class="text-sm text-slate-300">Estimativa mensal</dt>
                <dd class="mt-2 font-mono text-2xl font-bold text-slate-100">{{ formatCurrency(summary.estimated_cents) }}</dd>
                <p class="mt-1 text-xs text-slate-500">Valores anuais, semestrais e trimestrais divididos por mês.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <dt class="text-sm text-slate-300">Pago no mês</dt>
                <dd class="mt-2 font-mono text-2xl font-bold text-emerald-300">{{ formatCurrency(summary.paid_cents) }}</dd>
                <p class="mt-1 text-xs text-slate-500">Somente pagamentos confirmados.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <dt class="text-sm text-slate-300">Previsto para vencer no mês</dt>
                <dd class="mt-2 font-mono text-2xl font-bold text-amber-600">{{ formatCurrency(summary.forecast_cents) }}</dd>
                <p class="mt-1 text-xs text-slate-500">Previsão em aberto; não conta como gasto.</p>
            </div>
        </dl>

        <UpcomingDueList :items="upcoming" />

        <EmptyState
            v-if="recurring.length === 0"
            badge="Nenhuma recorrência"
            heading-level="h2"
            title="Nenhuma despesa recorrente cadastrada"
            description="Cadastre o seguro ou o IPVA para acompanhar vencimentos e confirmar pagamentos."
        />

        <ul v-else class="space-y-4">
            <li v-for="item in recurring" :key="item.id" class="rounded-2xl border border-slate-800 bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2 font-semibold" :class="item.active ? 'text-slate-100' : 'text-slate-500'">
                            {{ item.description }}
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="item.active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-800 text-slate-400'">
                                {{ item.active ? 'Ativa' : 'Inativa' }}
                            </span>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ item.vehicle?.name }} · {{ item.category?.name }} · {{ frequencyLabel(item.frequency) }} · vence dia {{ item.due_day }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Próximo vencimento:
                            <strong class="text-slate-300">{{ item.next_due_date ? formatDate(item.next_due_date) : '—' }}</strong>
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="font-mono text-lg font-bold text-slate-100">
                            {{ formatCurrency(item.expected_amount_cents) }}
                            <span class="font-sans text-xs font-normal text-slate-500">previsto</span>
                        </p>
                        <p class="text-xs text-slate-500">≈ {{ formatCurrency(item.monthly_estimate_cents) }} por mês (estimativa)</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-slate-800 px-5 py-3">
                    <BaseButton variant="secondary" size="sm" @click="openEdit(item)">Editar</BaseButton>
                    <BaseButton
                        variant="ghost"
                        size="sm"
                        :aria-expanded="expanded === item.id"
                        @click="expanded = expanded === item.id ? null : item.id"
                    >
                        {{ expanded === item.id ? 'Ocultar ocorrências' : 'Ver ocorrências' }}
                    </BaseButton>
                </div>

                <ol v-if="expanded === item.id" class="divide-y divide-slate-800 border-t border-slate-800" aria-label="Linha do tempo de ocorrências">
                    <li v-for="occurrence in timeline(item.id)" :key="occurrence.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                        <div>
                            <p class="text-sm text-slate-100">
                                Vencimento {{ formatDate(occurrence.due_date) }}
                                <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-medium" :class="statusStyle[occurrence.status]">
                                    {{ statusLabel[occurrence.status] }}
                                </span>
                            </p>
                            <p v-if="occurrence.status === 'paid'" class="text-xs text-slate-500">
                                Pago em {{ formatDate(occurrence.paid_at) }}: {{ formatCurrency(occurrence.paid_amount_cents) }}
                            </p>
                            <p v-else class="text-xs text-slate-500">Previsto: {{ formatCurrency(occurrence.expected_amount_cents) }}</p>
                        </div>

                        <div v-if="occurrence.status === 'forecast'" class="flex gap-2">
                            <BaseButton size="sm" @click="openPay(occurrence)">Confirmar pagamento</BaseButton>
                            <BaseButton variant="ghost" size="sm" @click="canceling = occurrence">Cancelar</BaseButton>
                        </div>
                    </li>
                </ol>
            </li>
        </ul>

        <BaseModal :show="formOpen" size="lg" labelled-by="recurring-form-title" @close="formOpen = false">
            <form class="space-y-4" novalidate @submit.prevent="submit">
                <h2 id="recurring-form-title" class="text-lg font-semibold text-slate-100">
                    {{ editing ? 'Editar recorrência' : 'Nova recorrência' }}
                </h2>
                <p v-if="editing" class="text-xs text-slate-500">
                    A edição recalcula apenas as previsões futuras; ocorrências pagas ou canceladas continuam como histórico.
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <BaseSelect v-model="form.vehicle_id" label="Veículo" :options="options.vehicles" required :error="form.errors.vehicle_id" />
                    <BaseSelect
                        v-model="form.expense_category_id"
                        label="Categoria"
                        placeholder="Selecione a categoria"
                        :options="categoryOptions"
                        required
                        :error="form.errors.expense_category_id"
                    />
                    <div class="sm:col-span-2">
                        <BaseInput v-model="form.description" label="Descrição" required :error="form.errors.description" />
                    </div>
                    <BaseCurrencyInput v-model="form.expected_amount_cents" label="Valor previsto" required :error="form.errors.expected_amount_cents" />
                    <BaseSelect v-model="form.frequency" label="Frequência" :options="options.frequencies" required :error="form.errors.frequency" />
                    <BaseDateInput v-model="form.start_date" label="Início" required :error="form.errors.start_date" />
                    <BaseDateInput v-model="form.end_date" label="Fim (opcional)" :error="form.errors.end_date" />
                    <BaseInput
                        :model-value="form.due_day"
                        label="Dia de vencimento"
                        hint="De 1 a 31; em meses mais curtos vale o último dia."
                        required
                        :error="form.errors.due_day"
                        @update:model-value="form.due_day = String($event).replace(/\D/g, '')"
                    />
                    <label class="flex items-center gap-2 self-end pb-2 text-sm text-slate-300">
                        <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-slate-600 text-indigo-500">
                        Recorrência ativa
                    </label>
                    <div class="sm:col-span-2">
                        <BaseTextarea v-model="form.notes" label="Observações" :rows="3" :error="form.errors.notes" />
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <BaseButton variant="secondary" :disabled="form.processing" @click="formOpen = false">Cancelar</BaseButton>
                    <BaseButton type="submit" :loading="form.processing">Salvar</BaseButton>
                </div>
            </form>
        </BaseModal>

        <BaseModal :show="paying !== null" labelled-by="pay-title" @close="paying = null">
            <form class="space-y-4" novalidate @submit.prevent="submitPay">
                <h2 id="pay-title" class="text-lg font-semibold text-slate-100">Confirmar pagamento</h2>
                <p class="text-sm text-slate-400">
                    Vencimento {{ formatDate(paying?.due_date) }}. Será criada uma despesa real com a data e o valor informados.
                </p>
                <BaseDateInput v-model="payForm.paid_on" label="Data do pagamento" required :error="payForm.errors.paid_on" />
                <BaseCurrencyInput v-model="payForm.paid_amount_cents" label="Valor pago" required :error="payForm.errors.paid_amount_cents" />
                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <BaseButton variant="secondary" :disabled="payForm.processing" @click="paying = null">Voltar</BaseButton>
                    <BaseButton type="submit" :loading="payForm.processing">Confirmar pagamento</BaseButton>
                </div>
            </form>
        </BaseModal>

        <ConfirmDialog
            :show="canceling !== null"
            title="Cancelar previsão"
            :message="`A previsão com vencimento em ${formatDate(canceling?.due_date)} será cancelada e não poderá ser paga depois.`"
            confirm-label="Cancelar previsão"
            cancel-label="Voltar"
            :processing="canceled"
            @confirm="confirmCancel"
            @cancel="canceling = null"
        />
    </div>
</template>
