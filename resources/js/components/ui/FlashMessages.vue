<script setup>
import { computed, reactive, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const flash = computed(() => page.props.flash ?? {});

const dismissed = reactive({
    success: false,
    error: false,
    message: false,
});

watch(
    flash,
    () => {
        dismissed.success = false;
        dismissed.error = false;
        dismissed.message = false;
    },
    { deep: true },
);

const baseClass =
    'flex items-start justify-between gap-4 rounded-lg border px-4 py-3 text-sm';

const dismissClass =
    'rounded p-1 text-current/70 transition hover:text-current focus:outline-none focus-visible:ring-2 focus-visible:ring-current';
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="flash.success && !dismissed.success"
            role="status"
            aria-live="polite"
            :class="[baseClass, 'border-emerald-800/60 bg-emerald-950/40 text-emerald-200']"
        >
            <p>{{ flash.success }}</p>
            <button
                type="button"
                :class="dismissClass"
                aria-label="Fechar mensagem de sucesso"
                @click="dismissed.success = true"
            >
                ✕
            </button>
        </div>

        <div
            v-if="flash.error && !dismissed.error"
            role="alert"
            :class="[baseClass, 'border-rose-800/60 bg-rose-950/40 text-rose-200']"
        >
            <p>{{ flash.error }}</p>
            <button
                type="button"
                :class="dismissClass"
                aria-label="Fechar mensagem de erro"
                @click="dismissed.error = true"
            >
                ✕
            </button>
        </div>

        <div
            v-if="flash.message && !dismissed.message"
            role="status"
            aria-live="polite"
            :class="[baseClass, 'border-indigo-800/60 bg-indigo-950/40 text-indigo-200']"
        >
            <p>{{ flash.message }}</p>
            <button
                type="button"
                :class="dismissClass"
                aria-label="Fechar mensagem"
                @click="dismissed.message = true"
            >
                ✕
            </button>
        </div>
    </div>
</template>
