# Item 1.5 — Convenções de domínio, documentação e validação final da fase

**Fase:** 1 — Fundação
**Posição:** item 5 de 5 da fase · tarefa 5 de 37 da sequência
**Pré-requisito:** `tarefa4.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 1 — Fundação") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **1.5 — Convenções de domínio, documentação e validação final da fase**
  - Registrar em `README.md` (ou `docs/arquitetura.md`) as convenções: centavos, datas, timezone, enums, uso de `app/Domain`, limites de controllers/services e política de testes.
  - Configurar `APP_NAME`, locale e faker locale nos arquivos de configuração quando necessário; garantir `php artisan migrate:fresh` funcionando.
  - Atualizar a página inicial com um resumo do MyCar e links para as seções.
  - Revisar `pint`, remover o componente/teste de exemplo remanescente e garantir que nenhum teste seja ignorado.
  - Critérios de aceite: documentação compatível com o código; suíte PHP e Vitest verdes; build limpo; instalação reproduzível a partir do README.
  - Validação: `php artisan migrate:fresh`, `composer test`, `npm test` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Esta é a última tarefa da Fase 1; ao terminar, aguarde orientação antes de iniciar a próxima fase.
