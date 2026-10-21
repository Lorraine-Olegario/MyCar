<script setup>
import { defineAsyncComponent, ref } from 'vue';

defineProps({
    type: { type: String, required: true },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    ariaLabel: { type: String, required: true },
    height: { type: String, default: 'h-64' },
});

const emit = defineEmits(['error']);
const failed = ref(false);

const Bundle = defineAsyncComponent({
    loader: () => import('./charts/ChartJsBundle.vue'),
    onError: (error, retry, fail) => {
        failed.value = true;
        emit('error', error);
        fail();
    },
});
</script>

<template>
    <div class="relative w-full" :class="height">
        <p
            v-if="failed"
            class="flex h-full items-center justify-center rounded-xl bg-slate-950 px-4 text-center text-sm text-slate-500"
            role="status"
        >
            Não foi possível carregar o gráfico. Consulte a tabela.
        </p>

        <div v-else class="h-full w-full" role="img" :aria-label="ariaLabel">
            <Suspense>
                <Bundle :type="type" :data="data" :options="options" />
                <template #fallback>
                    <div class="h-full w-full animate-pulse rounded-xl bg-slate-950" aria-hidden="true"></div>
                </template>
            </Suspense>
        </div>
    </div>
</template>
