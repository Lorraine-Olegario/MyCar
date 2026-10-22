# Item 5.5 — FIPE nos indicadores e relatórios

**Fase:** 5 — Tabela FIPE
**Posição:** item 5 de 5 da fase · tarefa 32 de 37 da sequência
**Pré-requisito:** `tarefa31.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 5 — Tabela FIPE") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **5.5 — FIPE nos indicadores e relatórios**
  - Dashboard: valor FIPE atualizado dos veículos ativos e valor total de referência da frota, com indicação de quantos veículos têm cotação e quantos estão desatualizados/sem consulta.
  - Página do veículo: valor atual e histórico/variação; não somar FIPE a gastos.
  - Relatório 8: evolução do valor FIPE por veículo, com filtros de período e aviso de lacunas entre consultas.
  - Criar testes de agregação (apenas veículos ativos, apenas cotações atuais) e de separação entre FIPE e despesas.
  - Critérios de aceite: indicador de frota não inclui veículos vendidos/arquivados nem cotações antigas como se fossem atuais; relatório mostra a evolução real das consultas.
  - Validação: `php artisan test --filter=FipeReporting` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Esta é a última tarefa da Fase 5; ao terminar, aguarde orientação antes de iniciar a próxima fase.

---

## Realizado

### Item 1 — FIPE nos indicadores e relatórios
- Branch: `tarefa32/1-fipe-indicadores-relatorios`
- Commit: `b238dd8`
- O que foi feito: `FipeFleetSummary`: valor FIPE da frota considerando só veículos ativos e cotações atuais (cotações com mais de 35 dias são contadas como desatualizadas e ficam fora da soma; sem consulta também é contado). Dashboard: card de FIPE com "X de Y com cotação atual · N desatualizados · M sem consulta" e total separado dos gastos. Página do veículo: variação em R$ e % em relação à consulta anterior. Relatório 8 (`FipeEvolutionReport`): consultas reais por veículo, variação (calculada sobre a consulta anterior real, mesmo fora do período), aviso de lacunas entre meses de referência, gráfico de linhas e exportação CSV.
- Validação: `php artisan test` (320 testes, incluindo `--filter=FipeReporting`: 12) e `npm run build` ok.
- Observações: A soma da frota usa só cotações atuais (limite configurável `FIPE_STALE_DAYS`). O filtro de veículo do dashboard aplica ao card de FIPE. FIPE não aparece em nenhuma coluna ou total de despesas, TCO ou resultado de venda (testado). Esta tarefa fecha a Fase 5.
