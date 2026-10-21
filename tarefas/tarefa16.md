# Item 3.5 — Interface de despesas recorrentes e vencimentos

**Fase:** 3 — Despesas
**Posição:** item 5 de 8 da fase · tarefa 16 de 37 da sequência
**Pré-requisito:** `tarefa15.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.5 — Interface de despesas recorrentes e vencimentos**
  - Página `/recurring` com modelos ativos/inativos, frequência, próximo vencimento, valor previsto, estimativa mensal e ações.
  - Formulário de criar/editar com frequência, valor previsto, início, fim opcional, dia de vencimento, veículo e categoria.
  - Linha do tempo de ocorrências com status (previsto/pago/cancelado), botão "Confirmar pagamento" (data e valor pago) e cancelamento com confirmação.
  - Lista de próximos vencimentos (seguro, IPVA, documentação e demais recorrências) reutilizável pelo dashboard e pela página do veículo.
  - Critérios de aceite: confirmar pagamento cria exatamente uma despesa; previsões e pagos ficam visualmente distintos; layout mobile funcional.
  - Validação: testes de feature e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
