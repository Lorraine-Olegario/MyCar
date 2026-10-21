# Item 2.2 — CRUD de veículos e arquivamento no backend

**Fase:** 2 — Veículos
**Posição:** item 2 de 6 da fase · tarefa 7 de 37 da sequência
**Pré-requisito:** `tarefa6.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 2 — Veículos") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **2.2 — CRUD de veículos e arquivamento no backend**
  - Criar `StoreVehicleRequest` e `UpdateVehicleRequest` com validação completa: nome/marca/modelo obrigatórios; anos entre 1900 e o próximo ano; `model_year >= manufacture_year`; placa nula ou no formato antigo/Mercosul, única ignorando o próprio registro; odômetros e valores não negativos; data de venda não futura e não anterior à aquisição.
  - Exigir `sale_date` e `sale_price_cents` quando `status = sold`; permitir veículo vendido sem despesas registradas.
  - Criar `VehicleController` resource com index (busca por nome/placa/marca/modelo, filtros de status/categoria/arquivados, paginação de 12), show, store, update, destroy (arquivar) e restore.
  - Arquivar não apaga despesas/histórico; restaurar reativa o veículo.
  - Criar testes de feature: criação ativa e vendida, validações, placa duplicada, atualização, arquivamento/restauração, filtros, paginação e veículo histórico de 2023.
  - Critérios de aceite: todos os endpoints respondem corretamente; nenhum registro é apagado fisicamente sem confirmação; mensagens de erro em português.
  - Validação: `php artisan test --filter=Vehicle`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
