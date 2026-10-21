# Item 4.3 — Dashboard principal

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 3 de 8 da fase · tarefa 22 de 37 da sequência
**Pré-requisito:** `tarefa21.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.3 — Dashboard principal**
  - Cards: veículos ativos, total do mês, total do ano, total histórico, valor FIPE da frota ativa (placeholder "não consultado" até a Fase 5), previsões dos próximos meses.
  - Blocos: próximos vencimentos (seguro, IPVA, documentação, manutenções e recorrências), evolução mensal (últimos 12 meses), distribuição por categoria e comparativo entre veículos.
  - Filtros por veículo e período, refletidos na URL; estado vazio para instalação nova.
  - Deixar explícito que FIPE, compra e despesas são indicadores diferentes; nunca somar FIPE aos gastos.
  - Criar testes de feature das props: contagem de ativos, exclusão de arquivados/vendidos, totais do período, previsões e vencimentos.
  - Critérios de aceite: dashboard abre sem dados e com dados; números batem com os relatórios.
  - Validação: `php artisan test --filter=Dashboard` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Dashboard principal
- Branch: `tarefa22/1-dashboard-principal`
- Commit: `6084944`
- O que foi feito: `DashboardOverview` (domínio) monta os dados só a partir de `FinancialQueries`; `DashboardController` virou delegador. Cards: veículos ativos, total do mês, do ano e histórico, FIPE ("Não consultado" até a Fase 5) e previsões dos próximos 3 meses; indicadores do mês do veículo em destaque (gasto, custo por km, consumo, próxima manutenção); evolução mensal, distribuição por categoria, comparativo entre veículos e próximos vencimentos (recorrências + manutenções); filtros de veículo e período na URL; aviso de histórico incompleto e texto deixando claro que compra, FIPE e despesas são indicadores diferentes. Novos: `Period::fromPreset`, `ReportFilters::fromInput`, `MaintenanceDueQueries`; `UpcomingDueList` entende manutenções. `DatabaseSeeder` agora é idempotente e não usa Faker (rodava mal no container de produção); o entrypoint do Docker semeia as categorias.
- Validação: `php artisan test` (206 testes, incluindo `--filter=Dashboard`: 15) e `npm run build` ok. No navegador (Docker): números conferidos à mão com 7 despesas e 2 recorrências (total R$ 3.530,00, mês R$ 600,00, previsão R$ 3.000,00, custo por km R$ 0,09), filtro de período refletido em `?period=this_month`.
- Observações: Os cards do mês/ano/histórico seguem apenas o filtro de veículo; o período vale para os gráficos e comparativos (indicado na tela). `MaintenanceFactory` agora cria despesa/veículo de forma preguiçosa (antes criava registros extras mesmo com override). Dashboard de instalação nova (sem veículos) esconde os filtros e mostra o convite para cadastrar.
