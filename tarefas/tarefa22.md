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
