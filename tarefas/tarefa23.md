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

---

## Realizado

### Item 1 — Página do veículo: visão geral e financeiro
- Branch: `tarefa23/1-veiculo-visao-financeira`
- Commit: `53a5ffe`
- O que foi feito: `VehicleFinancialOverview` (domínio, sobre `FinancialQueries`) e prop `financial` em `VehicleController::show`; componente `VehicleFinancial.vue` na página do veículo com seletor de ano e mês (anos anteriores marcados como "histórico incompleto"), despesas desde a aquisição, TCO, gasto do ano e do mês selecionados, custo médio mensal (total e operacional), custo por km (ou "Dados insuficientes"), resultado da venda, evolução mensal e distribuição por categoria. Cabeçalho ganhou o período de propriedade; aquisição e venda continuam em blocos separados.
- Validação: `php artisan test` (215 testes, incluindo `--filter=VehicleShow`: 9) e `npm run build` ok.
- Observações: O ano padrão é o atual (ou o da venda, para vendidos). A compra aparece apenas no TCO e no bloco de aquisição, nunca nas despesas por período/categoria. Resultado financeiro de veículo ativo fica em branco até a venda (a FIPE só entra na Fase 5, separada). As abas de históricos continuam com os textos provisórios até a tarefa 24.
