# Item 1.3 — Layout base, navegação e páginas provisórias

**Fase:** 1 — Fundação
**Posição:** item 3 de 5 da fase · tarefa 3 de 37 da sequência
**Pré-requisito:** `tarefa2.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 1 — Fundação") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **1.3 — Layout base, navegação e páginas provisórias**
  - Criar `AppLayout.vue` com menu lateral (Dashboard, Veículos, Despesas, Abastecimentos, Relatórios, Categorias), cabeçalho, área de conteúdo e versão mobile em drawer.
  - Navegação com estado ativo, `aria-current`, foco visível e link "pular para o conteúdo"; layout sem rolagem horizontal em 360 px.
  - Criar rotas nomeadas e páginas Vue provisórias para cada seção, além de página 404 Inertia. Manter os nomes das rotas estáveis para as fases seguintes.
  - Critérios de aceite: todas as rotas provisórias respondem 200; menu funciona em desktop e mobile; páginas provisórias usam estado vazio claro ("em construção").
  - Validação: teste de feature para cada rota, `npm run build` e conferência visual em 360 px e 1280 px.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — 1.3 — Layout base, navegação e páginas provisórias
- Branch: `tarefa3/1-layout-navegacao-paginas`
- Commit: `d95184c`
- O que foi feito:
  - `AppLayout.vue` reescrito com sidebar (Dashboard, Veículos, Despesas, Abastecimentos, Relatórios, Categorias), cabeçalho, área de conteúdo e **drawer** no mobile (overlay, fechamento por Escape e ao navegar).
  - Acessibilidade: link "Pular para o conteúdo", `aria-current="page"` no item ativo, foco visível (`focus-visible:ring`) e layout sem rolagem horizontal em 360 px.
  - Rotas nomeadas e estáveis: `dashboard`, `vehicles.index`, `expenses.index`, `fuelings.index`, `reports.index`, `categories.index`.
  - Páginas Vue provisórias (`Dashboard`, `Vehicles/Index`, `Expenses/Index`, `Fuelings/Index`, `Reports/Index`, `Categories/Index`) usando o componente reutilizável `EmptyState.vue` com o rótulo "Em construção".
  - Página 404 Inertia (`Error.vue`) servida via `Route::fallback` com status 404.
  - `/` passou a renderizar `Dashboard`; `Home.vue` removida.
  - `tests/Feature/NavigationTest.php` (novo) cobre cada rota provisória via `assertInertia` e a rota inexistente (404 + componente `Error`); `HomePageTest.php` removido por ter sido substituído.
- Validação:
  - `php artisan config:clear && php artisan test` → 8 testes / 59 asserções, todos passando.
  - `npm run build` → OK, com chunk por página (code splitting).
  - Conferência visual: rotas respondem 200 (`/`, `/vehicles`, `/expenses`, `/fuelings`, `/reports`, `/categories`) e 404 na inexistente; emulado 360 px sem rolagem horizontal (`scrollWidth == clientWidth == 360`), drawer abrindo (`translate -100% → 0`), `aria-expanded` alternando e `aria-current="page"` presente; desktop (~1232 px) com sidebar e item ativo destacado.
- Observações:
  - Caminhos e nomes de rota em **inglês** (`/vehicles`, `vehicles.index`, ...), com rótulos de UI em português — mantidos estáveis para as fases seguintes. Segue a convenção já existente (a rota anterior era `home`).
  - `tarefas/README.md` e o `README.md` raiz ainda citam `App.vue`/`ExampleComponent.vue`; atualização de documentação fica para a tarefa 1.5 (fora do escopo).
  - Trabalho **empilhado**: `review/tarefa3` parte de `review/tarefa2` e a PR aponta para `review/tarefa2` (o pré-requisito pede a base com a tarefa2 mergeada).
