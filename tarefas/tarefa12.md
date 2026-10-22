# Item 3.1 — Categorias de despesas

**Fase:** 3 — Despesas
**Posição:** item 1 de 8 da fase · tarefa 12 de 37 da sequência
**Pré-requisito:** `tarefa11.md` concluída, revisada e mergeada na branch base; é o último item da fase anterior.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 3 — Despesas") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **3.1 — Categorias de despesas**
  - Criar migration `expense_categories`: `name` (único), `slug`, `color` (nullable), `active` (default true), `sort_order` e timestamps.
  - Criar seeder com as 17 categorias iniciais na ordem do enunciado: Combustível, Troca de óleo e filtros, Manutenção preventiva, Manutenção corretiva, Pneus e alinhamento, Freios, Bateria e sistema elétrico, Motor e transmissão, Documentação e licenciamento, IPVA, Seguro, Estacionamento, Pedágios, Lavagem e estética, Multas, Acessórios e melhorias, Outros gastos.
  - CRUD em `/categories` com criar, editar, ativar/desativar e reordenar; não permitir excluir categoria em uso (oferecer desativação).
  - Validar nome único (ignorando maiúsculas/minúsculas) e obrigatório; categoria inativa não pode ser escolhida em novas despesas, mas continua válida em registros antigos.
  - Critérios de aceite: seed idempotente; desativar não apaga despesas; categoria em uso não é excluída.
  - Validação: `php artisan migrate:fresh --seed` e testes de feature/unitários.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Categorias de despesas
- Branch: `tarefa12/1-categorias-despesas`
- Commit: `a07487b`
- O que foi feito: Migration `expense_categories`, model/factory, `ExpenseCategorySeeder` com as 17 categorias (idempotente, não sobrescreve ajustes do usuário), `ExpenseCategoryController` (criar, editar, ativar/desativar, mover para cima/baixo, excluir só sem uso), validação de nome único sem diferenciar maiúsculas, página Categories/Index.vue. DatabaseSeeder passou a chamar o seeder e a criar o usuário de teste de forma idempotente.
- Validação: `php artisan test` (89 testes) ok; `migrate:fresh --seed` seguido de `db:seed` mantém 17 categorias; `npm run build` ok.
- Observações: O vínculo com despesas ainda não existe: `ExpenseCategory::isInUse()` consulta a tabela `expenses` somente se ela existir (a tarefa 13 a cria) e é coberto em teste com tabela temporária. Reordenação por botões ↑/↓ (acessível, sem drag-and-drop). O slug é informado explicitamente no seeder porque o DatabaseSeeder usa WithoutModelEvents.
