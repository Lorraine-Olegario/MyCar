<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseCurrencyInput from '../../components/ui/BaseCurrencyInput.vue';
import BaseDateInput from '../../components/ui/BaseDateInput.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseSelect from '../../components/ui/BaseSelect.vue';
import BaseTextarea from '../../components/ui/BaseTextarea.vue';

const props = defineProps({
    vehicle: {
        type: Object,
        default: null,
    },
    options: {
        type: Object,
        default: () => ({ statuses: [], categories: [], fuels: [] }),
    },
});

const isEditing = computed(() => props.vehicle !== null);

const form = useForm({
    name: props.vehicle?.name ?? '',
    brand: props.vehicle?.brand ?? '',
    model: props.vehicle?.model ?? '',
    version: props.vehicle?.version ?? '',
    manufacture_year: props.vehicle?.manufacture_year ?? '',
    model_year: props.vehicle?.model_year ?? '',
    category: props.vehicle?.category ?? '',
    plate: props.vehicle?.plate ?? '',
    color: props.vehicle?.color ?? '',
    fuel: props.vehicle?.fuel ?? '',
    odometer: props.vehicle?.odometer ?? '',
    acquisition_date: props.vehicle?.acquisition_date ?? '',
    acquisition_odometer: props.vehicle?.acquisition_odometer ?? '',
    purchase_price_cents: props.vehicle?.purchase_price_cents ?? null,
    sale_date: props.vehicle?.sale_date ?? '',
    sale_price_cents: props.vehicle?.sale_price_cents ?? null,
    tracking_start_date: props.vehicle?.tracking_start_date ?? '',
    status: props.vehicle?.status ?? 'active',
    notes: props.vehicle?.notes ?? '',
    photo: null,
    remove_photo: false,
});


// Marca e modelo vêm do catálogo FIPE (carro e moto). Os nomes escolhidos são gravados
// em `brand`/`model`; os códigos servem só para encadear a consulta. Se o catálogo
// falhar, ou a categoria não tiver catálogo, o usuário digita manualmente.
const fipeTypes = { car: 'cars', motorcycle: 'motorcycles' };
const fipeType = computed(() => fipeTypes[form.category] ?? null);
const manualEntry = ref(false);
const useCatalog = computed(() => fipeType.value !== null && !manualEntry.value);

const catalog = reactive({
    brands: { items: [], loading: false, error: '' },
    models: { items: [], loading: false, error: '' },
});
const brandCode = ref('');
const modelCode = ref('');

const toOptions = (items) => items.map((item) => ({ value: item.code, label: item.name }));

