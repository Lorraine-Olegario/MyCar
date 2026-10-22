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

---

## Realizado

### Item 1 — Componentes de indicadores, gráficos e filtros
- Branch: `tarefa21/1-componentes-indicadores-graficos`
- Commit: `16a7648`
- O que foi feito: Instalados `chart.js` e `vue-chartjs`. Componentes em `resources/js/components/reporting`: `KpiCard`, `LineTrendChart`, `BarChart`, `DonutChart`, `ChartCard` (estados vazio/carregando/erro da Fase 1, alternância gráfico/tabela e tabela sempre no DOM para leitores de tela), `PeriodFilter`, `VehicleFilter`, `CategoryFilter` e `DataCompletenessNotice`. O Chart.js é carregado por import dinâmico (chunk separado de ~190 kB) com fallback para a tabela. Utilitários `chartData.js` (paleta, tooltip em R$, opções) e `filterQuery.js`, e composable `useUrlFilters` (filtros na query string).
- Validação: `npm test` (28), `npm run build` e `php artisan test` (194) ok. Conferência visual no navegador com uma página temporária (removida antes do commit): desktop com gráfico de linha, barras, rosca, estado vazio, KPIs e aviso; mobile 375px sem rolagem horizontal; alternância para tabela.
- Observações: Os componentes ainda não estão em nenhuma página (entram no dashboard, tarefa 22). A resolução dos presets de período (`last_12_months`, `this_month`, `this_year`, `last_year`, `all`, `custom`) fica no backend, para web e CSV usarem a mesma regra. Sem @vue/test-utils: os testes automatizados cobrem apenas os utilitários.
