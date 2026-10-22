# Item 3.6 — Abastecimentos: domínio e cálculos

**Fase:** 3 — Despesas
**Posição:** item 6 de 8 da fase · tarefa 17 de 37 da sequência
**Pré-requisito:** `tarefa16.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.6 — Abastecimentos: domínio e cálculos**
  - Criar migration `fuelings`: `vehicle_id` (FK, restrict), `fueled_on` (date), `odometer`, `liters` (decimal 8,3), `price_per_liter` (decimal 8,3), `total_cents`, `fuel_type`, `station` (nullable), `full_tank` (boolean), `notes` (nullable), timestamps; índices por veículo/data e odômetro.
  - Validar odômetro não negativo e consistência por veículo: não pode ser menor que abastecimentos anteriores nem maior que o próximo já registrado na mesma data; impedir duplicidade exata (veículo + data + odômetro + litros) sem confirmação.
  - Calcular total automaticamente quando litros e preço forem informados, permitindo ajuste manual; atualizar `vehicles.odometer` para o maior odômetro conhecido.
  - Implementar `FuelConsumptionCalculator` em `app/Domain/Fuel`: distância e litros desde o último tanque cheio; km/l apenas entre cheios consecutivos; custo por km do mesmo intervalo; preço médio ponderado por litro.
  - Criar testes de primeiro abastecimento, tanque parcial, dois cheios consecutivos, odômetro inconsistente, mesmo dia, ausência de dados (retornar `null`, não zero) e custo por km.
  - Critérios de aceite: nunca exibir consumo inventado; dados insuficientes aparecem como "sem dados suficientes".
  - Validação: `php artisan test --filter=Fuel`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Abastecimentos: domínio e cálculos
- Branch: `tarefa17/1-abastecimentos-dominio`
- Commit: `d4151b2`
- O que foi feito: Migration `fuelings` (decimal 8,3, índices por veículo/data e odômetro), model/factory `Fueling`, `FuelConsumptionCalculator` em `app/Domain/Fuel` (km/l só entre cheios consecutivos, parciais somados ao próximo cheio, custo por km, preço médio por litro; `null` com motivo quando faltam dados), `FuelingRecorder` (total automático com ajuste manual, preço derivado do total, `vehicles.odometer` = maior odômetro conhecido), `StoreFuelingRequest`/`UpdateFuelingRequest` (odômetro coerente por veículo, duplicidade exata com confirmação) e `FuelingController` (store/update/destroy).
- Validação: `php artisan test` (157 testes, incluindo `--filter=Fuel`: 21) ok.
- Observações: Interpretação da regra de odômetro: comparação cronológica por data (anteriores ≤ odômetro ≤ posteriores); abastecimentos no mesmo dia são aceitos em qualquer ordem. O odômetro do veículo só sobe (excluir um abastecimento não o reduz). Listagem/UI ficam na tarefa 18; `/fuelings` segue com a página provisória.
