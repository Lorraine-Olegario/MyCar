# Item 6.3 — Revisão de segurança e configuração

**Fase:** 6 — Finalização
**Posição:** item 3 de 5 da fase · tarefa 35 de 37 da sequência
**Pré-requisito:** `tarefa34.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 6 — Finalização") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **6.3 — Revisão de segurança e configuração**
  - Confirmar que fotos, comprovantes e notas ficam em disco privado e só são servidos por rotas controladas; validar MIME, tamanho e nomes de arquivo.
  - Revisar validação no backend, atribuição em massa, CSRF, uploads, path traversal e mensagens de erro que não exponham detalhes internos.
  - Verificar `.env.example` sem segredos, `APP_DEBUG=false` documentado para uso real, sessão e permissões do arquivo SQLite.
  - Aplicar rate limit nas rotas de consulta FIPE e revisar dependências com `composer audit` e `npm audit`.
  - Critérios de aceite: nenhum arquivo sensível público; nenhuma consulta externa sem limite; avisos de dependências tratados ou documentados.
  - Validação: testes de upload/autorização, auditorias e inspeção manual de `public/`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
