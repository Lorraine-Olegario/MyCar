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
import { formatKm } from '../../utils/format';
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
    options: {
        type: Object,
        default: () => ({ statuses: [], categories: [], fuels: [] }),
    },
});

const form = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    category: props.filters.category ?? '',
    archived: Boolean(props.filters.archived),
});

const loading = ref(false);
const photoFailed = reactive({});
const vehicleToArchive = ref(null);
const archiving = ref(false);

const statusOptions = computed(() => [
    { value: '', label: 'Todos os status' },
    ...props.options.statuses,
]);

const categoryOptions = computed(() => [
    { value: '', label: 'Todas as categorias' },
    ...props.options.categories,
]);

const hasFilters = computed(
    () =>
        form.search.trim() !== '' ||
        form.status !== '' ||
        form.category !== '' ||
        form.archived,
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

    if (form.status !== '') {
        params.status = form.status;
    }

    if (form.category !== '') {
        params.category = form.category;
    }

    if (form.archived) {
        params.archived = 1;
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

const onStatusChange = (value) => {
    form.status = value;
    applyFilters();
};

const onCategoryChange = (value) => {
    form.category = value;
    applyFilters();
};

const clearFilters = () => {
    form.search = '';
    form.status = '';
    form.category = '';
    form.archived = false;
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

const restore = (vehicle) => {
    router.patch(`/vehicles/${vehicle.id}/restore`, {}, { preserveScroll: true });
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

        <form
            class="rounded-2xl border border-slate-800 bg-slate-900/40 p-5"
            @submit.prevent="applyFilters"
        >
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <BaseInput
                    v-model="form.search"
                    label="Busca"
                    hint="Nome, marca, modelo ou placa"
                    placeholder="Ex.: Onix"
                />

                <BaseSelect
                    :model-value="form.status"
                    label="Status"
                    :options="statusOptions"
                    @update:model-value="onStatusChange"
                />

                <BaseSelect
                    :model-value="form.category"
                    label="Categoria"
                    :options="categoryOptions"
                    @update:model-value="onCategoryChange"
                />

                <div class="flex flex-col justify-end gap-3">
                    <label class="flex items-center gap-2 text-sm text-slate-300" for="vehicles-archived">
                        <input
                            id="vehicles-archived"
                            v-model="form.archived"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                            @change="applyFilters"
                        >
                        Somente arquivados
                    </label>
                </div>
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
                                        @click="restore(vehicle)"
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
