# Item 5.2 — Persistência da seleção FIPE e do histórico de cotações

**Fase:** 5 — Tabela FIPE
**Posição:** item 2 de 5 da fase · tarefa 29 de 37 da sequência
**Pré-requisito:** `tarefa28.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 5 — Tabela FIPE") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **5.2 — Persistência da seleção FIPE e do histórico de cotações**
  - Adicionar ao veículo: `fipe_brand_id`, `fipe_model_id`, `fipe_year_id`, `fipe_code`, `fipe_value_cents`, `fipe_reference_month`, `fipe_consulted_at`.
  - Criar `fipe_quotes`: `vehicle_id`, `fipe_code`, `value_cents`, `reference_month`, `brand`, `model`, `model_year`, `fuel`, `consulted_at`, `payload` (JSON nullable) e timestamps; índice por veículo/data.
  - Criar model, relação `fipeQuotes` e accessor da cotação mais recente; não usar unique que impeça histórico.
  - Criar factory e testes de persistência, ordenação e valor mais recente.
  - Critérios de aceite: histórico guarda cada consulta; cotação atual sempre corresponde ao último registro persistido.
  - Validação: `php artisan migrate:fresh` e `php artisan test --filter=FipeQuote`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Persistência da seleção FIPE e do histórico de cotações
- Branch: `tarefa29/1-persistencia-fipe`
- Commit: `b3fad26`
- O que foi feito: Migration que adiciona ao veículo `fipe_brand_id`, `fipe_model_id`, `fipe_year_id`, `fipe_code`, `fipe_value_cents`, `fipe_reference_month`, `fipe_consulted_at` (e `fipe_vehicle_type`) e cria `fipe_quotes` (índice veículo/data, payload JSON, sem unique que impeça histórico). Model `FipeQuote` + factory, relações `Vehicle::fipeQuotes()` (mais recente primeiro) e `Vehicle::latestFipeQuote()` (desempate por id).
- Validação: `php artisan migrate:fresh` (em SQLite temporário) e `php artisan test` (285 testes, incluindo `--filter=FipeQuote`: 9) ok.
- Observações: Coluna extra `fipe_vehicle_type` (cars/motorcycles/trucks), necessária para veículos da categoria "outro", em que o usuário escolhe o tipo. Os campos `fipe_*` do veículo não ficam no `fillable`: só o serviço (tarefa 30) os grava. As cotações persistem se o veículo for arquivado.
