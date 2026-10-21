<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    id: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const generatedId = useId();
const selectId = computed(() => props.id || `select-${generatedId}`);

const describedBy = computed(() => {
    const ids = [];

    if (props.hint) {
        ids.push(`${selectId.value}-hint`);
    }

    if (props.error) {
        ids.push(`${selectId.value}-error`);
    }

    return ids.length ? ids.join(' ') : undefined;
});
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="selectId"
            class="block text-sm font-medium text-slate-300"
        >
            {{ label }}<span v-if="required" class="text-rose-400" aria-hidden="true"> *</span>
        </label>

        <select
            :id="selectId"
            :value="modelValue"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            class="mt-1 block w-full rounded-lg border bg-slate-900 px-3 py-2 text-sm text-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            :class="error ? 'border-rose-500' : 'border-slate-700'"
            @change="emit('update:modelValue', $event.target.value)"
        >
            <option v-if="placeholder" value="">{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>

        <p v-if="hint && !error" :id="`${selectId}-hint`" class="mt-1 text-xs text-slate-500">
            {{ hint }}
        </p>

        <p v-if="error" :id="`${selectId}-error`" class="mt-1 text-xs text-rose-400" role="alert">
            {{ error }}
        </p>
    </div>
</template>
