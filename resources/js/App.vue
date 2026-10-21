<script setup>
import ExampleComponent from './components/ExampleComponent.vue';

defineProps({
    appName: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center px-6 py-16">
            <header class="mb-10">
                <p class="text-sm font-medium uppercase tracking-widest text-indigo-400">
                    Laravel + Vue 3
                </p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-white">
                    {{ appName }}
                </h1>
                <p class="mt-3 text-base leading-relaxed text-slate-400">
                    Aplicação inicial com Laravel, Vue 3, Vite e SQLite configurados.
                </p>
            </header>

            <ExampleComponent
                title="Componente Vue de exemplo"
                message="Este componente é renderizado pelo Vue e compilado pelo Vite."
            />
        </div>
    </div>
</template>
