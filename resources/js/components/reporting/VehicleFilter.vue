<script setup>
import { computed } from 'vue';
import BaseSelect from '../ui/BaseSelect.vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    vehicles: { type: Array, default: () => [] },
    label: { type: String, default: 'Veículo' },
    allLabel: { type: String, default: 'Todos os veículos' },
});

const emit = defineEmits(['update:modelValue']);
const options = computed(() => [{ value: '', label: props.allLabel }, ...props.vehicles]);
</script>

<template>
    <BaseSelect :model-value="modelValue" :label="label" :options="options" @update:model-value="emit('update:modelValue', $event)" />
</template>
