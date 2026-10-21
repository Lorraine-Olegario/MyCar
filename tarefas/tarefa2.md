# Item 1.2 — Instalar e configurar Inertia.js com Vue 3

**Fase:** 1 — Fundação
**Posição:** item 2 de 5 da fase · tarefa 2 de 37 da sequência
**Pré-requisito:** `tarefa1.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 1 — Fundação") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **1.2 — Instalar e configurar Inertia.js com Vue 3**
  - Instalar `inertiajs/inertia-laravel` (Composer) e `@inertiajs/vue3` (npm), mantendo Vite e Tailwind existentes.
  - Criar `app/Http/Middleware/HandleInertiaRequests.php`, registrar no grupo `web` e compartilhar `appName` e futuros flash messages.
  - Substituir a montagem manual do Vue por `createInertiaApp` com `resolvePageComponent` e layout padrão.
  - Trocar `app.blade.php` pelo template raiz com `@inertia` e `@vite`; remover `App.vue`, `ExampleComponent.vue` e o atributo `data-app-name`.
  - Criar `resources/js/pages/Home.vue` e fazer `/` renderizar via `Inertia::render`.
  - Critérios de aceite: `/` responde 200 com página Inertia; não há código morto do exemplo; build de produção funciona.
  - Validação: `php artisan test` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
