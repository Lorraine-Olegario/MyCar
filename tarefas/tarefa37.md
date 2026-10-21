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
