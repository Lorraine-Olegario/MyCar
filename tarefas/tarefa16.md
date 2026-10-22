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

---

## Realizado

### Item 1 — Interface de despesas recorrentes e vencimentos
- Branch: `tarefa16/1-interface-recorrentes`
- Commit: `695964f`
- O que foi feito: `RecurringExpenseController` (index, store, update, pagar e cancelar ocorrência) e rotas `/recurring`; página `Recurring/Index.vue` com resumo do mês (estimativa mensal, pago e previsto, sempre separados), modelos ativos/inativos com próximo vencimento, formulário, linha do tempo por modelo com status distintos (previsto/pago/cancelado), modal de confirmação de pagamento e cancelamento com confirmação; componente reutilizável `UpcomingDueList.vue` e `RecurringExpenseService::upcoming()` (inclui vencidas) para dashboard e página do veículo; item "Recorrentes" na navegação. Corrigido o default `active=true` do model, que impedia a geração quando o campo não vinha no payload.
- Validação: `php artisan test` (137 testes) e `npm run build` ok.
- Observações: Confirmar de novo uma ocorrência já paga devolve mensagem de erro e não cria segunda despesa (testado). Interação visual não exercitada no navegador nesta tarefa.
