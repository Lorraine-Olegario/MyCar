# Item 2.1 — Estrutura de dados de veículos

**Fase:** 2 — Veículos
**Posição:** item 1 de 6 da fase · tarefa 6 de 37 da sequência
**Pré-requisito:** `tarefa5.md` concluída, revisada e mergeada na branch base; é o último item da fase anterior.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 2 — Veículos") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **2.1 — Estrutura de dados de veículos**
  - Criar migration `vehicles` com: `name`, `brand`, `model`, `version` (nullable), `manufacture_year`, `model_year`, `category`, `plate` (nullable, unique), `color` (nullable), `fuel`, `odometer` (unsigned, default 0), `acquisition_date`, `acquisition_odometer` (nullable), `purchase_price_cents` (nullable), `sale_date` (nullable), `sale_price_cents` (nullable), `photo_path` (nullable), `notes` (nullable), `status` (default `active`), `tracking_start_date` (nullable), timestamps e `deleted_at`.
  - Criar enums PHP `VehicleCategory` (`car`, `motorcycle`, `other`), `VehicleStatus` (`active`, `sold`) e `FuelType` (`gasoline`, `ethanol`, `flex`, `diesel`, `cng`, `electric`, `hybrid`, `other`).
  - Criar model `Vehicle` com casts, `SoftDeletes`, escopos `active`, `sold`, `archived` e accessors de compatibilidade para exibição.
  - Criar factory com dados brasileiros plausíveis (Faker `pt_BR`).
  - Índices para `status`, `category`, `plate` e `deleted_at`; foreign keys que forem adicionadas nas próximas fases devem usar `restrict`/`cascade` de forma explícita.
  - Critérios de aceite: `migrate:fresh` cria a tabela; factory gera veículo válido; escopos se comportam como esperado.
  - Validação: `php artisan migrate:fresh` e teste unitário do model.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
