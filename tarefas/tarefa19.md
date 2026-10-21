# Item 3.8 — Histórico de manutenções

**Fase:** 3 — Despesas
**Posição:** item 8 de 8 da fase · tarefa 19 de 37 da sequência
**Pré-requisito:** `tarefa18.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.8 — Histórico de manutenções**
  - Criar migration `maintenances`: `vehicle_id`, `expense_id` (unique, FK restrict), `type` (`preventive`, `corrective`), `odometer` (nullable), `next_due_on` (nullable), `next_due_odometer` (nullable), `notes` (nullable), timestamps.
  - Criar manutenção junto com a despesa em transação (categoria entre as de manutenção/óleo/pneus/freios etc.); se a despesa falhar, nada é persistido.
  - Listar histórico por veículo com data, tipo, custo, odômetro, descrição e próxima troca; sinalizar vencimentos próximos por data ou odômetro.
  - Editar/excluir mantém despesa e manutenção sincronizadas; não permitir manutenção órfã.
  - Criar testes de transação, vínculo 1:1, próxima troca e listagem histórica.
  - Critérios de aceite: custo da manutenção aparece uma única vez nos totais; vencimentos não se confundem com despesas pagas.
  - Validação: `php artisan test --filter=Maintenance`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Esta é a última tarefa da Fase 3; ao terminar, aguarde orientação antes de iniciar a próxima fase.
