# Item 3.7 — Interface de abastecimentos

**Fase:** 3 — Despesas
**Posição:** item 7 de 8 da fase · tarefa 18 de 37 da sequência
**Pré-requisito:** `tarefa17.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.7 — Interface de abastecimentos**
  - Página `/fuelings` com filtros de veículo e período, totais de litros/valor, preço médio por litro e lista paginada.
  - Formulário com data, odômetro, litros, preço por litro, total calculado, tipo de combustível, posto, tanque cheio e observações.
  - Exibir por abastecimento: km percorridos, km/l e custo por km quando calculáveis; caso contrário, indicar dados insuficientes.
  - Edição e exclusão com confirmação; recálculo automático após alteração; mensagens de erro de odômetro claras.
  - Critérios de aceite: totais mensais/anuais conferem com a lista; validação de odômetro impede cálculos errados; responsivo.
  - Validação: testes de feature e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Interface de abastecimentos
- Branch: `tarefa18/1-interface-abastecimentos`
- Commit: `03077ca`
- O que foi feito: `FuelingController::index` com filtros (veículo, período), paginação de 20, consumo por abastecimento e totais do filtro/mensais/anuais via `FuelingOverview`; página `Fuelings/Index.vue` com resumo (litros, valor, preço médio, consumo), tabelas por mês e por ano, lista com km percorridos, km/l e custo por km (ou "Sem dados suficientes" com o motivo), formulário com total calculado e ajuste manual, aviso de duplicidade com confirmação, mensagens de erro de odômetro e exclusão com confirmação.
- Validação: `php artisan test` (163 testes, incluindo FuelingPagesTest) e `npm run build` ok.
- Observações: O consumo de cada abastecimento usa o histórico completo do veículo, então não muda com o filtro de período (testado). Interação visual não exercitada no navegador nesta tarefa.
