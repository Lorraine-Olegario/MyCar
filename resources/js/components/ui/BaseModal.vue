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

let previouslyFocused = null;

const focusable = () =>
    [...(dialog.value?.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])') ?? [])];

const onKeydown = (event) => {
    if (!props.show) {
        return;
    }

    if (event.key === 'Escape') {
        emit('close');

        return;
    }

    // O foco fica preso no diálogo enquanto ele está aberto.
    if (event.key === 'Tab' && dialog.value) {
        const items = focusable();

        if (items.length === 0) {
            event.preventDefault();
            dialog.value.focus();

            return;
        }

        const first = items[0];
        const last = items[items.length - 1];

        if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog.value)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
};

watch(
    () => props.show,
    async (visible) => {
        if (visible) {
            previouslyFocused = document.activeElement;
            await new Promise((resolve) => setTimeout(resolve, 0));
            dialog.value?.focus();
        } else if (previouslyFocused instanceof HTMLElement) {
            // Ao fechar, devolve o foco ao elemento que abriu o diálogo.
            previouslyFocused.focus();
            previouslyFocused = null;
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
