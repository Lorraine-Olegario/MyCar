<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'Paginação',
    },
});

const decode = (value) =>
    String(value)
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
</script>

<template>
    <nav v-if="links.length > 3" :aria-label="label" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="rounded-lg px-3 py-1.5 text-sm text-slate-600"
                aria-disabled="true"
            >
                {{ decode(link.label) }}
            </span>

            <Link
                v-else
                :href="link.url"
                class="rounded-lg px-3 py-1.5 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"
                :class="
                    link.active
                        ? 'bg-indigo-500/15 font-medium text-slate-100'
                        : 'text-slate-400 hover:bg-slate-800 hover:text-slate-100'
                "
                :aria-current="link.active ? 'page' : undefined"
            >
                {{ decode(link.label) }}
            </Link>
        </template>
    </nav>
</template>
