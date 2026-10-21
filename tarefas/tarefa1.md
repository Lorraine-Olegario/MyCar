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

---

## Realizado

### Item 1 — 1.1 — Auditar ambiente e consolidar configuração base
- Branch: `tarefa1/1-auditar-ambiente-base`
- Commit: `7062185`
- O que foi feito:
  - Auditoria do ambiente: PHP 8.5.8, Composer 2.8.10, Node 20.20.0, npm 10.8.2, SQLite 3.51.0 e `pdo_sqlite` presente. Todas as versões da tabela do `README.md` conferem — nenhuma divergência, então o README não foi alterado.
  - `.env.example` ajustado para o padrão brasileiro: `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=pt_BR` e `APP_TIMEZONE=America/Sao_Paulo`. `APP_NAME=MyCar` e `DB_CONNECTION=sqlite` já estavam corretos.
  - `config/app.php`: o timezone estava fixo em `'UTC'`, ignorando variáveis de ambiente. Trocado por `env('APP_TIMEZONE', 'UTC')` para que o novo valor do `.env.example` tenha efeito, mantendo `UTC` como padrão quando a variável não é definida.
  - Confirmado que `.env` e o banco SQLite continuam ignorados (`.gitignore` e `database/.gitignore` respectivamente), assim como `vendor`, `node_modules` e `public/build`; nenhum segredo versionado (apenas `.env.example`).
- Validação:
  - `php artisan config:clear && php artisan test` → 2 testes / 5 asserções, todos passando.
  - `npm run build` → build concluído com sucesso (Vite 8.3.5).
  - `php artisan about` → `Database .. sqlite`; com `APP_TIMEZONE=America/Sao_Paulo` e `APP_LOCALE=pt_BR` o comando passa a reportar `Timezone .. America/Sao_Paulo` e `Locale .. pt_BR`, confirmando o wiring do `config/app.php`.
- Observações:
  - Número da tarefa mantido em **1** ainda que `tarefas/tarefa1.md` já exista na base: o próprio `tarefas/README.md` alerta que a checagem de "número ocupado" não deve disparar renumeração nesse cenário (todos os arquivos já estão versionados) e não há branches `tarefa1/*` nem `review/tarefa1` conflitantes.
  - Alteração pré-existente e não relacionada no working tree: `tarefas/tarefa01.md` (arquivo vazio, duplicado de `tarefa1.md`) estava deletado. Por decisão do usuário, foi mantido **fora** dos commits e segue pendente (não commitado).
  - O `.env` local não foi alterado (fora do escopo, que trata do `.env.example`); por isso `php artisan about` sem override ainda mostra `UTC`/`en`, mas o banco aparece como `sqlite`, atendendo ao critério de aceite.
  - Linha de atribuição dos commits seguiu o padrão assumido pela skill (`Co-Authored-By: Claude <noreply@anthropic.com>`), já que o repositório não tinha convenção própria.
