# Item 4.7 — Relatórios 6, 7, 9 e 10

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 7 de 8 da fase · tarefa 26 de 37 da sequência
**Pré-requisito:** `tarefa25.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.7 — Relatórios 6, 7, 9 e 10**
  - Implementar: histórico de abastecimentos e consumo, despesas previstas vs pagas, custo total de propriedade e resultado financeiro na venda.
  - Consumo deve respeitar a regra de tanque cheio e indicar dados insuficientes; previstas e pagas em colunas/blocos separados.
  - TCO e resultado de venda por veículo, com detalhamento de compra, despesas operacionais e venda.
  - Criar testes de consistência com os cálculos do serviço e de veículos vendidos.
  - Critérios de aceite: nenhuma previsão contamina o realizado; resultado de venda não inclui FIPE.
  - Validação: `php artisan test --filter=Report`.
  - Observação: o relatório 8 (evolução do valor FIPE) é entregue no item 5.5, após a integração.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Relatórios 6, 7, 9 e 10
- Branch: `tarefa26/1-relatorios-6-7-9-10`
- Commit: `b1d5fd7`
- O que foi feito: Novos relatórios sobre a base da tarefa 25: 6 abastecimentos e consumo (`FuelHistoryReport`, regra do tanque cheio, km/l e custo por km ou vazio quando faltam dados, paginado), 7 previstas vs pagas (`ForecastVsPaidReport`, colunas e totais separados, canceladas fora, gráfico de barras), 9 custo total de propriedade (`OwnershipCostReport`, compra em coluna própria + despesas operacionais, custo médio mensal e custo por km) e 10 resultado na venda (`SaleResultReport`, só vendidos, sem FIPE). `Report::adjustFilters()` permite que TCO e resultado sempre incluam vendidos; novo tipo de coluna `price` (R$ com 3 casas) em `reportFormat.js`. A página `Reports/Show.vue` é genérica e só ajusta quais filtros aparecem.
- Validação: `php artisan test` (253 testes, incluindo `--filter=Report`: 47, com conferência contra `FinancialQueries`, contra a página de abastecimentos e cálculo manual de TCO/resultado), `npm test` (32) e `npm run build` ok.
- Observações: Relatórios 9 e 10 não dependem de período (cobrem toda a propriedade) e não mostram o filtro de período. Na previsão vs pago, pagas entram pela data de pagamento e previstas pelo vencimento, como em `FinancialQueries::forecastVsPaid`. O relatório 8 (evolução FIPE) fica para a tarefa 32. Interação visual não exercitada no navegador nesta tarefa.
