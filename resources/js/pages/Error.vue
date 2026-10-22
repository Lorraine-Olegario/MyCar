<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: Number,
        default: 404,
    },
});

const messages = {
    403: ['Acesso negado', 'Você não tem permissão para acessar esta página.'],
    404: ['Página não encontrada', 'O endereço acessado não existe ou foi movido.'],
    419: ['Sessão expirada', 'A página ficou aberta por muito tempo. Recarregue e tente novamente.'],
    429: ['Muitas tentativas', 'Aguarde alguns instantes antes de tentar novamente.'],
    500: ['Algo deu errado', 'Não foi possível concluir a operação. Tente novamente; se continuar, consulte o log do servidor.'],
    503: ['Serviço indisponível', 'O sistema está em manutenção. Tente novamente em instantes.'],
};

const content = computed(() => messages[props.status] ?? messages[500]);
</script>

<template>
    <Head :title="content[0]" />

    <section
        class="mx-auto max-w-prose rounded-2xl border border-dashed border-slate-700 bg-slate-900/40 p-8 text-center"
    >
        <p class="text-sm font-semibold uppercase tracking-widest text-indigo-400">
            {{ status }}
        </p>
        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-100">
            {{ content[0] }}
        </h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-400">
            {{ content[1] }}
        </p>
        <Link
            href="/"
            class="mt-6 inline-flex rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
        >
            Voltar ao dashboard
        </Link>
    </section>
</template>
