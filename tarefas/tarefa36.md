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

---

## Realizado

### Item 1 — Documentação final e dados de demonstração
- Branch: `tarefa36/1-documentacao-demo`
- Commit: `28f5197`
- O que foi feito: `README.md` reescrito (visão geral, requisitos, instalação local e Docker, configuração, migrations/seed, execução, testes, agendador, backup do SQLite e dos anexos, FIPE, segurança, estrutura, convenções, limitações e solução de problemas) e `docs/arquitetura.md` ampliado (contextos de domínio, regras transversais, segurança, banco). Dados de demonstração opcionais e idempotentes: `DemoData`, `DemoSeeder` (só com `MYCAR_DEMO=true`), comando `mycar:demo seed|clear` (confirmação; `--force` obrigatório em produção) e `config/mycar.php`; veículos marcados com `[DEMO]`, placas reservadas e remoção que não toca nos dados reais. O container Docker agora gera as recorrências e roda o agendador.
- Validação: `php artisan test` (347 testes, incluindo `--filter=DemoData`: 8) ok. Instalação do zero validada em diretório limpo seguindo o README: `composer install` → `.env` → `key:generate` → SQLite → `migrate --seed` → `npm ci` → `npm run build` → `composer test` (347 verdes) → `pint --test` → `artisan serve` respondendo 200 em `/` e `/reports/by-category`; `mycar:demo seed` criou 2 veículos. Docker revalidado (agendador ativo, home 200).
- Observações: As cotações FIPE da demonstração são fictícias e marcadas em `payload.demo`; a demonstração não é criada por `db:seed` comum. O comando `mycar:demo clear` remove somente os veículos com placas reservadas e prefixo `[DEMO]`. O backup via `sqlite3` no Docker exige o binário no container (a alternativa com volume está documentada).
