<script setup>
import { computed } from 'vue';
import BaseSelect from '../ui/BaseSelect.vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    categories: { type: Array, default: () => [] },
    label: { type: String, default: 'Categoria' },
    allLabel: { type: String, default: 'Todas as categorias' },
});

const emit = defineEmits(['update:modelValue']);
const options = computed(() => [{ value: '', label: props.allLabel }, ...props.categories]);
</script>

<template>
    <BaseSelect :model-value="modelValue" :label="label" :options="options" @update:model-value="emit('update:modelValue', $event)" />
</template>
