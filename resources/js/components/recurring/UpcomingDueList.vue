<script setup>
import { formatCurrency, formatDate, formatKm } from '../../utils/format';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    title: {
        type: String,
        default: 'Próximos vencimentos',
    },
    showVehicle: {
        type: Boolean,
        default: true,
    },
});

const dueText = (item) => {
    if (item.kind === 'maintenance' && item.days_until_due == null) {
        const km = item.km_remaining ?? 0;

        return km < 0 ? `Vencida há ${formatKm(Math.abs(km))}` : `Faltam ${formatKm(km)}`;
    }

    if (item.is_overdue) {
        return `Vencida há ${Math.abs(item.days_until_due)} ${Math.abs(item.days_until_due) === 1 ? 'dia' : 'dias'}`;
    }

    if (item.days_until_due === 0) {
        return 'Vence hoje';
    }

    return `Vence em ${item.days_until_due} ${item.days_until_due === 1 ? 'dia' : 'dias'}`;
};
</script>

<template>
    <section class="rounded-2xl border border-slate-800 bg-slate-900" aria-labelledby="upcoming-due-title">
        <h2 id="upcoming-due-title" class="px-5 pt-5 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
            {{ title }}
        </h2>

        <p v-if="items.length === 0" class="px-5 py-5 text-sm text-slate-500">
            Nenhum vencimento previsto nos próximos meses.
        </p>

        <ul v-else class="mt-3 divide-y divide-slate-800">
            <li v-for="item in items" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 px-5 py-3">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-100">{{ item.description }}</p>
                    <p class="text-xs text-slate-500">
                        <span v-if="showVehicle && item.vehicle_name">{{ item.vehicle_name }} · </span>
                        <template v-if="item.due_date">{{ formatDate(item.due_date) }}</template>
                        <template v-else-if="item.due_odometer != null">Aos {{ formatKm(item.due_odometer) }}</template>
                        <span v-if="item.kind === 'maintenance'"> · manutenção</span>
                    </p>
                </div>

                <div class="text-right">
                    <p v-if="item.expected_amount_cents != null" class="font-mono text-sm font-bold text-slate-100">
                        {{ formatCurrency(item.expected_amount_cents) }}
                        <span class="font-sans text-xs font-normal text-slate-500">previsto</span>
                    </p>
                    <p class="text-xs" :class="item.is_overdue ? 'font-semibold text-rose-400' : 'text-slate-500'">
                        {{ dueText(item) }}
                    </p>
                </div>
            </li>
        </ul>
    </section>
</template>
