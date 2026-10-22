<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BasePagination from '../../components/ui/BasePagination.vue';
import BaseSelect from '../../components/ui/BaseSelect.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import LoadingState from '../../components/ui/LoadingState.vue';
import { formatDate, formatKm } from '../../utils/format';
import { optionLabel } from '../../utils/options';

const props = defineProps({
    vehicles: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    counts: {
        type: Object,
        default: () => ({ active: 0, sold: 0, archived: 0 }),
    },
    options: {
        type: Object,
        default: () => ({ statuses: [], categories: [], fuels: [] }),
    },
});

const form = reactive({
    search: props.filters.search ?? '',
    view: props.filters.view ?? '',
    category: props.filters.category ?? '',
});

const views = computed(() => [
    { value: '', label: 'Ativos e vendidos', count: props.counts.active + props.counts.sold },
    { value: 'active', label: 'Ativos', count: props.counts.active },
    { value: 'sold', label: 'Vendidos', count: props.counts.sold },
    { value: 'archived', label: 'Arquivados', count: props.counts.archived },
]);

const currentView = computed(() => views.value.find((view) => view.value === form.view));

const viewDescription = {
    '': 'Exibindo veículos ativos e vendidos. Arquivados ficam fora desta lista.',
    active: 'Exibindo somente veículos que ainda são seus.',
    sold: 'Exibindo o histórico de veículos vendidos, com o período em que foram seus.',
    archived: 'Exibindo somente veículos arquivados. Restaure para voltar à lista padrão.',
};

const loading = ref(false);
const photoFailed = reactive({});
const vehicleToArchive = ref(null);
const vehicleToRestore = ref(null);
const restoring = ref(false);
const archiving = ref(false);

const categoryOptions = computed(() => [
    { value: '', label: 'Todas as categorias' },
    ...props.options.categories,
]);

const hasFilters = computed(
    () =>
        form.search.trim() !== '' ||
        form.view !== '' ||
        form.category !== '',
);

const statusClass = {
    active: 'bg-emerald-500/15 text-emerald-300',
    sold: 'bg-indigo-500/15 text-indigo-300',
};

const query = () => {
    const params = {};

    if (form.search.trim() !== '') {
        params.search = form.search.trim();
    }

    if (form.view !== '') {
        params.view = form.view;
    }

    if (form.category !== '') {
        params.category = form.category;
    }

    return params;
};

