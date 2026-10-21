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

---

## Realizado

### Item 1 — Testes de integração dos fluxos completos
- Branch: `tarefa33/1-testes-integracao`
- Commit: `40e9a01`
- O que foi feito: `tests/Feature/FullFlowTest.php`: ciclo completo usando só as rotas da aplicação (veículo antigo de 2021 → despesas retroativas de 2022/2023/2025 → dois abastecimentos de tanque cheio → recorrência anual criada e paga → consulta FIPE fake → dashboard e relatórios → venda → arquivamento e restauração). Verifica que dashboard, página do veículo e relatórios 3, 4, 5 e 2 dão o mesmo total para o mesmo filtro, que consumo e FIPE ficam separados dos gastos, histórico incompleto antes do início do acompanhamento, resultado da venda conferido à mão (63 meses; venda − compra − despesas), exclusão dos indicadores correntes após venda/arquivamento sem perder histórico, e que todas as telas e os 10 relatórios (inclusive CSV) abrem sem dados.
- Validação: `composer test` completo: 326 testes, 3648 asserções, verdes; `vendor/bin/phpunit --order-by=random` também verde (independente de ordem). Sem testes ignorados/incompletos (busca por skip/incomplete); datas fixas com `Carbon::setTestNow`, `Http::fake` com `preventStrayRequests` e SQLite em memória.
- Observações: Abastecimentos não entram nos totais de despesas (são um registro à parte, como já era nas tarefas 17/18); o teste documenta isso ao comparar o relatório de consumo com o total de despesas. Relatórios em matriz (mensal/anual) sem dados mostram os meses do período com valor zero e nenhuma coluna de veículo.
