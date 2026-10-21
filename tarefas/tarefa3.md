# Item 1.3 — Layout base, navegação e páginas provisórias

**Fase:** 1 — Fundação
**Posição:** item 3 de 5 da fase · tarefa 3 de 37 da sequência
**Pré-requisito:** `tarefa2.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 1 — Fundação") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **1.3 — Layout base, navegação e páginas provisórias**
  - Criar `AppLayout.vue` com menu lateral (Dashboard, Veículos, Despesas, Abastecimentos, Relatórios, Categorias), cabeçalho, área de conteúdo e versão mobile em drawer.
  - Navegação com estado ativo, `aria-current`, foco visível e link "pular para o conteúdo"; layout sem rolagem horizontal em 360 px.
  - Criar rotas nomeadas e páginas Vue provisórias para cada seção, além de página 404 Inertia. Manter os nomes das rotas estáveis para as fases seguintes.
  - Critérios de aceite: todas as rotas provisórias respondem 200; menu funciona em desktop e mobile; páginas provisórias usam estado vazio claro ("em construção").
  - Validação: teste de feature para cada rota, `npm run build` e conferência visual em 360 px e 1280 px.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
