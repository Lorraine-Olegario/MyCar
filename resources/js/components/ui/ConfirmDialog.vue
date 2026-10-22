<script setup>
import BaseButton from './BaseButton.vue';
import BaseModal from './BaseModal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirmar ação',
    },
    message: {
        type: String,
        default: 'Tem certeza de que deseja continuar?',
    },
    confirmLabel: {
        type: String,
        default: 'Confirmar',
    },
    cancelLabel: {
        type: String,
        default: 'Cancelar',
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <BaseModal :show="show" labelled-by="confirm-dialog-title" @close="emit('cancel')">
        <h2 id="confirm-dialog-title" class="text-lg font-semibold text-slate-100">
            {{ title }}
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ message }}</p>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            <BaseButton variant="secondary" :disabled="processing" @click="emit('cancel')">
                {{ cancelLabel }}
            </BaseButton>

            <BaseButton variant="danger" :loading="processing" @click="emit('confirm')">
                {{ confirmLabel }}
            </BaseButton>
        </div>
    </BaseModal>
</template>
