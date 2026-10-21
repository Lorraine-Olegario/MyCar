# Item 4.4 — Página do veículo: visão geral e financeiro

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 4 de 8 da fase · tarefa 23 de 37 da sequência
**Pré-requisito:** `tarefa22.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.4 — Página do veículo: visão geral e financeiro**
  - Cabeçalho com foto, marca/modelo/versão, ano, placa, status, odômetro e período de propriedade.
  - Blocos de aquisição e venda separados; total gasto desde a aquisição; gastos do mês/ano selecionados; gastos por categoria; evolução mensal; custo médio mensal; custo por km; resultado financeiro estimado.
  - Seletor de período com anos anteriores, inclusive antes do início do uso do sistema, marcando histórico incompleto.
  - Quando faltar `acquisition_odometer` ou distância válida, exibir "dados insuficientes" em vez de zero.
  - Criar testes das props e dos cenários de dados incompletos, veículo vendido e veículo sem despesas.
  - Critérios de aceite: todos os números derivam do serviço de relatórios; compra não aparece como despesa operacional.
  - Validação: `php artisan test --filter=VehicleShow`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
