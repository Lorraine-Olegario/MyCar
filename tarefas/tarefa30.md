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

---

## Realizado

### Item 1 — Serviço de consulta e atualização com histórico
- Branch: `tarefa30/1-servico-consulta-fipe`
- Commit: `cc1180c`
- O que foi feito: `FipeQuoteService` (`consult`, `refresh`, `snapshot`), `FipeSelection` e `FipeVehicleMatcher` em `app/Domain/Fipe`. A consulta usa o mês de referência da própria fonte, rejeita combinações que não batem com marca, modelo/versão e ano do modelo do veículo (sem acentos/maiúsculas e tolerante ao nome longo da FIPE), e em sucesso grava o histórico e os campos atuais do veículo numa transação. Em falha, nada é alterado e a exceção é tipada (`FipeException` com motivo, inclusive `mismatch`). `snapshot()` devolve a última cotação com `is_stale` (mais de `FIPE_STALE_DAYS`=35 dias) e a data da consulta.
- Validação: `php artisan test` (297 testes, incluindo `--filter=FipeService`: 12, com rede fake): sucesso, divergência de ano/marca/modelo/versão, falha preservando o valor anterior, nova consulta gerando novo histórico, rollback por falha de banco e valor antigo nunca reaproveitado.
- Observações: `is_stale` é por idade da última consulta bem-sucedida (a falha de atualização não é gravada). O casamento de versão exige que todas as palavras de modelo e versão do veículo estejam no texto da FIPE: é estrito de propósito, para evitar cotação do modelo errado.
