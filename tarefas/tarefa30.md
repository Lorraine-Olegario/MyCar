# Item 5.3 — Serviço de consulta e atualização com histórico

**Fase:** 5 — Tabela FIPE
**Posição:** item 3 de 5 da fase · tarefa 30 de 37 da sequência
**Pré-requisito:** `tarefa29.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 5 — Tabela FIPE") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **5.3 — Serviço de consulta e atualização com histórico**
  - Implementar serviço que recebe veículo + seleção (marca/modelo/ano), chama a fonte e persiste a cotação em transação, usando o mês de referência retornado pela própria fonte.
  - Validar que a combinação retornada corresponde ao veículo (marca, modelo/versão e ano do modelo); rejeitar combinações divergentes.
  - Em sucesso: atualizar campos atuais do veículo, gravar histórico e retornar DTO com valor, mês, marca, modelo, ano, código, combustível e data da consulta.
  - Em falha: não alterar os campos atuais nem criar registro; retornar erro tipado. Se já houver cotação, expor `is_stale` e a data da última consulta bem-sucedida.
  - Criar testes de sucesso, divergência de versão/ano, falha que preserva valor anterior, atualização repetida gerando novo histórico e nunca reaproveitar valor antigo como atual.
  - Critérios de aceite: histórico íntegro e atualização atômica; nenhum valor fictício em nenhum caminho.
  - Validação: `php artisan test --filter=FipeService`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
