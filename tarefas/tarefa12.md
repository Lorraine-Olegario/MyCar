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
