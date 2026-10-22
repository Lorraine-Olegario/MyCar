# Item 4.5 — Página do veículo: históricos, documentos e vencimentos

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 5 de 8 da fase · tarefa 24 de 37 da sequência
**Pré-requisito:** `tarefa23.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.5 — Página do veículo: históricos, documentos e vencimentos**
  - Abas/seções de despesas, abastecimentos (com km/l e custo/km), manutenções, recorrências e FIPE (placeholder até a Fase 5).
  - Histórico de documentos e vencimentos a partir de recorrências e manutenções, com destaque para atrasados e próximos.
  - Listagens paginadas e filtráveis por período/categoria, mantendo o período selecionado na página.
  - Links para criar/editar registros já existentes, sem duplicar formulários.
  - Critérios de aceite: navegação entre seções preserva o veículo; nenhuma lista quebra em mobile; dados antigos acessíveis.
  - Validação: testes de feature e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Página do veículo: históricos, documentos e vencimentos
- Branch: `tarefa24/1-veiculo-historicos-vencimentos`
- Commit: `36793f3`
- O que foi feito: `VehicleHistory` (domínio) monta só a aba ativa da página do veículo, com paginação de 15 e filtros de período (todo o histórico / período selecionado / personalizado) e categoria: despesas, abastecimentos (km/l e custo/km ou "Sem dados suficientes"), manutenções, recorrências, documentos e vencimentos (recorrências + próximas trocas, atrasados e próximos em destaque) e FIPE (placeholder). `VehicleHistory.vue` substitui as abas provisórias; `financial` e `history` viraram props lazy. O ano/mês do bloco financeiro e a aba/filtros ativos são preservados ao navegar. Links de edição para os formulários existentes via `?edit=ID` em despesas, abastecimentos, manutenções e recorrências (sem duplicar formulários), e links de criação filtrados pelo veículo.
- Validação: `php artisan test` (226 testes, incluindo VehicleHistoryTabsTest: 11) e `npm run build` ok. No navegador (Docker): página do veículo sem erros de console, troca de aba preservando `year` e `scope` na URL, vencimentos em destaque e lista de documentos e vencimentos.
- Observações: As listas começam em "Todo o histórico" (dados antigos acessíveis); o período selecionado no bloco financeiro é uma opção do filtro. Previsões futuras de recorrências (até ~12 meses) aparecem na lista de vencimentos e ficam sinalizadas como "Previsto". Layout mobile não foi verificado visualmente nesta tarefa.
