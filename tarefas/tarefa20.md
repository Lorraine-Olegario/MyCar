# Item 4.1 — Serviço único de consultas financeiras

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 1 de 8 da fase · tarefa 20 de 37 da sequência
**Pré-requisito:** `tarefa19.md` concluída, revisada e mergeada na branch base; é o último item da fase anterior.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.1 — Serviço único de consultas financeiras**
  - Criar `app/Domain/Reporting` com objetos de período e consultas reutilizáveis: totais por período, série mensal, série anual, gastos por categoria, gastos por veículo, previsto vs pago e próximos vencimentos.
  - Implementar TCO, custo médio mensal, custo por km e resultado de venda por veículo, com regras de veículo vendido/arquivado.
  - Filtrar por veículo, categoria e intervalo inclusivo; aplicar timezone `America/Sao_Paulo`; evitar N+1 com `with`/agregações no banco.
  - Retornar metadados de completude do histórico (`is_complete`, `tracking_start_date`) e de ausência de dados.
  - Criar testes com fixtures de vários anos: limites de mês/ano, ano bissexto, período sem dados, veículo vendido, veículo arquivado, despesa retroativa e recorrência prevista vs paga.
  - Critérios de aceite: todos os indicadores usam a mesma fonte; nenhum total mistura previsão com pagamento; TCO e resultado conferem com cálculo manual.
  - Validação: `php artisan test --filter=Reporting`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Serviço único de consultas financeiras
- Branch: `tarefa20/1-servico-consultas-financeiras`
- Commit: `e389e84`
- O que foi feito: `app/Domain/Reporting`: `Period` (intervalo inclusivo, mês, ano, últimos N meses, ilimitado), `ReportFilters` e `FinancialQueries` com totais, série mensal e anual (com meses/anos vazios preenchidos), gastos por categoria e por veículo, previsto vs pago, próximos vencimentos, completude do histórico (`is_complete`, `tracking_start_date`, `has_data`) e `vehicleSummary` (TCO, custo médio mensal, média operacional, custo por km, resultado de venda). Agregações no banco, sem N+1; escopo: sem veículo só ativos não arquivados, vendidos limitados a aquisição→venda.
- Validação: `php artisan test` (194 testes, incluindo `--filter=Reporting`: 18, com fixtures de 2021 a 2026, ano bissexto, vendido, arquivado e conferência manual de TCO/resultado) ok.
- Observações: Meses de propriedade = meses completos entre aquisição e venda/hoje, mínimo 1. Despesas com data futura entram no realizado (a regra da Fase 3 aceita datas futuras). As séries são agrupadas no PHP a partir de totais diários do banco, para não depender de funções de data do SQLite. As datas do veículo são gravadas com hora (cast `date`), por isso a consulta usa `date()` nessas colunas.
