# Item 6.1 — Testes de integração dos fluxos completos

**Fase:** 6 — Finalização
**Posição:** item 1 de 5 da fase · tarefa 33 de 37 da sequência
**Pré-requisito:** `tarefa32.md` concluída, revisada e mergeada na branch base; é o último item da fase anterior.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 6 — Finalização") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **6.1 — Testes de integração dos fluxos completos**
  - Cobrir o ciclo: cadastrar veículo antigo → lançar despesas retroativas → abastecimentos → recorrência prevista e paga → consulta FIPE (fake) → dashboard/relatórios → vender veículo → resultado da venda.
  - Validar que totais de dashboard, página do veículo e relatórios coincidem para o mesmo filtro.
  - Cobrir cenários de histórico incompleto, veículo arquivado, veículo vendido e ausência de dados.
  - Garantir testes determinísticos (datas fixas/freezing time, `Http::fake`, SQLite em memória) e sem dependência de rede.
  - Critérios de aceite: fluxos críticos cobertos; nenhum teste ignorado ou dependente de ordem.
  - Validação: `composer test` completo.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
