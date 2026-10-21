# Item 2.4 — Listagem e detalhe do veículo (Vue)

**Fase:** 2 — Veículos
**Posição:** item 4 de 6 da fase · tarefa 9 de 37 da sequência
**Pré-requisito:** `tarefa8.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 2 — Veículos") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **2.4 — Listagem e detalhe do veículo (Vue)**
  - Página `/vehicles` com cards/fotos (placeholder quando ausente), nome, marca/modelo/versão, ano, placa, status, odômetro, categoria e ações.
  - Filtros por busca, status, categoria e arquivados; paginação preservando query string; estado vazio e estado de carregamento.
  - Página `/vehicles/{id}` com foto, dados principais, bloco separado de aquisição (data, valor, odômetro) e venda (quando houver), observações e status.
  - Exibir o resultado de venda apenas quando compra e venda existirem, deixando explícito que despesas operacionais entram na Fase 3/4; não incluir compra no total de despesas.
  - Criar abas/placeholders para despesas, abastecimentos, manutenções e FIPE, a serem preenchidas nas próximas fases.
  - Critérios de aceite: navegação e filtros funcionam em desktop e mobile; dados monetários/datas formatados em pt-BR.
  - Validação: testes de feature das props Inertia e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — 2.4 — Listagem e detalhe do veículo (Vue)
- Branch: `tarefa9/1-listagem-detalhe-veiculo`
- Commit: `1a8b96b`
- O que foi feito:
  - Backend (props das páginas): `VehicleController` passa `options` com `statuses`, `categories` e `fuels` (valor + rótulo em português vindos dos enums) em `index` e `show`.
  - Model `Vehicle`: novos atributos de apresentação `archived`, `photo_url` (URL da rota controlada `vehicles.photo`, nula sem foto) e `sale_result_cents` (venda − compra, nulo quando falta compra ou venda). `photo_path` entrou em `$hidden`: a interface só conhece a URL da rota controlada, nunca o caminho no disco privado.
  - `resources/js/pages/Vehicles/Index.vue`: cabeçalho com busca (nome/marca/modelo/placa), status, categoria e "somente arquivados"; aplicação via Inertia com `preserveState`, `preserveScroll` e `replace`, preservando a query string; estado de carregamento; contagem de resultados; grade de cards (`sm:2`, `xl:3`); estado vazio com ação de limpar filtros; paginação com `BasePagination`.
  - Cards: foto do veículo (ou placeholder "sem foto", com queda para o placeholder se a imagem falhar), nome completo, categoria/ano, status, placa (ou "sem placa"), odômetro formatado em pt-BR, selo "Arquivado" quando for o caso e ações — "Ver detalhes", "Arquivar" (com `ConfirmDialog`, sem exclusão física) e "Restaurar".
  - `resources/js/pages/Vehicles/Show.vue`: foto grande, selo de status, placa, bloco de dados principais, bloco de aquisição (data, valor, odômetro), bloco de venda quando houver dados e resultado da venda (verde/vermelho) somente quando compra e venda existem, com o aviso de que o valor de compra não é despesa e que as despesas operacionais entram nas Fases 3 e 4; observações; ações (voltar e arquivar com confirmação); abas/placeholders de despesas, abastecimentos, manutenções e FIPE com `role="tablist"`, `role="tab"`, `aria-selected` e `role="tabpanel"`.
  - `resources/js/utils/options.js`: `optionLabel(options, value)` para traduzir valores das listas fechadas em rótulos, com testes em Vitest.
  - `resources/js/components/ui/EmptyState.vue`: ganhou `badge`, `headingLevel` e slot de ações, preservando o uso anterior nas páginas provisórias (não deixar dois `h1` na página foi o motivo do `headingLevel`).
  - Testes: `tests/Feature/VehiclePagesTest.php` com 10 testes das props Inertia (lista + paginação, opções em português, filtros ecoados, 12 por página com query string preservada nos links, lista vazia, `photo_url`/`archived` sem expor `photo_path`, detalhe, resultado de venda presente e ausente, ordem dos mais recentes) e 3 testes de unidade em `tests/Unit/VehicleTest.php` para os novos acessores.
- Validação:
  - `php artisan test` → 61 testes / 490 asserções, todos passando.
  - `npm test` → 17 testes (Vitest), todos passando.
  - `npm run build` → build concluído.
  - `./vendor/bin/pint` → sem alterações pendentes.
  - Verificação visual no navegador (`php artisan serve`): listagem (páginas 1 e 2, filtro de arquivados, cards com e sem foto), detalhe de veículo ativo e de vendido (resultado R$ 13.000,00 em verde), diálogo de confirmação de arquivamento e as duas telas em 360 px de largura.
- Observações:
  - Veículos arquivados não abrem a tela de detalhe (a rota `show` continua sem `withTrashed`): no card eles aparecem com o selo "Arquivado" e a ação "Restaurar". A consulta histórica de arquivados é o item 2.6.
  - O detalhe deixou de ser um placeholder mínimo do item 2.2 e agora mostra todos os dados; a edição continua fora de escopo (item 2.5).
  - `deleted_at` continua exposto no JSON do veículo (comportamento padrão do Eloquent); o atributo `archived` existe só para deixar a intenção explícita na interface.
  - A base local de desenvolvimento foi populada com 16 veículos de exemplo (um com foto, um vendido e um arquivado) apenas para a verificação visual; são dados de desenvolvimento no `database.sqlite`, que não é versionado.
  - Esta PR parte de `review/tarefa8` (Fase 2 empilhada).
