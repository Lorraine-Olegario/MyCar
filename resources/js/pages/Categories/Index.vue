<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import BaseModal from '../../components/ui/BaseModal.vue';
import ConfirmDialog from '../../components/ui/ConfirmDialog.vue';
import EmptyState from '../../components/ui/EmptyState.vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

const editing = ref(null);
const formOpen = ref(false);
const categoryToDelete = ref(null);
const deleting = ref(false);

const form = useForm({
    name: '',
    color: '#78716c',
    active: true,
});

const activeCount = computed(() => props.categories.filter((category) => category.active).length);

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    formOpen.value = true;
};

const openEdit = (category) => {
    editing.value = category;
    form.name = category.name;
    form.color = category.color ?? '#78716c';
    form.active = category.active;
    form.clearErrors();
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeForm,
    };

    if (editing.value) {
        form.put(`/categories/${editing.value.id}`, options);
    } else {
        form.post('/categories', options);
    }
};

const toggle = (category) => {
    router.patch(`/categories/${category.id}/toggle`, {}, { preserveScroll: true });
};

const move = (category, direction) => {
    router.patch(`/categories/${category.id}/move`, { direction }, { preserveScroll: true });
};

const confirmDelete = () => {
    deleting.value = true;

    router.delete(`/categories/${categoryToDelete.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            categoryToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Categorias" />

    <div class="mx-auto max-w-4xl space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-100">Categorias</h1>
                <p class="mt-1 max-w-prose text-sm text-slate-400">
                    Organize as despesas. Categorias inativas não aparecem em novos lançamentos, mas
                    continuam válidas nos registros antigos.
                </p>
            </div>

            <BaseButton @click="openCreate">Nova categoria</BaseButton>
        </header>

        <EmptyState
            v-if="categories.length === 0"
            badge="Nenhuma categoria"
            heading-level="h2"
            title="Nenhuma categoria cadastrada"
            description="Rode o seeder ou crie a primeira categoria para classificar despesas."
        />

        <template v-else>
            <p class="text-sm text-slate-500">
                {{ categories.length }} categorias · {{ activeCount }} ativas
            </p>

            <ol class="divide-y divide-slate-800 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
                <li
                    v-for="(category, index) in categories"
                    :key="category.id"
                    class="flex flex-wrap items-center gap-3 px-5 py-3"
                >
                    <span
                        class="h-4 w-4 shrink-0 rounded-full border border-slate-700"
                        :style="{ backgroundColor: category.color ?? 'transparent' }"
                        aria-hidden="true"
                    ></span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold" :class="category.active ? 'text-slate-100' : 'text-slate-500'">
                            {{ category.name }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ category.in_use ? 'Em uso por despesas' : 'Sem despesas' }}
                        </p>
                    </div>

                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="category.active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ category.active ? 'Ativa' : 'Inativa' }}
                    </span>

                    <div class="flex flex-wrap items-center gap-1">
                        <BaseButton
                            variant="ghost"
                            size="sm"
                            :disabled="index === 0"
                            :aria-label="`Mover ${category.name} para cima`"
                            @click="move(category, 'up')"
                        >
                            ↑
                        </BaseButton>
                        <BaseButton
                            variant="ghost"
                            size="sm"
                            :disabled="index === categories.length - 1"
                            :aria-label="`Mover ${category.name} para baixo`"
                            @click="move(category, 'down')"
                        >
                            ↓
                        </BaseButton>
                        <BaseButton variant="secondary" size="sm" @click="openEdit(category)">Editar</BaseButton>
                        <BaseButton variant="secondary" size="sm" @click="toggle(category)">
                            {{ category.active ? 'Desativar' : 'Ativar' }}
                        </BaseButton>
                        <BaseButton
                            variant="ghost"
                            size="sm"
                            :disabled="category.in_use"
                            :title="category.in_use ? 'Categoria em uso: desative em vez de excluir' : undefined"
                            @click="categoryToDelete = category"
                        >
                            Excluir
                        </BaseButton>
                    </div>
                </li>
            </ol>
        </template>

        <BaseModal :show="formOpen" labelled-by="category-form-title" @close="closeForm">
            <form class="space-y-4" novalidate @submit.prevent="submit">
                <h2 id="category-form-title" class="text-lg font-semibold text-slate-100">
                    {{ editing ? 'Editar categoria' : 'Nova categoria' }}
                </h2>

                <BaseInput v-model="form.name" label="Nome" required :error="form.errors.name" />

                <div>
                    <label for="category-color" class="block text-sm font-medium text-slate-300">Cor</label>
                    <input
                        id="category-color"
                        v-model="form.color"
                        type="color"
                        class="mt-1 h-10 w-20 cursor-pointer rounded-lg border border-slate-700 bg-slate-900 p-1"
                    >
                    <p v-if="form.errors.color" class="mt-1 text-xs text-rose-400" role="alert">{{ form.errors.color }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input v-model="form.active" type="checkbox" class="h-4 w-4 rounded border-slate-600 text-indigo-500">
                    Categoria ativa
                </label>

                <div class="flex flex-wrap justify-end gap-3 pt-2">
                    <BaseButton variant="secondary" :disabled="form.processing" @click="closeForm">Cancelar</BaseButton>
                    <BaseButton type="submit" :loading="form.processing">Salvar</BaseButton>
                </div>
            </form>
        </BaseModal>

        <ConfirmDialog
            :show="categoryToDelete !== null"
            title="Excluir categoria"
            :message="`A categoria ${categoryToDelete?.name ?? ''} será excluída. Esta ação não pode ser desfeita.`"
            confirm-label="Excluir"
            :processing="deleting"
            @confirm="confirmDelete"
            @cancel="categoryToDelete = null"
        />
    </div>
</template>
