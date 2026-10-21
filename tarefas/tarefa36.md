# Item 6.4 — Documentação final e dados de demonstração

**Fase:** 6 — Finalização
**Posição:** item 4 de 5 da fase · tarefa 36 de 37 da sequência
**Pré-requisito:** `tarefa35.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 6 — Finalização") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **6.4 — Documentação final e dados de demonstração**
  - Completar o `README.md`: requisitos, instalação, configuração, migrations/seed, execução, testes, build, agendador, backup do SQLite e solução de problemas.
  - Documentar arquitetura (pastas, `app/Domain`, serviços), convenções (centavos, datas, timezone) e limitações conhecidas.
  - Criar seeder de demonstração opcional e idempotente, acionado por variável/flag, com veículos, despesas, abastecimentos e recorrências de exemplo.
  - Documentar a fonte FIPE, cache, limites e comportamento em indisponibilidade.
  - Critérios de aceite: instalação do zero reproduzível seguindo apenas o README; demo não roda por acidente em dados reais.
  - Validação: execução em diretório limpo (ou `migrate:fresh --seed`) e revisão dos comandos.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
