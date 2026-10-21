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
