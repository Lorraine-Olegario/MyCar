<script setup>
import BaseButton from './BaseButton.vue';

defineProps({
    title: {
        type: String,
        default: 'Algo deu errado',
    },
    message: {
        type: String,
        default: 'Não foi possível carregar as informações. Tente novamente.',
    },
});

const emit = defineEmits(['retry']);
</script>

<template>
    <div
        class="rounded-2xl border border-rose-900/60 bg-rose-950/30 p-6 text-center"
        role="alert"
    >
        <h2 class="text-base font-semibold text-rose-200">{{ title }}</h2>

        <p class="mx-auto mt-2 max-w-prose text-sm leading-relaxed text-rose-300/80">
            {{ message }}
        </p>

        <BaseButton
            v-if="$attrs.onRetry"
            variant="secondary"
            class="mt-4"
            @click="emit('retry')"
        >
            Tentar novamente
        </BaseButton>
    </div>
</template>