const loadCatalog = async (step, params) => {
    const state = catalog[step];

    state.loading = true;
    state.error = '';
    state.items = [];

    try {
        const query = new URLSearchParams(params).toString();
        const response = await fetch(`/fipe/${step}?${query}`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
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

    return state.items;
};

const findByName = (items, name) =>
    items.find((item) => item.label.trim().toLowerCase() === String(name ?? '').trim().toLowerCase());

const loadBrands = async () => {
    brandCode.value = '';
    modelCode.value = '';
    catalog.models.items = [];

    if (!useCatalog.value) {
        return;
    }

    const items = await loadCatalog('brands', { type: fipeType.value });
    const current = findByName(items, form.brand);

    if (current) {
        brandCode.value = current.value;
        await loadModels();
    }
};

const loadModels = async () => {
    modelCode.value = '';

    if (brandCode.value === '') {
        catalog.models.items = [];

        return;
    }

    const items = await loadCatalog('models', { type: fipeType.value, brand: brandCode.value });
    const current = findByName(items, form.model);

    if (current) {
        modelCode.value = current.value;
    }
};

const onBrandSelect = (code) => {
    brandCode.value = code;
    form.brand = catalog.brands.items.find((item) => item.value === code)?.label ?? '';
    form.model = '';
    form.version = '';
    loadModels();
};

const onModelSelect = (code) => {
    modelCode.value = code;
    form.model = catalog.models.items.find((item) => item.value === code)?.label ?? '';
};

watch(fipeType, () => {
    // Trocar a categoria invalida a marca/modelo escolhidos no catálogo anterior.
    form.brand = '';
    form.model = '';
    manualEntry.value = false;
    loadBrands();
});

watch(manualEntry, (manual) => {
    if (!manual) {
        loadBrands();
    }
});

onMounted(loadBrands);

const isSold = computed(() => form.status === 'sold');
const hasErrors = computed(() => Object.keys(form.errors).length > 0);

// O nome é derivado de marca + modelo + versão enquanto o usuário não escrever
// um nome próprio, para o cadastro não ficar com campos redundantes divergentes.
const derivedName = computed(() =>
    [form.brand, form.model, form.version]
        .map((value) => String(value ?? '').trim())
        .filter(Boolean)
        .join(' '),
);

watch(derivedName, (value, previous) => {
    if (form.name.trim() === '' || form.name === previous) {
        form.name = value;
    }
});

// O acompanhamento começa, por padrão, na aquisição do veículo.
watch(
    () => form.acquisition_date,
    (value, previous) => {
        if (form.tracking_start_date === '' || form.tracking_start_date === previous) {
            form.tracking_start_date = value;
        }
    },
);

// Voltar para "ativo" não pode manter dados de venda de um veículo que não foi vendido.
watch(
    () => form.status,
    (value) => {
        if (value !== 'sold') {
            form.sale_date = '';
            form.sale_price_cents = null;
        }
    },
);

const onPlateInput = (value) => {
    form.plate = String(value ?? '')
        .toUpperCase()
        .replace(/[^A-Z0-9-]/g, '')
        .slice(0, 8);
};

const onIntegerInput = (field, value) => {
    form[field] = String(value ?? '').replace(/\D/g, '');
};

const photoInput = ref(null);
const previewUrl = ref(null);
const photoError = ref('');

const revokePreview = () => {
    if (previewUrl.value !== null) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

onBeforeUnmount(revokePreview);

const onPhotoChange = (event) => {
    const file = event.target.files?.[0] ?? null;

    revokePreview();
    photoError.value = '';

    const reject = (message) => {
        photoError.value = message;
        form.photo = null;
        event.target.value = '';
    };

    if (file === null) {
        form.photo = null;
        return;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        reject('Envie uma imagem JPG, PNG ou WebP.');

        return;
    }

    if (file.size > 4 * 1024 * 1024) {
        reject('A imagem deve ter no máximo 4 MB.');

        return;
    }

    form.photo = file;
    form.remove_photo = false;
    previewUrl.value = URL.createObjectURL(file);
};

const currentPhotoUrl = computed(() => props.vehicle?.photo_url ?? null);

const previewSource = computed(() => previewUrl.value ?? (form.remove_photo ? null : currentPhotoUrl.value));

const photoMessage = computed(() => form.errors.photo || photoError.value);

const photoStatus = computed(() => {
    if (form.photo) {
        return currentPhotoUrl.value && !form.remove_photo
            ? 'Nova foto selecionada — a foto atual será substituída ao salvar.'
            : 'Foto selecionada.';
    }

    if (form.remove_photo) {
        return 'A foto atual será removida ao salvar.';
    }

    return currentPhotoUrl.value ? 'Foto atual do veículo.' : 'Nenhuma foto cadastrada.';
});

const cancelHref = computed(() =>
    isEditing.value ? `/vehicles/${props.vehicle.id}` : '/vehicles',
);

const submit = () => {
    if (isEditing.value) {
        // PUT não aceita multipart no PHP: com `_method` o upload da foto também funciona na edição.
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/vehicles/${props.vehicle.id}`, {
            preserveScroll: true,
            forceFormData: true,
        });

        return;
    }

    form.post('/vehicles', { preserveScroll: true });
};
</script>

<template>
    <Head :title="isEditing ? 'Editar veículo' : 'Novo veículo'" />

    <form class="space-y-6" novalidate @submit.prevent="submit">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-indigo-400">
                    Veículos
                </p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-100">
                    {{ isEditing ? 'Editar veículo' : 'Novo veículo' }}
                </h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">
                    {{
                        isEditing
                            ? 'Atualize os dados do veículo. Campos marcados com * são obrigatórios.'
                            : 'Preencha os dados do veículo. Campos marcados com * são obrigatórios.'
                    }}
                </p>
            </div>

            <Link
                :href="cancelHref"
                class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            >
                Voltar
            </Link>
        </div>

        <p
            v-if="hasErrors"
            class="rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-200"
            role="alert"
        >
            Não foi possível salvar. Corrija os campos destacados abaixo e tente novamente.
        </p>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                Identificação
            </h2>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <BaseInput
                        v-model="form.name"
                        label="Nome"
                        hint="Como o veículo é identificado no sistema. Sugerido automaticamente a partir de marca, modelo e versão."
                        required
                        :error="form.errors.name"
                    />
                </div>

                <BaseSelect
                    v-model="form.category"
                    label="Categoria"
                    placeholder="Selecione a categoria"
                    :options="options.categories"
                    required
                    :error="form.errors.category"
                />

                <div v-if="fipeType" class="flex items-end">
                    <button
                        type="button"
                        class="text-xs font-medium text-indigo-400 underline-offset-2 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        @click="manualEntry = !manualEntry"
                    >
                        {{ manualEntry ? 'Escolher marca e modelo da lista FIPE' : 'Não encontrou? Informar marca e modelo manualmente' }}
                    </button>
                </div>

                <template v-if="useCatalog">
                    <div>
                        <BaseSelect
                            :model-value="brandCode"
                            label="Marca"
                            :placeholder="catalog.brands.loading ? 'Carregando…' : 'Selecione a marca'"
                            :options="catalog.brands.items"
                            :disabled="catalog.brands.loading"
                            required
                            :error="form.errors.brand"
                            @update:model-value="onBrandSelect"
                        />
                        <p v-if="catalog.brands.error" class="mt-1 text-xs text-rose-400" role="alert">
                            {{ catalog.brands.error }} Você pode informar manualmente.
                        </p>
                    </div>

                    <div>
                        <BaseSelect
                            :model-value="modelCode"
                            label="Modelo"
                            :placeholder="catalog.models.loading ? 'Carregando…' : (brandCode ? 'Selecione o modelo' : 'Selecione a marca primeiro')"
                            :options="catalog.models.items"
                            :disabled="brandCode === '' || catalog.models.loading"
                            required
                            :error="form.errors.model"
                            @update:model-value="onModelSelect"
                        />
                        <p v-if="catalog.models.error" class="mt-1 text-xs text-rose-400" role="alert">
                            {{ catalog.models.error }} Você pode informar manualmente.
                        </p>
                    </div>
                </template>

                <template v-else>
                    <BaseInput
                        v-model="form.brand"
                        label="Marca"
                        placeholder="Ex.: Chevrolet"
                        required
                        :error="form.errors.brand"
                    />

                    <BaseInput
                        v-model="form.model"
                        label="Modelo"
                        placeholder="Ex.: Onix"
                        required
                        :error="form.errors.model"
                    />
                </template>

                <BaseInput
                    v-model="form.version"
                    label="Versão"
                    placeholder="Ex.: 1.0 LT"
                    :error="form.errors.version"
                />

                <BaseInput
                    v-model="form.color"
                    label="Cor"
                    placeholder="Ex.: Prata"
                    :error="form.errors.color"
                />

                <BaseSelect
                    v-model="form.fuel"
                    label="Combustível"
                    placeholder="Selecione o combustível"
                    :options="options.fuels"
                    required
                    :error="form.errors.fuel"
                />
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                Documentação
            </h2>

            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <BaseInput
                    :model-value="form.plate"
                    label="Placa"
                    hint="Formatos aceitos: ABC1234 ou ABC1D23 (Mercosul)."
                    placeholder="ABC1D23"
                    :error="form.errors.plate"
                    @update:model-value="onPlateInput"
                />

                <BaseInput
                    :model-value="form.manufacture_year"
                    label="Ano de fabricação"
                    type="number"
                    min="1900"
                    inputmode="numeric"
                    placeholder="2023"
                    required
                    :error="form.errors.manufacture_year"
                    @update:model-value="(value) => onIntegerInput('manufacture_year', value)"
                />

                <BaseInput
                    :model-value="form.model_year"
                    label="Ano do modelo"
                    type="number"
                    min="1900"
                    inputmode="numeric"
                    placeholder="2024"
                    hint="Deve ser igual ou posterior ao ano de fabricação."
                    required
                    :error="form.errors.model_year"
                    @update:model-value="(value) => onIntegerInput('model_year', value)"
                />

                <BaseInput
                    :model-value="form.odometer"
                    label="Odômetro (km)"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    placeholder="35000"
                    hint="Leitura atual do hodômetro."
                    required
                    :error="form.errors.odometer"
                    @update:model-value="(value) => onIntegerInput('odometer', value)"
                />
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                Aquisição
            </h2>

            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <BaseDateInput
                    v-model="form.acquisition_date"
                    label="Data de aquisição"
                    required
                    :error="form.errors.acquisition_date"
                />

                <BaseCurrencyInput
                    v-model="form.purchase_price_cents"
                    label="Valor de compra"
                    hint="Deixe vazio quando o valor não for conhecido."
                    :error="form.errors.purchase_price_cents"
                />

                <BaseInput
                    :model-value="form.acquisition_odometer"
                    label="Odômetro na aquisição (km)"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    placeholder="12000"
                    hint="Opcional; usado para calcular custo por quilômetro."
                    :error="form.errors.acquisition_odometer"
                    @update:model-value="(value) => onIntegerInput('acquisition_odometer', value)"
                />

                <BaseDateInput
                    v-model="form.tracking_start_date"
                    label="Início do acompanhamento"
                    hint="A partir de quando os gastos deste veículo passam a ser acompanhados."
                    :error="form.errors.tracking_start_date"
                />
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                Situação e venda
            </h2>

            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <BaseSelect
                    v-model="form.status"
                    label="Status"
                    :options="options.statuses"
                    required
                    :error="form.errors.status"
                />

                <template v-if="isSold">
                    <BaseDateInput
                        v-model="form.sale_date"
                        label="Data de venda"
                        hint="Não pode ser futura nem anterior à aquisição."
                        required
                        :error="form.errors.sale_date"
                    />

                    <BaseCurrencyInput
                        v-model="form.sale_price_cents"
                        label="Valor de venda"
                        required
                        :error="form.errors.sale_price_cents"
                    />
                </template>
            </div>

            <p class="mt-4 text-xs leading-relaxed text-slate-500">
                {{
                    isSold
                        ? 'Veículo vendido: data e valor de venda são obrigatórios. A aquisição pode ser bem anterior à venda.'
                        : 'Veículo em uso: a data e o valor de venda aparecem quando o status for alterado para "Vendido".'
                }}
            </p>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">Foto</h2>

            <div class="mt-4 grid gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <div>
                    <label
                        for="vehicle-photo"
                        class="block text-sm font-medium text-slate-300"
                    >
                        Arquivo da foto
                    </label>

                    <input
                        id="vehicle-photo"
                        ref="photoInput"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        :aria-invalid="photoMessage ? 'true' : undefined"
                        :aria-describedby="photoMessage ? 'vehicle-photo-error' : 'vehicle-photo-hint'"
                        class="mt-1 block w-full cursor-pointer rounded-lg border bg-slate-900 px-3 py-2 text-sm text-slate-300 file:mr-3 file:rounded-md file:border-0 file:bg-slate-800 file:px-3 file:py-1.5 file:text-sm file:text-slate-200 hover:file:bg-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        :class="photoMessage ? 'border-rose-500' : 'border-slate-700'"
                        @change="onPhotoChange"
                    >

                    <p
                        v-if="!photoMessage"
                        id="vehicle-photo-hint"
                        class="mt-1 text-xs text-slate-500"
                    >
                        JPG, PNG ou WebP de até 4 MB. A foto fica em disco privado e só é exibida
                        pelo sistema.
                    </p>

                    <p
                        v-if="photoMessage"
                        id="vehicle-photo-error"
                        class="mt-1 text-xs text-rose-400"
                        role="alert"
                    >
                        {{ photoMessage }}
                    </p>

                    <label
                        v-if="isEditing && currentPhotoUrl && !form.photo"
                        class="mt-3 flex items-center gap-2 text-sm text-slate-300"
                        for="vehicle-remove-photo"
                    >
                        <input
                            id="vehicle-remove-photo"
                            v-model="form.remove_photo"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                        >
                        Remover a foto atual
                    </label>
                </div>

                <div>
                    <p class="text-sm font-medium text-slate-300">Pré-visualização</p>

                    <div
                        class="mt-1 aspect-[4/3] w-full overflow-hidden rounded-lg border border-slate-700 bg-slate-900"
                    >
                        <img
                            v-if="previewSource"
                            :src="previewSource"
                            :alt="`Pré-visualização da foto de ${form.name || 'veículo'}`"
                            class="h-full w-full object-cover"
                        >
                        <p
                            v-else
                            class="flex h-full w-full items-center justify-center px-3 text-center text-xs font-medium uppercase tracking-widest text-slate-600"
                        >
                            Sem foto
                        </p>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">{{ photoStatus }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                Observações
            </h2>

            <div class="mt-4">
                <BaseTextarea
                    v-model="form.notes"
                    label="Observações"
                    hint="Anotações livres sobre o veículo (estado, detalhes da negociação, etc.)."
                    :rows="4"
                    :error="form.errors.notes"
                />
            </div>
        </section>

        <div
            class="sticky bottom-0 flex flex-wrap items-center justify-end gap-3 rounded-2xl border border-slate-800 bg-slate-950/90 px-6 py-4 backdrop-blur"
        >
            <Link
                :href="cancelHref"
                class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-200 transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            >
                Cancelar
            </Link>

            <BaseButton type="submit" :loading="form.processing" :disabled="form.processing">
                {{ isEditing ? 'Salvar alterações' : 'Cadastrar veículo' }}
            </BaseButton>
        </div>
    </form>
</template>
