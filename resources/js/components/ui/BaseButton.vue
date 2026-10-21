<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
    },
    size: {
        type: String,
        default: 'md',
    },
    type: {
        type: String,
        default: 'button',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const variants = {
    primary: 'bg-indigo-500 text-white hover:bg-indigo-400 focus-visible:ring-indigo-300',
    secondary: 'border border-slate-700 text-slate-200 hover:bg-slate-800 focus-visible:ring-indigo-300',
    danger: 'bg-rose-600 text-white hover:bg-rose-500 focus-visible:ring-rose-300',
    ghost: 'text-slate-300 hover:bg-slate-800 focus-visible:ring-indigo-300',
};

const sizes = {
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-base',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950 disabled:cursor-not-allowed disabled:opacity-60',
    variants[props.variant] ?? variants.primary,
    sizes[props.size] ?? sizes.md,
]);

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <button
        :type="type"
        :disabled="isDisabled"
        :aria-busy="loading || undefined"
        :class="classes"
    >
        <span
            v-if="loading"
            class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"
            aria-hidden="true"
        ></span>
        <slot />
    </button>
</template>
