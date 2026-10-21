# Item 1.1 — Auditar ambiente e consolidar configuração base

**Fase:** 1 — Fundação
**Posição:** item 1 de 5 da fase · tarefa 1 de 37 da sequência
**Pré-requisito:** nenhum. Esta é a primeira tarefa da sequência.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 1 — Fundação") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **1.1 — Auditar ambiente e consolidar configuração base**
  - Conferir `php -v`, `composer --version`, `node -v`, `npm -v`, `php artisan about`, `sqlite3 --version` e a presença de `pdo_sqlite`.
  - Ajustar `.env.example`: `APP_NAME=MyCar`, `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=pt_BR`, `APP_TIMEZONE=America/Sao_Paulo` e `DB_CONNECTION=sqlite`.
  - Garantir que `.env`, banco SQLite, `vendor`, `node_modules` e `public/build` continuem ignorados; nenhum segredo versionado.
  - Atualizar a tabela de versões do `README.md` se alguma versão estiver divergente.
  - Incluir `tarefas/README.md` no commit de documentação deste item, junto com `tarefas/tarefa1.md`.
  - Critérios de aceite: `php artisan about` mostra SQLite; a aplicação sobe; a suíte atual continua verde.
  - Validação: `php artisan config:clear && php artisan test` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
