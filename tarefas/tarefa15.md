# Item 3.4 — Despesas recorrentes: modelos e ocorrências

**Fase:** 3 — Despesas
**Posição:** item 4 de 8 da fase · tarefa 15 de 37 da sequência
**Pré-requisito:** `tarefa14.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.4 — Despesas recorrentes: modelos e ocorrências**
  - Criar `recurring_expenses`: `vehicle_id`, `expense_category_id`, `description`, `expected_amount_cents`, `frequency` (`monthly`, `quarterly`, `semiannual`, `annual`), `start_date`, `end_date` (nullable), `due_day` (1–31), `active`, `notes`, timestamps.
  - Criar `recurring_expense_occurrences`: `recurring_expense_id`, `competence_date` (primeiro dia do mês), `due_date`, `expected_amount_cents`, `status` (`forecast`, `paid`, `canceled`), `paid_at` (nullable), `paid_amount_cents` (nullable), `expense_id` (nullable, unique), `notes`; unique (`recurring_expense_id`, `competence_date`).
  - Implementar geração idempotente de ocorrências para os próximos meses, respeitando início/fim e ajustando `due_day` para o último dia em meses curtos; comando `recurring:generate {--months=12}` agendado diariamente.
  - Confirmar pagamento em transação: cria a despesa real (data, valor pago, veículo/categoria do modelo), vincula e marca a ocorrência como paga; bloquear confirmação dupla e permitir cancelar previsão.
  - Editar o modelo afeta apenas ocorrências previstas futuras; pagas permanecem como histórico.
  - Expor estimativa mensal proporcional (valor anual ÷ 12) separada do valor efetivamente pago no mês.
  - Criar testes de geração mensal/trimestral/semestral/anual, mês curto, idempotência, confirmação única, cancelamento e não contaminação dos totais realizados por previsões.
  - Critérios de aceite: previsão nunca aparece como paga; valores anuais mostram pago e estimativa mensal com rótulos claros.
  - Validação: `php artisan test --filter=RecurringExpense`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
