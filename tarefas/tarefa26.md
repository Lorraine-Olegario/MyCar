# Item 4.7 — Relatórios 6, 7, 9 e 10

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 7 de 8 da fase · tarefa 26 de 37 da sequência
**Pré-requisito:** `tarefa25.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.7 — Relatórios 6, 7, 9 e 10**
  - Implementar: histórico de abastecimentos e consumo, despesas previstas vs pagas, custo total de propriedade e resultado financeiro na venda.
  - Consumo deve respeitar a regra de tanque cheio e indicar dados insuficientes; previstas e pagas em colunas/blocos separados.
  - TCO e resultado de venda por veículo, com detalhamento de compra, despesas operacionais e venda.
  - Criar testes de consistência com os cálculos do serviço e de veículos vendidos.
  - Critérios de aceite: nenhuma previsão contamina o realizado; resultado de venda não inclui FIPE.
  - Validação: `php artisan test --filter=Report`.
  - Observação: o relatório 8 (evolução do valor FIPE) é entregue no item 5.5, após a integração.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
