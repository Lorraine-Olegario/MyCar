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

---

## Realizado

### Item 1 — 2.1 — Estrutura de dados de veículos
- Branch: `tarefa6/1-estrutura-dados-veiculos`
- Commit: `a33eb63`
- O que foi feito:
  - Migration `create_vehicles_table` com todos os campos pedidos (`name`, `brand`, `model`, `version`, `manufacture_year`, `model_year`, `category`, `plate` nullable/unique, `color`, `fuel`, `odometer`, `acquisition_date`, `acquisition_odometer`, `purchase_price_cents`, `sale_date`, `sale_price_cents`, `photo_path`, `notes`, `status`, `tracking_start_date`, timestamps e `deleted_at`) e índices em `status`, `category` e `deleted_at` (o índice de `plate` vem do `unique`).
  - Enums PHP em `app/Enums`: `VehicleCategory` (car/motorcycle/other), `VehicleStatus` (active/sold) e `FuelType` (gasoline/ethanol/flex/diesel/cng/electric/hybrid/other), com `label()` em português.
  - Model `Vehicle` (`app/Models`): `SoftDeletes`, casts dos enums/datas/inteiros, escopos `active`, `sold` e `archived` (soft deleted), normalização da placa na escrita (maiúsculas, sem separadores) e accessors de exibição `full_name` e `formatted_plate` (também expostos em `$appends`).
  - `VehicleFactory` com catálogo de carros/motos brasileiros plausíveis (Faker `pt_BR`) e estados `sold()` e `archived()`.
  - `tests/Unit/VehicleTest.php` cobrindo factory, casts, accessors, normalização de placa, escopos e soft delete. `.gitkeep` de `tests/Unit` removido (o diretório voltou a ter teste real).
- Validação:
  - `php artisan migrate:fresh` → tabela `vehicles` criada.
  - `php artisan test` → 14 testes / 85 asserções, todos passando.
  - `./vendor/bin/pint` aplicado (imports de `Vehicle.php`).
- Observações:
  - Enums ficaram em `app/Enums` (local padrão do `make:enum`), não em `app/Domain`; `docs/arquitetura.md` cita enums mas não fixa pasta — ajusto se você preferir `app/Domain/Vehicles`.
  - `tracking_start_date` é gerado pela factory a partir da data de aquisição (usado nas fases seguintes para sinalizar histórico incompleto).
  - Foreign keys de fases futuras usarão `restrict`/`cascade` explícitos, conforme o item.
  - Esta PR parte da `main` (Fase 1 já mergeada).
