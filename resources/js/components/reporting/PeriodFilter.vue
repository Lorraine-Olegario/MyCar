<script setup>
import { PERIOD_PRESETS } from '../../utils/filterQuery';
import BaseDateInput from '../ui/BaseDateInput.vue';
import BaseSelect from '../ui/BaseSelect.vue';

const props = defineProps({
    period: { type: String, default: 'last_12_months' },
    from: { type: String, default: '' },
    to: { type: String, default: '' },
    presets: { type: Array, default: () => PERIOD_PRESETS },
});

const emit = defineEmits(['update:period', 'update:from', 'update:to', 'change']);

const onPeriod = (value) => {
    emit('update:period', value);
    emit('change', { period: value, from: props.from, to: props.to });
};

const onDate = (key, value) => {
    emit(`update:${key}`, value);
    emit('change', { period: props.period, from: props.from, to: props.to, [key]: value });
};
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-end">
        <BaseSelect :model-value="period" label="Período" :options="presets" @update:model-value="onPeriod" />
        <template v-if="period === 'custom'">
            <BaseDateInput :model-value="from" label="De" @update:model-value="onDate('from', $event)" />
            <BaseDateInput :model-value="to" label="Até" @update:model-value="onDate('to', $event)" />
        </template>
    </div>
</template>
