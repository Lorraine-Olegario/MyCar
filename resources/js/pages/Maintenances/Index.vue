<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
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
import { formatCurrency, formatDate, formatKm } from '../../utils/format';

const props = defineProps({
    maintenances: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({ vehicles: [], categories: [], types: [], payment_methods: [] }) },
});

const filterForm = reactive({ vehicle_id: props.filters.vehicle_id ?? '' });
const formOpen = ref(false);
const editing = ref(null);
const toDelete = ref(null);
const deleting = ref(false);

const vehicleFilterOptions = computed(() => [{ value: '', label: 'Todos os veículos' }, ...props.options.vehicles]);
const typeLabel = (value) => props.options.types.find((type) => type.value === value)?.label ?? value;

const categoryOptions = computed(() =>
    props.options.categories
        .filter((category) => category.active || category.value === editing.value?.expense.expense_category_id)
        .map((category) => ({ value: category.value, label: category.active ? category.label : `${category.label} (inativa)` })),
);

const dueStyle = {
    overdue: 'bg-rose-500/15 text-rose-300',
    due_soon: 'bg-amber-500/15 text-amber-600',
    ok: 'bg-emerald-500/15 text-emerald-300',
};

const dueText = (due) => {
    const parts = [];

    if (due.days_remaining !== null) {
        parts.push(due.days_remaining < 0 ? `${Math.abs(due.days_remaining)} dias de atraso` : `${due.days_remaining} dias`);
    }

    if (due.km_remaining !== null) {
        parts.push(due.km_remaining < 0 ? `${Math.abs(due.km_remaining)} km de atraso` : `${due.km_remaining} km`);
    }

    const label = { overdue: 'Vencida', due_soon: 'Vence em breve', ok: 'Em dia' }[due.status];

    return `${label}: ${parts.join(' · ')}`;
};

const applyFilter = () => {
    router.get('/maintenances', filterForm.vehicle_id === '' ? {} : { vehicle_id: filterForm.vehicle_id }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const form = useForm({
    vehicle_id: '',
    expense_category_id: '',
    description: '',
    amount_cents: null,
    occurred_on: '',
    type: 'preventive',
    odometer: '',
    next_due_on: '',
    next_due_odometer: '',
    vendor: '',
    payment_method: '',
    notes: '',
});

const todayIso = () => {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
};

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.vehicle_id = filterForm.vehicle_id !== '' ? Number(filterForm.vehicle_id) : (props.options.vehicles[0]?.value ?? '');
    form.occurred_on = todayIso();
    formOpen.value = true;
};

const openEdit = (item) => {
    editing.value = item;
    form.clearErrors();
    form.vehicle_id = item.vehicle_id;
    form.expense_category_id = item.expense.expense_category_id;
    form.description = item.expense.description;
    form.amount_cents = item.expense.amount_cents;
    form.occurred_on = item.expense.occurred_on;
    form.type = item.type;
    form.odometer = item.odometer ?? '';
    form.next_due_on = item.next_due_on ?? '';
    form.next_due_odometer = item.next_due_odometer ?? '';
    form.vendor = item.expense.vendor ?? '';
    form.payment_method = item.expense.payment_method ?? '';
    form.notes = item.notes ?? '';
    formOpen.value = true;
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (formOpen.value = false) };

    if (editing.value) {
        form.put(`/maintenances/${editing.value.id}`, options);
    } else {
        form.post('/maintenances', options);
    }
};