const applyFilters = () => {
    router.get('/vehicles', query(), {
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

const selectView = (value) => {
    form.view = value;
    applyFilters();
};

const onCategoryChange = (value) => {
    form.category = value;
    applyFilters();
};

const clearFilters = () => {
    form.search = '';
    form.view = '';
    form.category = '';
    applyFilters();
};

const confirmArchive = () => {
    if (vehicleToArchive.value === null) {
        return;
    }

    archiving.value = true;

    router.delete(`/vehicles/${vehicleToArchive.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            archiving.value = false;
            vehicleToArchive.value = null;
        },
    });
};

const confirmRestore = () => {
    if (vehicleToRestore.value === null) {
        return;
    }

    restoring.value = true;

    router.patch(`/vehicles/${vehicleToRestore.value.id}/restore`, {}, {
        preserveScroll: true,
        onFinish: () => {
            restoring.value = false;
            vehicleToRestore.value = null;
        },
    });
};
</script>

<template>
    <Head title="Veículos" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-100">Veículos</h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">
                    Cadastro, fotos e histórico de veículos ativos, vendidos e arquivados.
                </p>
            </div>

            <Link
                href="/vehicles/create"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
            >
                Novo veículo
            </Link>
        </div>

        <nav aria-label="Filtro de histórico de veículos">
            <ul class="flex flex-wrap gap-2">
                <li v-for="view in views" :key="view.value">
                    <button
                        type="button"
                        :aria-pressed="form.view === view.value"
                        class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        :class="
                            form.view === view.value
                                ? 'border-indigo-500 bg-indigo-500 text-white'
                                : 'border-slate-700 bg-slate-900 text-slate-300 hover:bg-slate-800'
                        "
                        @click="selectView(view.value)"
                    >
                        {{ view.label }}
                        <span
                            class="rounded-full px-2 py-0.5 text-xs"
                            :class="form.view === view.value ? 'bg-white/20' : 'bg-slate-800 text-slate-400'"
                        >
                            {{ view.count }}
                        </span>
                    </button>
                </li>
            </ul>
            <p class="mt-3 text-sm text-slate-500" role="status">{{ viewDescription[form.view] }}</p>
        </nav>

        <form
            class="rounded-2xl border border-slate-800 bg-slate-900/40 p-5"
            @submit.prevent="applyFilters"
        >
            <div class="grid gap-4 md:grid-cols-2">
                <BaseInput
                    v-model="form.search"
                    label="Busca"
                    hint="Nome, marca, modelo ou placa"
                    placeholder="Ex.: Onix"
                />

                <BaseSelect
                    :model-value="form.category"
                    label="Categoria"
                    :options="categoryOptions"
                    @update:model-value="onCategoryChange"
                />

            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <BaseButton type="submit" :loading="loading">Filtrar</BaseButton>

                <BaseButton
                    v-if="hasFilters"
                    variant="secondary"
                    :disabled="loading"
                    @click="clearFilters"
                >
                    Limpar filtros
                </BaseButton>
            </div>
        </form>

        <LoadingState v-if="loading" label="Carregando veículos…" />

        <template v-else>
            <EmptyState
                v-if="vehicles.data.length === 0"
                badge="Nenhum resultado"
                heading-level="h2"
                :title="hasFilters ? 'Nenhum veículo encontrado' : 'Nenhum veículo cadastrado'"
                :description="
                    hasFilters
                        ? 'Nenhum veículo corresponde aos filtros informados. Ajuste a busca ou limpe os filtros para ver todos.'
                        : 'Os veículos que você cadastrar aparecem aqui, com foto, dados de aquisição e status.'
                "
            >
                <BaseButton v-if="hasFilters" variant="secondary" @click="clearFilters">
                    Limpar filtros
                </BaseButton>

                <Link
                    v-else
                    href="/vehicles/create"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950"
                >
                    Cadastrar veículo
                </Link>
            </EmptyState>

            <template v-else>
                <p class="text-sm text-slate-400">
                    Mostrando {{ vehicles.from }}–{{ vehicles.to }} de {{ vehicles.total }}
                    {{ vehicles.total === 1 ? 'veículo' : 'veículos' }}
                    <span v-if="currentView">· {{ currentView.label.toLowerCase() }}</span>
                </p>

                <ul class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <li v-for="vehicle in vehicles.data" :key="vehicle.id">
                        <article
                            class="flex h-full flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40 transition hover:border-indigo-500/50"
                        >
                            <div class="aspect-[16/10] w-full overflow-hidden bg-slate-900">
                                <img
                                    v-if="vehicle.photo_url && !photoFailed[vehicle.id]"
                                    :src="vehicle.photo_url"
                                    :alt="`Foto de ${vehicle.full_name}`"
                                    loading="lazy"
                                    class="h-full w-full object-cover"
                                    @error="photoFailed[vehicle.id] = true"
                                >
                                <p
                                    v-else
                                    class="flex h-full w-full items-center justify-center text-xs font-medium uppercase tracking-widest text-slate-600"
                                >
                                    Sem foto
                                </p>
                            </div>

                            <div class="flex flex-1 flex-col gap-4 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <Link
                                            v-if="!vehicle.archived"
                                            :href="`/vehicles/${vehicle.id}`"
                                            class="block truncate text-base font-medium text-slate-100 transition hover:text-indigo-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                        >
                                            {{ vehicle.full_name }}
                                        </Link>
                                        <p v-else class="truncate text-base font-medium text-slate-300">
                                            {{ vehicle.full_name }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ optionLabel(options.categories, vehicle.category) }} ·
                                            {{ vehicle.manufacture_year }}/{{ vehicle.model_year }}
                                        </p>
                                    </div>

                                    <span
                                        class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusClass[vehicle.status] ?? 'bg-slate-800 text-slate-300'"
                                    >
                                        {{ optionLabel(options.statuses, vehicle.status) }}
                                    </span>
                                </div>

                                <p
                                    v-if="vehicle.status === 'sold'"
                                    class="rounded-lg bg-indigo-950 px-3 py-2 text-xs leading-relaxed text-indigo-200"
                                >
                                    Vendido<template v-if="vehicle.sale_date"> em {{ formatDate(vehicle.sale_date) }}</template>.
                                    <template v-if="vehicle.ownership_label">
                                        Ficou com você por {{ vehicle.ownership_label }}.
                                    </template>
                                </p>

                                <dl class="grid grid-cols-2 gap-3">
                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-slate-500">
                                            Placa
                                        </dt>
                                        <dd class="mt-0.5 text-sm text-slate-200">
                                            {{ vehicle.formatted_plate ?? 'Sem placa' }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="text-xs uppercase tracking-wide text-slate-500">
                                            Odômetro
                                        </dt>
                                        <dd class="mt-0.5 text-sm text-slate-200">
                                            {{ formatKm(vehicle.odometer) }}
                                        </dd>
                                    </div>
                                </dl>

                                <div class="mt-auto flex flex-wrap items-center gap-3">
                                    <span
                                        v-if="vehicle.archived"
                                        class="rounded-full bg-slate-800 px-2.5 py-1 text-xs text-slate-400"
                                    >
                                        Arquivado
                                    </span>

                                    <Link
                                        v-if="!vehicle.archived"
                                        :href="`/vehicles/${vehicle.id}`"
                                        class="text-sm font-medium text-indigo-300 transition hover:text-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                                    >
                                        Ver detalhes
                                    </Link>

                                    <BaseButton
                                        v-else
                                        variant="secondary"
                                        size="sm"
                                        @click="vehicleToRestore = vehicle"
                                    >
                                        Restaurar
                                    </BaseButton>

                                    <BaseButton
                                        v-if="!vehicle.archived"
                                        variant="ghost"
                                        size="sm"
                                        class="ml-auto"
                                        @click="vehicleToArchive = vehicle"
                                    >
                                        Arquivar
                                    </BaseButton>
                                </div>
                            </div>
                        </article>
                    </li>
                </ul>

                <BasePagination
                    :links="vehicles.links ?? []"
                    label="Paginação de veículos"
                    class="pt-2"
                />
            </template>
        </template>

        <ConfirmDialog
            :show="vehicleToRestore !== null"
            title="Restaurar veículo"
            :message="`O veículo ${vehicleToRestore?.full_name ?? ''} voltará para a lista de ativos e vendidos, com todo o histórico preservado.`"
            confirm-label="Restaurar"
            :processing="restoring"
            @confirm="confirmRestore"
            @cancel="vehicleToRestore = null"
        />

        <ConfirmDialog
            :show="vehicleToArchive !== null"
            title="Arquivar veículo"
            :message="`O veículo ${vehicleToArchive?.full_name ?? ''} será arquivado. Nada é apagado: despesas e histórico continuam guardados e o veículo pode ser restaurado depois.`"
            confirm-label="Arquivar"
            :processing="archiving"
            @confirm="confirmArchive"
            @cancel="vehicleToArchive = null"
        />
    </div>
</template>
