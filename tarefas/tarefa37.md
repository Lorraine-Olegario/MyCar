# Item 6.5 — Regressão final e checklist do enunciado

**Fase:** 6 — Finalização
**Posição:** item 5 de 5 da fase · tarefa 37 de 37 da sequência
**Pré-requisito:** `tarefa36.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 6 — Finalização") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **6.5 — Regressão final e checklist do enunciado**
  - Rodar `php artisan migrate:fresh --seed`, `composer test`, `npm test`, `npm run build` e `pint --test`.
  - Criar `docs/checklist.md` com os requisitos das seções 2 a 13 do enunciado, status e onde foram implementados/testados.
  - Remover TODOs, código morto, imports não usados e testes incompletos; conferir logs sem erros.
  - Validar manualmente um roteiro com dois veículos (um atual e um vendido), despesas em anos diferentes e uma recorrência anual.
  - Critérios de aceite: suíte e build verdes; checklist sem requisito obrigatório em aberto; nenhuma pendência crítica escondida.
  - Validação: comandos acima + roteiro manual registrado no checklist.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Esta é a última tarefa da Fase 6; ao terminar, aguarde orientação antes de iniciar a próxima fase.

---

## Realizado

### Item 1 — Regressão final e checklist do enunciado
- Branch: `tarefa37/1-regressao-final`
- Commit: `1126130`
- O que foi feito: Regressão completa, `docs/checklist.md` (requisitos agrupados nas seções 2 a 13, com status, onde foram implementados e testados, resultados da regressão e roteiro manual registrado) e três correções encontradas na regressão: os testes passam a gravar log no canal `null` (o teste de segurança lança exceção de propósito e poluía o `laravel.log`); a tela de relatórios deixa de exibir os filtros que o relatório não usa (ex.: "Período" no TCO e no resultado de venda); teste novo para isso.
- Validação: `php artisan migrate:fresh --seed` (SQLite temporário: 10 migrations, 17 categorias), `composer test` (348 testes, 3884 asserções), `npm test` (32), `npm run build` e `vendor/bin/pint --test` ok; sem TODO/FIXME/dump, sem testes ignorados, sem erros no log do container. Roteiro manual no Docker com dois veículos (atual e vendido), despesas de vários anos e recorrência anual, com todos os números conferidos à mão e registrados no checklist.
- Observações: O enunciado original não está no repositório; a checklist foi derivada de `tarefas/README.md` e dos 37 arquivos de tarefa (indicado no topo do documento). Não há requisito obrigatório em aberto; pendências não bloqueantes: teste manual com leitor de tela e o fato de a consulta FIPE real ter sido feita manualmente uma única vez.
