# Item 5.4 — Interface de seleção, consulta e histórico FIPE

**Fase:** 5 — Tabela FIPE
**Posição:** item 4 de 5 da fase · tarefa 31 de 37 da sequência
**Pré-requisito:** `tarefa30.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 5 — Tabela FIPE") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **5.4 — Interface de seleção, consulta e histórico FIPE**
  - Na página do veículo, selecionar marca → modelo → ano/versão em cascata, carregando opções da API com estados de carregamento e erro.
  - Exibir card com valor em R$, mês/ano de referência, marca, modelo, ano do modelo, código FIPE e data da última consulta.
  - Botão "Atualizar cotação" com feedback; em erro, mensagem clara, botão "Tentar novamente" e aviso quando o valor exibido estiver desatualizado.
  - Histórico de cotações em tabela/gráfico, com data da consulta e mês de referência; incluir aviso de que FIPE é referência, não preço garantido de venda.
  - Criar testes de feature das props e estados de erro; conferir responsividade.
  - Critérios de aceite: usuário consegue selecionar a versão correta; falha não apaga o último valor; datas e moeda em pt-BR.
  - Validação: `php artisan test --filter=FipePage` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
