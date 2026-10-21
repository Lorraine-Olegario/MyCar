# Item 4.2 — Componentes de indicadores, gráficos e filtros

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 2 de 8 da fase · tarefa 21 de 37 da sequência
**Pré-requisito:** `tarefa20.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.2 — Componentes de indicadores, gráficos e filtros**
  - Criar `KpiCard`, `LineTrendChart`, `BarChart`, `DonutChart`, `ChartCard`, `PeriodFilter`, `VehicleFilter`, `CategoryFilter` e `DataCompletenessNotice`.
  - Instalar `chart.js` e `vue-chartjs` somente agora, com import dinâmico e fallback em tabela acessível.
  - Gráficos responsivos, com tooltip em R$, legenda clara e paleta consistente; filtros preservam estado na query string.
  - Reutilizar estados vazio/carregando/erro da Fase 1; não duplicar lógica de formatação.
  - Critérios de aceite: componentes funcionam sem dados; não há layout shift; navegação por teclado e leitores de tela recebem rótulos.
  - Validação: `npm test` (utilitários), `npm run build` e conferência visual em desktop/mobile.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
