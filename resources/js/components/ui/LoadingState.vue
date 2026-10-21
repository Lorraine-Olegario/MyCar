<script setup>
defineProps({
    label: {
        type: String,
        default: 'Carregando…',
    },
});
</script>

<template>
    <div
        class="flex items-center justify-center gap-3 py-10 text-slate-400"
        role="status"
        aria-live="polite"
    >
        <span
            class="h-5 w-5 animate-spin rounded-full border-2 border-current border-t-transparent"
            aria-hidden="true"
        ></span>
        <span class="text-sm">{{ label }}</span>
    </div>
</template>
