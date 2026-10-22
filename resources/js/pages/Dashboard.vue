<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { formatCurrency, formatKm } from '../utils/format';

const props = defineProps({
    vehicle: { type: Object, default: null },
    fuelLabel: { type: String, default: null },
    monthLabel: { type: String, default: '' },
    indicators: { type: Object, default: () => ({}) },
});

const shortcuts = [
    {
        label: 'Despesas',
        href: '/expenses',
        description: 'Custos avulsos, recorrentes e manutenções',
        icon: 'M7 3h10a1 1 0 0 1 1 1v16l-2.5-1.5L13 20l-2.5-1.5L8 20l-2.5-1.5L5 20V4a1 1 0 0 1 1-1zM9 8h6M9 12h6',
    },
    {
        label: 'Abastecimentos',
        href: '/fuelings',
        description: 'Consumo e custo por quilômetro rodado',
        icon: 'M5 20V5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v15M4 20h11M5 10h9M14 8h2.5a1.5 1.5 0 0 1 1.5 1.5V16a1.5 1.5 0 0 0 3 0v-5l-2.5-2.5',
    },
    {
        label: 'Relatórios',
        href: '/reports',
        description: 'Indicadores, gráficos e exportação',
        icon: 'M4 20h16M7 20v-6M12 20V8M17 20v-9',
    },
    {
        label: 'Categorias',
        href: '/categories',
        description: 'Organização das despesas',
        icon: 'M3.5 12.5V5a1.5 1.5 0 0 1 1.5-1.5h7.5L21 12l-8.5 8.5zM8 8h.01',
    },
];

const money = (cents) => (cents === null || cents === undefined ? 'R$ —' : formatCurrency(cents));

