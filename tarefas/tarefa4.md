# Item 1.4 — Padrões de UI, formatação brasileira e feedback

**Fase:** 1 — Fundação
**Posição:** item 4 de 5 da fase · tarefa 4 de 37 da sequência
**Pré-requisito:** `tarefa3.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 1 — Fundação") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **1.4 — Padrões de UI, formatação brasileira e feedback**
  - Criar utilitários `formatCurrency` (R$), `formatDate` (dd/mm/aaaa), `formatMonthYear`, `formatKm` e `parseCurrencyInput`, usando `Intl` e evitando deslocamento de fuso em datas.
  - Criar componentes base: botão, input, select, textarea, modal, diálogo de confirmação, paginação, estado vazio, estado de carregamento, estado de erro e mensagens de sucesso/erro.
  - Flash messages via props compartilhadas do Inertia; confirmação obrigatória antes de excluir ou arquivar.
  - Adicionar **Vitest** apenas para utilitários de formatação/conversão, com script `npm test`.
  - Critérios de aceite: moeda no padrão `R$ 1.234,56`, datas no padrão `dd/mm/aaaa`, mensagens acessíveis e botões com foco visível.
  - Validação: `npm test` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — 1.4 — Padrões de UI, formatação brasileira e feedback
- Branch: `tarefa4/1-padroes-ui-formatacao`
- Commit: `a48c109`
- O que foi feito:
  - `resources/js/utils/format.js`: `formatCurrency` (R$), `formatDate` (dd/mm/aaaa), `formatMonthYear`, `formatKm` e `parseCurrencyInput`, usando `Intl`. Datas em string ISO são lidas literalmente (`/^(\d{4})-(\d{2})-(\d{2})/`) para não sofrer deslocamento de fuso.
  - Componentes base em `resources/js/components/ui/`: `BaseButton`, `BaseInput`, `BaseSelect`, `BaseTextarea`, `BaseModal`, `ConfirmDialog`, `BasePagination`, `LoadingState`, `ErrorState` e `FlashMessages`. `EmptyState.vue` movido de `components/` para `components/ui/` (imports das páginas atualizados).
  - Flash messages consumidas das props compartilhadas do Inertia (`flash.success`/`error`/`message`, já compartilhadas desde a tarefa2) e renderizadas no `AppLayout` com `role="status"`/`role="alert"`.
  - `ConfirmDialog` acessível (role dialog, `aria-modal`, fecha no Escape, foco inicial no diálogo) para confirmação obrigatória antes de excluir/arquivar.
  - Botões e controles com foco visível (`focus-visible:ring`) e estados de erro/loading acessíveis.
  - **Vitest** adicionado apenas para utilitários: `vitest.config.js` e script `"test": "vitest run"`; `resources/js/utils/format.test.js` com 14 testes.
- Validação:
  - `npm test` → 14 testes passando (Vitest 4.1.12).
  - `npm run build` → OK.
  - `php artisan test` → 8 testes / 59 asserções, sem regressão.
  - Vistoria: moeda `R$ 1.234,56`, data `21/10/2026`, mês `outubro de 2026`, km `12.345 km` (cobertos por teste).
- Observações:
  - `formatCurrency` troca o NBSP que o `Intl` insere por espaço comum, para casar literalmente com o padrão `R$ 1.234,56`.
  - `formatKm` usa 0 casas decimais (odômetro é inteiro).
  - O bug do npm 10.8.x citado no README apareceu ao instalar o Vitest; a instalação foi feita com `npx npm@11 install -D vitest` (alternativa indicada no próprio README).
  - `parseCurrencyInput` trata `1.234,56` e `1234,56` como reais; entradas ambíguas sem vírgula (ex.: `1.234`) são interpretadas como decimal.
  - Trabalho **empilhado**: `review/tarefa4` parte de `review/tarefa3` e a PR aponta para `review/tarefa3`.
