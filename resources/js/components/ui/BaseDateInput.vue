<script setup>
import { computed, ref, useId, watch } from 'vue';
import { formatDate, parseDateInput } from '../../utils/format';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
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
const inputId = computed(() => props.id || `date-${generatedId}`);

const text = ref(formatDate(props.modelValue));
const invalid = ref(false);
const focused = ref(false);

// Enquanto o campo está em edição o texto é do usuário; sincronizar a cada
// tecla apagaria uma data digitada pela metade.
watch(
    () => props.modelValue,
    (value) => {
        if (!focused.value) {
            text.value = formatDate(value);
            invalid.value = false;
        }
    },
);

const message = computed(() => {
    if (props.error) {
        return props.error;
    }

    return invalid.value ? 'Informe uma data válida (dd/mm/aaaa).' : '';
});

const describedBy = computed(() => {
    const ids = [];

    if (props.hint && !message.value) {
        ids.push(`${inputId.value}-hint`);
    }

    if (message.value) {
        ids.push(`${inputId.value}-error`);
    }

    return ids.length ? ids.join(' ') : undefined;
});

const onInput = (event) => {
    const digits = event.target.value.replace(/\D/g, '').slice(0, 8);

    let masked = digits;

    if (digits.length > 4) {
        masked = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
    } else if (digits.length > 2) {
        masked = `${digits.slice(0, 2)}/${digits.slice(2)}`;
    }

    text.value = masked;
    invalid.value = masked.length === 10 && parseDateInput(masked) === '';

    emit('update:modelValue', parseDateInput(masked));
};

const onFocus = () => {
    focused.value = true;
};

const onBlur = () => {
    focused.value = false;
    text.value = formatDate(parseDateInput(text.value));
    invalid.value = false;
};
</script>

<template>
    <div>
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-slate-300">
            {{ label }}<span v-if="required" class="text-rose-400" aria-hidden="true"> *</span>
        </label>

        <input
            :id="inputId"
            :value="text"
            type="text"
            inputmode="numeric"
            maxlength="10"
            placeholder="dd/mm/aaaa"
            :required="required"
            :aria-invalid="message ? 'true' : undefined"
            :aria-describedby="describedBy"
            class="mt-1 block w-full rounded-lg border bg-slate-900 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
            :class="message ? 'border-rose-500' : 'border-slate-700'"
            @input="onInput"
            @focus="onFocus"
            @blur="onBlur"
        >

        <p v-if="hint && !message" :id="`${inputId}-hint`" class="mt-1 text-xs text-slate-500">
            {{ hint }}
        </p>

        <p v-if="message" :id="`${inputId}-error`" class="mt-1 text-xs text-rose-400" role="alert">
            {{ message }}
        </p>
    </div>
</template>
