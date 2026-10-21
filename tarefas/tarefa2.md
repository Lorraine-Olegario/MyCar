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

---

## Realizado

### Item 1 — 1.2 — Instalar e configurar Inertia.js com Vue 3
- Branch: `tarefa2/1-instalar-configurar-inertia`
- Commit: `1b47d4a`
- O que foi feito:
  - Dependências: `inertiajs/inertia-laravel` v3.5.1 (Composer) e `@inertiajs/vue3` v3.9.1 (npm), mantendo Vite e Tailwind.
  - `app/Http/Middleware/HandleInertiaRequests.php` criado (`rootView = 'app'`), compartilhando `appName` (de `config('app.name')`) e `flash` (success/error/message) de forma lazy; registrado no grupo `web` em `bootstrap/app.php`.
  - `resources/js/app.js` reescrito com `createInertiaApp`, `resolvePageComponent` (`laravel-vite-plugin/inertia-helpers`) e **layout padrão** `AppLayout` aplicado via `page.default.layout`.
  - `resources/js/layouts/AppLayout.vue` (novo) usa `usePage()` para exibir `appName`; `resources/js/pages/Home.vue` (novo) é a página inicial.
  - `resources/views/app.blade.php` virou o template raiz com `@inertia`, `@inertiaHead` e `@vite`.
  - `routes/web.php`: `/` agora renderiza via `Inertia::render('Home')`.
  - Removidos `resources/js/App.vue`, `resources/js/components/ExampleComponent.vue` e o atributo `data-app-name` (sem código morto do exemplo).
  - `tests/Feature/HomePageTest.php` atualizado para `assertInertia` (componente `Home` e prop `appName`).
- Validação:
  - `php artisan config:clear && php artisan test` → 2 testes / 11 asserções, todos passando.
  - `npm run build` → OK, com chunk `Home` gerado (code splitting do Vite).
  - `/` responde 200 renderizando o componente Inertia `Home` (coberto pelo teste de feature).
- Observações:
  - Pré-requisito do arquivo (`tarefa1.md` mergeada na base) **não** foi atendido: por decisão do usuário o trabalho segue **empilhado** — `review/tarefa2` parte de `review/tarefa1`, e a PR aponta para `review/tarefa1` em vez de `main`.
  - `tarefas/README.md` e o `README.md` raiz ainda descrevem o componente de exemplo removido; a atualização de documentação fica para a tarefa 1.5 (fora do escopo deste item).
  - `.env` local não foi alterado; `VITE_APP_NAME="${APP_NAME}"` já existia no `.env.example` e é usado como fallback do título.
