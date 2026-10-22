# Item 3.3 — Interface de despesas

**Fase:** 3 — Despesas
**Posição:** item 3 de 8 da fase · tarefa 14 de 37 da sequência
**Pré-requisito:** `tarefa13.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.3 — Interface de despesas**
  - Página `/expenses` com tabela/cards, filtros de veículo, categoria, período e busca, paginação e total do filtro atual.
  - Formulário de criar/editar com veículo, categoria, descrição, valor, data, odômetro, fornecedor, forma de pagamento, comprovante e observações.
  - Comprovante com preview/download; exclusão com confirmação; mensagens de sucesso/erro; estados vazios e de carregamento.
  - Exibir indicador quando a despesa veio de recorrência e aviso quando o período consultado é anterior ao início do acompanhamento do veículo.
  - Critérios de aceite: registrar despesa de anos anteriores pela interface; filtros combinam corretamente; layout responsivo.
  - Validação: testes de feature das props e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Interface de despesas
- Branch: `tarefa14/1-interface-despesas`
- Commit: `a6fdcf9`
- O que foi feito: Página `Expenses/Index.vue`: lista responsiva com total do filtro, filtros combinados (veículo, categoria, período, busca), paginação, formulário de criar/editar em modal (inclusive datas antigas), comprovante com visualizar/baixar (`?download=1`), exclusão com confirmação, estados vazio/carregando, badge de recorrência e avisos de histórico incompleto (período anterior a `tracking_start_date`). `BaseModal` ganhou a prop `size`; as opções de veículos passam a incluir `tracking_start_date`.
- Validação: `php artisan test` (107 testes, incluindo ExpensePagesTest) e `npm run build` ok; `npm test` (20) ok.
- Observações: Corrigido junto, por ser o mesmo padrão: PUT com arquivo não é lido pelo PHP, então o upload na edição usa `_method=put` via POST (despesas e também a foto em `Vehicles/Form.vue`, que antes enviava PUT multipart). Apesar de testado só por props/feature e build, a interação no navegador não foi exercitada nesta tarefa.
