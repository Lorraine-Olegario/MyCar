# Item 4.6 — Relatórios 1 a 5

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 6 de 8 da fase · tarefa 25 de 37 da sequência
**Pré-requisito:** `tarefa24.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.6 — Relatórios 1 a 5**
  - Implementar rotas e páginas para: gastos mensais por veículo, gastos anuais por veículo, histórico completo de despesas, comparativo entre veículos e gastos por categoria.
  - Todos com filtros de veículo, categoria e período; totais do filtro; tabela e gráfico quando fizer sentido; paginação nas listagens.
  - Deixar claro o período e os filtros aplicados no cabeçalho de cada relatório.
  - Criar testes de totais e filtros combinados, incluindo despesas retroativas.
  - Critérios de aceite: relatórios e dashboard mostram os mesmos valores para o mesmo filtro.
  - Validação: `php artisan test --filter=Report`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Relatórios 1 a 5
- Branch: `tarefa25/1-relatorios-1-a-5`
- Commit: `69060e9`
- O que foi feito: Base declarativa de relatórios em `app/Domain/Reporting` (`Report` com colunas tipadas, linhas brutas, totais e gráfico; `ReportRegistry`; `ReportBuilder` com a descrição dos filtros aplicados) e os relatórios: 1 gastos mensais por veículo, 2 gastos anuais por veículo, 3 histórico completo de despesas (paginado de 20 em 20), 4 comparativo entre veículos e 5 gastos por categoria. `ReportController` e páginas `Reports/Index.vue` e `Reports/Show.vue` com filtros de veículo, categoria, período e inclusão de vendidos/arquivados, cabeçalho com os filtros aplicados, totais, tabela e gráfico (barras ou rosca). Novas consultas `seriesByVehicle` e `expenseRows` em `FinancialQueries`; utilitário `reportFormat.js` com testes.
- Validação: `php artisan test` (240 testes, incluindo `--filter=Report`: 34, com teste de que relatório e dashboard mostram os mesmos valores para o mesmo filtro e de despesas retroativas), `npm test` (32) e `npm run build` ok.
- Observações: A estrutura (colunas tipadas) foi pensada para a exportação CSV da tarefa 27: o botão de exportar entra lá. Gastos por veículo mostram uma coluna por veículo do recorte (com vendidos/arquivados só se pedidos). Relatórios longos são paginados na tela em memória; a exportação levará todas as linhas. Interação visual não exercitada no navegador nesta tarefa.
