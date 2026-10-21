# Item 4.8 — Exportação CSV dos relatórios

**Fase:** 4 — Indicadores e relatórios
**Posição:** item 8 de 8 da fase · tarefa 27 de 37 da sequência
**Pré-requisito:** `tarefa26.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 4 — Indicadores e relatórios") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **4.8 — Exportação CSV dos relatórios**
  - Exportar qualquer relatório implementado respeitando os filtros ativos, via resposta em streaming.
  - CSV compatível com Excel pt-BR: UTF-8 com BOM, separador `;`, decimal com vírgula, datas `dd/mm/aaaa`, cabeçalhos claros.
  - Nome de arquivo com relatório e período; não incluir dados sensíveis desnecessários.
  - Criar testes de conteúdo, codificação, separador, filtros e cabeçalhos.
  - Critérios de aceite: arquivo abre corretamente no Excel/LibreOffice e contém exatamente o recorte filtrado.
  - Validação: `php artisan test --filter=CsvExport`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Esta é a última tarefa da Fase 4; ao terminar, aguarde orientação antes de iniciar a próxima fase.
