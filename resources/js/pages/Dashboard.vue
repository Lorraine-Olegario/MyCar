<script setup>
import { Head, Link } from '@inertiajs/vue3';

const sections = [
    {
        label: 'Veículos',
        href: '/vehicles',
        description: 'Cadastro, fotos e histórico dos veículos.',
    },
    {
        label: 'Despesas',
        href: '/expenses',
        description: 'Custos avulsos, recorrentes e manutenções.',
    },
    {
        label: 'Abastecimentos',
        href: '/fuelings',
        description: 'Consumo e custo por quilômetro rodado.',
    },
    {
        label: 'Relatórios',
        href: '/reports',
        description: 'Indicadores, gráficos e exportação.',
    },
    {
        label: 'Categorias',
        href: '/categories',
        description: 'Organização das despesas.',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-8">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h1 class="text-2xl font-semibold tracking-tight text-white">MyCar</h1>
            <p class="mt-2 max-w-prose text-sm leading-relaxed text-slate-400">
                Aplicação pessoal para acompanhar veículos, despesas, abastecimentos e
                indicadores financeiros. Os valores são persistidos em centavos, as datas
                no padrão dd/mm/aaaa e todo o controle é local.
            </p>
        </section>

        <section>
            <h2 class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
                Seções
            </h2>

            <ul class="mt-4 grid gap-4 sm:grid-cols-2">
                <li v-for="section in sections" :key="section.href">
                    <Link
                        :href="section.href"
                        class="block h-full rounded-2xl border border-slate-800 bg-slate-900/40 p-5 transition hover:border-indigo-500/60 hover:bg-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                    >
                        <span class="text-base font-medium text-white">{{ section.label }}</span>
                        <span class="mt-1 block text-sm text-slate-400">
                            {{ section.description }}
                        </span>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
