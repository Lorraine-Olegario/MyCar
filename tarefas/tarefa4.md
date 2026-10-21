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
