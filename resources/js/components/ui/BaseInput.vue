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
    type: {
        type: String,
        default: 'text',
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
const inputId = computed(() => props.id || `input-${generatedId}`);

const describedBy = computed(() => {
    const ids = [];

    if (props.hint) {
        ids.push(`${inputId.value}-hint`);
    }

    if (props.error) {
        ids.push(`${inputId.value}-error`);
    }

    return ids.length ? ids.join(' ') : undefined;
});
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="inputId"
            class="block text-sm font-medium text-slate-300"
        >
            {{ label }}<span v-if="required" class="text-rose-400" aria-hidden="true"> *</span>
        </label>

        <input
            :id="inputId"
            :type="type"
            :value="modelValue"
            :required="required"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            class="mt-1 block w-full rounded-lg border bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            :class="error ? 'border-rose-500' : 'border-slate-700'"
            @input="emit('update:modelValue', $event.target.value)"
        >

        <p v-if="hint && !error" :id="`${inputId}-hint`" class="mt-1 text-xs text-slate-500">
            {{ hint }}
        </p>

        <p v-if="error" :id="`${inputId}-error`" class="mt-1 text-xs text-rose-400" role="alert">
            {{ error }}
        </p>
    </div>
</template>
