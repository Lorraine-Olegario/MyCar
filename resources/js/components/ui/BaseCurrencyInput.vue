<script setup>
import { computed, ref, useId, watch } from 'vue';
import { formatCurrency, parseCurrencyInput } from '../../utils/format';

const props = defineProps({
    modelValue: {
        type: Number,
        default: null,
    },
    label: {
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
const inputId = computed(() => props.id || `currency-${generatedId}`);

const toText = (cents) =>
    cents === null || cents === undefined || cents === '' ? '' : formatCurrency(cents);

const text = ref(toText(props.modelValue));
const focused = ref(false);

// Enquanto o campo está em edição o valor exibido é do usuário: reescrever o
// texto a cada tecla brigaria com a digitação (o valor enviado é em centavos).
watch(
    () => props.modelValue,
    (value) => {
        if (!focused.value) {
            text.value = toText(value);
        }
    },
);

const cents = (raw) => (String(raw).replace(/\D/g, '') === '' ? null : parseCurrencyInput(raw));

const describedBy = computed(() => {
    const ids = [];

    if (props.hint && !props.error) {
        ids.push(`${inputId.value}-hint`);
    }

    if (props.error) {
        ids.push(`${inputId.value}-error`);
    }

    return ids.length ? ids.join(' ') : undefined;
});

const onInput = (event) => {
    text.value = event.target.value;

    emit('update:modelValue', cents(text.value));
};

const onFocus = () => {
    focused.value = true;
};

const onBlur = () => {
    const value = cents(text.value);

    focused.value = false;
    text.value = toText(value);

    emit('update:modelValue', value);
};
</script>

<template>
    <div>
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-slate-300">
            {{ label }}<span v-if="required" class="text-rose-400" aria-hidden="true"> *</span>
        </label>

        <div class="relative mt-1">
            <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500"
                aria-hidden="true"
            >
                R$
            </span>

            <input
                :id="inputId"
                :value="text"
                type="text"
                inputmode="decimal"
                placeholder="0,00"
                :required="required"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="describedBy"
                class="block w-full rounded-lg border bg-slate-900 py-2 pl-9 pr-3 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                :class="error ? 'border-rose-500' : 'border-slate-700'"
                @input="onInput"
                @focus="onFocus"
                @blur="onBlur"
            >
        </div>

        <p v-if="hint && !error" :id="`${inputId}-hint`" class="mt-1 text-xs text-slate-500">
            {{ hint }}
        </p>

        <p v-if="error" :id="`${inputId}-error`" class="mt-1 text-xs text-rose-400" role="alert">
            {{ error }}
        </p>
    </div>
</template>
