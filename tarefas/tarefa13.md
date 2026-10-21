# Item 3.2 — Domínio e CRUD de despesas

**Fase:** 3 — Despesas
**Posição:** item 2 de 8 da fase · tarefa 13 de 37 da sequência
**Pré-requisito:** `tarefa12.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.2 — Domínio e CRUD de despesas**
  - Criar migration `expenses`: `vehicle_id` (FK, restrict), `expense_category_id` (FK, restrict), `description`, `amount_cents`, `occurred_on` (date, index), `odometer` (nullable), `vendor` (nullable), `payment_method` (nullable, enum), `receipt_path` (nullable), `notes` (nullable), `recurring_expense_id` (nullable, FK set null), timestamps.
  - Criar enum `PaymentMethod` (`cash`, `debit`, `credit`, `pix`, `transfer`, `boleto`, `other`).
  - `ExpenseController` com index (filtros veículo/categoria/período/busca, paginação de 20), store, update e destroy; validar valor > 0, data válida, odômetro não negativo e categoria ativa no cadastro.
  - Comprovante opcional em disco privado, com rota de leitura e remoção do arquivo ao excluir/substituir.
  - Criar testes de despesa retroativa (2023/2024/2025), validação, categoria inativa, restrição de exclusão de veículo com despesas e upload de comprovante.
  - Critérios de aceite: despesas antigas são aceitas e mantêm a data real; totais sempre derivam de `occurred_on`; exclusão não deixa arquivo órfão.
  - Validação: `php artisan test --filter=Expense`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