const sinceAcquisition = () => {
    const { odometer, acquisition_odometer: start } = props.vehicle ?? {};

    return odometer != null && start != null && odometer >= start ? formatKm(odometer - start) : '—';
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto max-w-6xl space-y-10">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    {{ monthLabel }}
                </p>
                <h1 class="mt-2 text-4xl font-bold tracking-tight text-slate-100">Visão geral</h1>
            </div>

            <div class="flex flex-wrap gap-3">
                <Link
                    href="/fuelings"
                    class="rounded-xl border border-slate-700 bg-slate-900 px-4 py-2.5 text-sm font-medium text-slate-100 transition hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                >
                    Registrar abastecimento
                </Link>
                <Link
                    href="/expenses"
                    class="rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2"
                >
                    + Nova despesa
                </Link>
            </div>
        </header>

        <section
            class="grid gap-8 rounded-3xl bg-sidebar p-6 text-slate-300 sm:p-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]"
            aria-labelledby="active-vehicle-title"
        >
            <div class="flex min-h-48 items-center justify-center overflow-hidden rounded-2xl bg-sidebar-hover">
                <img
                    v-if="vehicle?.photo_url"
                    :src="vehicle.photo_url"
                    :alt="`Foto de ${vehicle.full_name}`"
                    class="h-full w-full object-cover"
                />
                <svg
                    v-else
                    class="h-24 w-24 text-slate-500"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M5 16V11l1.6-4.2A2 2 0 0 1 8.5 5.5h7a2 2 0 0 1 1.9 1.3L19 11v5M3.5 16h17M7 16v2M17 16v2M7.5 12.5h.01M16.5 12.5h.01" />
                </svg>
            </div>

            <div v-if="vehicle" class="flex flex-col justify-between gap-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-400">
                            Veículo ativo
                        </p>
                        <h2 id="active-vehicle-title" class="mt-3 text-3xl font-bold tracking-tight text-white">
                            {{ vehicle.full_name }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ [vehicle.manufacture_year && vehicle.model_year ? `${vehicle.manufacture_year}/${vehicle.model_year}` : null, fuelLabel, vehicle.color].filter(Boolean).join(' · ') }}
                        </p>
                    </div>

                    <span
                        v-if="vehicle.formatted_plate"
                        class="shrink-0 overflow-hidden rounded-lg bg-white text-center shadow"
                    >
                        <span class="block bg-blue-700 px-3 py-0.5 text-[10px] font-bold uppercase tracking-[0.25em] text-white">Brasil</span>
                        <span class="block px-4 py-1 font-mono text-xl font-bold tracking-[0.2em] text-slate-100">
                            {{ vehicle.formatted_plate }}
                        </span>
                    </span>
                </div>

                <dl class="grid grid-cols-1 divide-y divide-white/10 rounded-xl bg-sidebar-hover sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div class="px-5 py-4">
                        <dt class="text-xs text-slate-600">Odômetro atual</dt>
                        <dd class="mt-1 font-mono text-xl font-bold text-white">
                            {{ vehicle.odometer != null ? formatKm(vehicle.odometer) : '—' }}
                        </dd>
                    </div>
                    <div class="px-5 py-4">
                        <dt class="text-xs text-slate-600">Rodados desde a compra</dt>
                        <dd class="mt-1 font-mono text-xl font-bold text-white">{{ sinceAcquisition() }}</dd>
                    </div>
                    <div class="px-5 py-4">
                        <dt class="text-xs text-slate-600">Valor de compra</dt>
                        <dd class="mt-1 font-mono text-xl font-bold text-white">
                            {{ vehicle.purchase_price_cents != null ? formatCurrency(vehicle.purchase_price_cents) : '—' }}
                        </dd>
                    </div>
                </dl>

                <Link
                    :href="`/vehicles/${vehicle.id}`"
                    class="w-fit text-sm font-semibold text-amber-400 hover:text-amber-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-300"
                >
                    Ver ficha completa →
                </Link>
            </div>

            <div v-else class="flex flex-col justify-center gap-3">
                <h2 id="active-vehicle-title" class="text-2xl font-bold text-white">Nenhum veículo ativo</h2>
                <p class="text-sm text-slate-600">Cadastre seu primeiro veículo para acompanhar gastos e consumo.</p>
                <Link
                    href="/vehicles/create"
                    class="w-fit rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
                >
                    Cadastrar veículo
                </Link>
            </div>
        </section>

        <section aria-labelledby="indicators-title">
            <h2 id="indicators-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                Indicadores do mês
            </h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <h3 class="text-sm text-slate-300">Gasto total</h3>
                    <p class="mt-3 font-mono text-3xl font-bold text-slate-100">{{ money(indicators.total_spent_cents) }}</p>
                    <Link href="/expenses" class="mt-3 inline-block text-sm text-indigo-500 underline-offset-2 hover:underline">
                        Lançar primeira despesa →
                    </Link>
                </article>

                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <h3 class="text-sm text-slate-300">Custo por km</h3>
                    <p class="mt-3 font-mono text-3xl font-bold text-slate-100">{{ money(indicators.cost_per_km_cents) }}</p>
                    <p class="mt-3 text-sm text-slate-500">Precisa de despesas e quilometragem</p>
                </article>

                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <h3 class="text-sm text-slate-300">Consumo médio</h3>
                    <p class="mt-3 font-mono text-3xl font-bold text-slate-100">
                        {{ indicators.average_consumption != null ? `${indicators.average_consumption} km/l` : '— km/l' }}
                    </p>
                    <Link href="/fuelings" class="mt-3 inline-block text-sm text-indigo-500 underline-offset-2 hover:underline">
                        Registrar abastecimento →
                    </Link>
                </article>

                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <h3 class="text-sm text-slate-300">Próxima manutenção</h3>
                    <p class="mt-3 font-mono text-3xl font-bold text-slate-100">{{ indicators.next_maintenance ?? '—' }}</p>
                    <p class="mt-3 text-sm text-slate-500">Nenhuma manutenção agendada</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="shortcuts-title">
            <h2 id="shortcuts-title" class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Atalhos</h2>

            <ul class="mt-4 divide-y divide-slate-800 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                <li v-for="shortcut in shortcuts" :key="shortcut.href">
                    <Link
                        :href="shortcut.href"
                        class="flex items-center gap-4 px-5 py-4 transition hover:bg-slate-950 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-400"
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-950 text-indigo-500" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path :d="shortcut.icon" />
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-100">{{ shortcut.label }}</span>
                            <span class="block text-sm text-slate-500">{{ shortcut.description }}</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </Link>
                </li>
            </ul>
        </section>

        <p class="text-xs text-slate-500">
            Valores armazenados em centavos · datas no padrão dd/mm/aaaa · todo o controle é local.
        </p>
    </div>
</template>