const confirmDelete = () => {
    deleting.value = true;

    router.delete(`/maintenances/${toDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            toDelete.value = null;
        },
    });
};

const digits = (field, value) => {
    form[field] = String(value ?? '').replace(/\D/g, '');
};
</script>

<template>
    <Head title="Manutenções" />

    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-100">Manutenções</h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">
                    Cada manutenção gera uma despesa. Avisos de próxima troca são só lembretes e não entram nos totais pagos.
                </p>
            </div>

            <BaseButton :disabled="options.vehicles.length === 0" @click="openCreate">Nova manutenção</BaseButton>
        </header>

        <div class="max-w-sm">
            <BaseSelect
                v-model="filterForm.vehicle_id"
                label="Veículo"
                :options="vehicleFilterOptions"
                @update:model-value="(value) => { filterForm.vehicle_id = value; applyFilter(); }"
            />
        </div>

        <EmptyState
            v-if="maintenances.data.length === 0"
            badge="Nenhuma manutenção"
            heading-level="h2"
            title="Nenhuma manutenção registrada"
            description="Registre trocas de óleo, pneus, freios e outros serviços para acompanhar o histórico e a próxima troca."
        />

        <template v-else>
            <ul class="divide-y divide-slate-800 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                <li v-for="item in maintenances.data" :key="item.id" class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-100">
                                {{ item.expense.description }}
                                <span class="ml-2 rounded-full bg-slate-800 px-2 py-0.5 text-xs font-medium text-slate-300">{{ typeLabel(item.type) }}</span>
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ formatDate(item.expense.occurred_on) }} · {{ item.vehicle_name }} · {{ item.category?.name }}
                                <template v-if="item.odometer != null"> · {{ formatKm(item.odometer) }}</template>
                                <template v-if="item.expense.vendor"> · {{ item.expense.vendor }}</template>
                            </p>
                            <p v-if="item.next_due_on || item.next_due_odometer != null" class="mt-2 text-xs text-slate-400">
                                Próxima troca:
                                <template v-if="item.next_due_on">{{ formatDate(item.next_due_on) }}</template>
                                <template v-if="item.next_due_on && item.next_due_odometer != null"> ou </template>
                                <template v-if="item.next_due_odometer != null">{{ formatKm(item.next_due_odometer) }}</template>
                            </p>
                            <p v-if="item.due.status !== 'none'" class="mt-1">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="dueStyle[item.due.status]" data-testid="due-badge">
                                    {{ dueText(item.due) }}
                                </span>
                            </p>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <p class="font-mono text-lg font-bold text-slate-100">{{ formatCurrency(item.expense.amount_cents) }}</p>
                            <div class="flex gap-1">
                                <BaseButton variant="secondary" size="sm" @click="openEdit(item)">Editar</BaseButton>
                                <BaseButton variant="ghost" size="sm" @click="toDelete = item">Excluir</BaseButton>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>

            <BasePagination :links="maintenances.links ?? []" label="Paginação de manutenções" class="pt-2" />
        </template>

        <BaseModal :show="formOpen" size="lg" labelled-by="maintenance-form-title" @close="formOpen = false">
            <form class="space-y-4" novalidate @submit.prevent="submit">
                <h2 id="maintenance-form-title" class="text-lg font-semibold text-slate-100">
                    {{ editing ? 'Editar manutenção' : 'Nova manutenção' }}
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <BaseSelect v-model="form.vehicle_id" label="Veículo" :options="options.vehicles" required :error="form.errors.vehicle_id" />
                    <BaseSelect v-model="form.type" label="Tipo" :options="options.types" required :error="form.errors.type" />
                    <BaseSelect v-model="form.expense_category_id" label="Categoria" placeholder="Selecione a categoria" :options="categoryOptions" required :error="form.errors.expense_category_id" />
                    <BaseInput v-model="form.description" label="Descrição" required :error="form.errors.description" />
                    <BaseCurrencyInput v-model="form.amount_cents" label="Custo" required :error="form.errors.amount_cents" />
                    <BaseDateInput v-model="form.occurred_on" label="Data" required :error="form.errors.occurred_on" />
                    <BaseInput :model-value="form.odometer" label="Odômetro (km)" :error="form.errors.odometer" @update:model-value="digits('odometer', $event)" />
                    <BaseInput v-model="form.vendor" label="Oficina / fornecedor" :error="form.errors.vendor" />
                    <BaseDateInput v-model="form.next_due_on" label="Próxima troca (data)" :error="form.errors.next_due_on" />
                    <BaseInput :model-value="form.next_due_odometer" label="Próxima troca (km)" :error="form.errors.next_due_odometer" @update:model-value="digits('next_due_odometer', $event)" />
                    <BaseSelect v-model="form.payment_method" label="Forma de pagamento" placeholder="Não informada" :options="options.payment_methods" :error="form.errors.payment_method" />
                    <div class="sm:col-span-2">
                        <BaseTextarea v-model="form.notes" label="Observações" :rows="2" :error="form.errors.notes" />
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <BaseButton variant="secondary" :disabled="form.processing" @click="formOpen = false">Cancelar</BaseButton>
                    <BaseButton type="submit" :loading="form.processing">Salvar</BaseButton>
                </div>
            </form>
        </BaseModal>

        <ConfirmDialog
            :show="toDelete !== null"
            title="Excluir manutenção"
            :message="`A manutenção e a despesa vinculada (${formatCurrency(toDelete?.expense.amount_cents ?? 0)}) serão excluídas. Esta ação não pode ser desfeita.`"
            confirm-label="Excluir"
            :processing="deleting"
            @confirm="confirmDelete"
            @cancel="toDelete = null"
        />
    </div>
</template>
