# Item 2.6 — Consulta histórica de veículos vendidos e arquivados

**Fase:** 2 — Veículos
**Posição:** item 6 de 6 da fase · tarefa 11 de 37 da sequência
**Pré-requisito:** `tarefa10.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 2 — Veículos") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **2.6 — Consulta histórica de veículos vendidos e arquivados**
  - Incluir filtro explícito para vendidos e arquivados, com contadores e badge de status.
  - Permitir restaurar veículo arquivado com confirmação; veículos vendidos permanecem consultáveis e não contam como ativos.
  - Exibir na listagem/detalhe aviso quando o veículo foi vendido e o período de propriedade; não esconder histórico.
  - Criar testes de que arquivados ficam fora da listagem padrão, aparecem no filtro, podem ser restaurados e que vendidos continuam acessíveis.
  - Critérios de aceite: nenhum histórico é perdido ao arquivar/vender; filtros deixam claro o que está sendo exibido.
  - Validação: `php artisan test --filter=VehicleHistory`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Esta é a última tarefa da Fase 2; ao terminar, aguarde orientação antes de iniciar a próxima fase.
