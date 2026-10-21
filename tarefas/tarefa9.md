# Item 2.4 — Listagem e detalhe do veículo (Vue)

**Fase:** 2 — Veículos
**Posição:** item 4 de 6 da fase · tarefa 9 de 37 da sequência
**Pré-requisito:** `tarefa8.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 2 — Veículos") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **2.4 — Listagem e detalhe do veículo (Vue)**
  - Página `/vehicles` com cards/fotos (placeholder quando ausente), nome, marca/modelo/versão, ano, placa, status, odômetro, categoria e ações.
  - Filtros por busca, status, categoria e arquivados; paginação preservando query string; estado vazio e estado de carregamento.
  - Página `/vehicles/{id}` com foto, dados principais, bloco separado de aquisição (data, valor, odômetro) e venda (quando houver), observações e status.
  - Exibir o resultado de venda apenas quando compra e venda existirem, deixando explícito que despesas operacionais entram na Fase 3/4; não incluir compra no total de despesas.
  - Criar abas/placeholders para despesas, abastecimentos, manutenções e FIPE, a serem preenchidas nas próximas fases.
  - Critérios de aceite: navegação e filtros funcionam em desktop e mobile; dados monetários/datas formatados em pt-BR.
  - Validação: testes de feature das props Inertia e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
