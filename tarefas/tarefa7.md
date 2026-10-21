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

---

## Realizado

### Item 1 — 2.2 — CRUD de veículos e arquivamento no backend
- Branch: `tarefa7/1-crud-veiculos-backend`
- Commit: `b356fa7`
- O que foi feito:
  - `app/Http/Requests/StoreVehicleRequest.php`: validação completa — nome/marca/modelo obrigatórios; anos entre 1900 e o próximo ano; `model_year >= manufacture_year`; `category`, `fuel` e `status` pelos enums; placa opcional nos formatos antigo (`ABC1234`) e Mercosul (`ABC1D23`), normalizada em `prepareForValidation` (maiúsculas, sem separadores) e única ignorando o próprio registro; `odometer`, `acquisition_odometer`, `purchase_price_cents` e `sale_price_cents` não negativos; `sale_date` não futura e não anterior à aquisição; `sale_date` e `sale_price_cents` obrigatórios quando `status = sold`. `UpdateVehicleRequest` reaproveita as regras.
  - `app/Http/Controllers/VehicleController.php`: `index` com busca por nome/placa/marca/modelo, filtros de status/categoria/arquivados e paginação de 12 (com `withQueryString`); `show`; `store`; `update`; `destroy` (arquivamento via soft delete) e `restore`.
  - `routes/web.php`: `Route::resource('vehicles', ...)` exceto `create`/`edit` (fora do escopo deste item) + `PATCH vehicles/{vehicle}/restore` com `withTrashed()`.
  - Arquivar não apaga nada: o `destroy` apenas faz soft delete, então despesas e histórico permanecem; `restore` reativa o veículo e ele reaparece na listagem padrão.
  - `lang/pt_BR/validation.php` e `lang/pt_BR/pagination.php` com mensagens em português, incluindo `custom` para "ano do modelo" e "data de venda", e `attributes` traduzindo os nomes dos campos; `APP_LOCALE=pt_BR`/`APP_FALLBACK_LOCALE=en` no `phpunit.xml` para mensagens determinísticas nos testes.
  - `resources/js/pages/Vehicles/Show.vue`: página de detalhe mínima (nome, marca, modelo, versão, anos, placa, cor, odômetro, aquisição e preço de compra). Necessária para o endpoint `show`; o restante da UI vem no item 2.4.
  - `tests/Feature/VehicleControllerTest.php`: 21 testes de feature cobrindo criação ativa e vendida, placa opcional/normalizada/duplicada, campos obrigatórios, faixa de anos, `model_year`, formatos de placa, valores negativos, data de venda (futura e anterior à aquisição), exigência de data/preço em `sold`, atualização (inclusive placa de outro veículo), arquivamento/restauração, detalhe, filtros, paginação de 12 e veículo histórico de 2023.
  - `tests/Feature/NavigationTest.php`: passou a usar `RefreshDatabase`, já que `/vehicles` agora consulta o banco.
- Validação:
  - `php artisan test --filter=Vehicle` → 29 testes / 202 asserções, todos passando.
  - `php artisan test` → 35 testes / 252 asserções, todos passando.
  - `php artisan route:list --path=vehicles` → 6 rotas (`index`, `store`, `show`, `update`, `destroy`, `restore`).
  - `./vendor/bin/pint` → sem alterações pendentes.
  - `npm run build` → build concluído (inclui `Show.vue`).
- Observações:
  - `resource` exclui `create`/`edit` porque formulários são o item 2.5; quando ele chegar, basta remover a exclusão.
  - A validação de placa única considera também veículos arquivados, alinhada ao índice `unique` do banco.
  - Em produção o `.env` precisa de `APP_LOCALE=pt_BR` para as mensagens aparecerem em português (o `.env` local continua em `en` e não foi versionado).
  - Esta PR parte de `review/tarefa6` (Fase 2 empilhada).
