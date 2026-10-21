<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    labelledBy: {
        type: String,
        default: undefined,
    },
    size: {
        type: String,
        default: 'md',
    },
});

const emit = defineEmits(['close']);

const dialog = ref(null);

const onKeydown = (event) => {
    if (event.key === 'Escape' && props.show) {
        emit('close');
    }
};

watch(
    () => props.show,
    async (visible) => {
        if (visible) {
            await new Promise((resolve) => setTimeout(resolve, 0));
            dialog.value?.focus();
        }
    },
);

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div
                class="fixed inset-0 bg-slate-950/70"
                aria-hidden="true"
                @click="emit('close')"
            ></div>

            <div
                ref="dialog"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="labelledBy"
                tabindex="-1"
                class="relative max-h-[90vh] w-full overflow-y-auto rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-xl focus:outline-none"
                :class="size === 'lg' ? 'max-w-2xl' : 'max-w-md'"
            >
                <slot />
            </div>
        </div>
    </Teleport>
</template>
