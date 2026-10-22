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

---

## Realizado

### Item 1 — Revisão de segurança e configuração
- Branch: `tarefa35/1-revisao-seguranca`
- Commit: `89efdd0`
- O que foi feito: Disco privado sem `serve` (`config/filesystems.php`); rate limit por IP nas rotas FIPE (catálogo 60/min, consulta/atualização 10/min); middleware `SecurityHeaders` (nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy); páginas de erro amigáveis do Inertia (403/404/419/429/500/503) quando `APP_DEBUG=false`, sem expor detalhes; `Error.vue` com mensagens por status; `.env.example` documenta `APP_DEBUG=false`/`APP_ENV=production` e `SESSION_SECURE_COOKIE` para uso real; Docker em produção com `APP_DEBUG=false`, entrypoint gravando `APP_ENV/APP_DEBUG/APP_URL` no `.env` (o `artisan serve` ignora variáveis de ambiente) e `chmod 600` no SQLite. Revisados sem mudança necessária: nomes de arquivo gerados (uuid), validação de MIME/tamanho no backend, `safe()`/`validated()` contra mass assignment, ids FIPE restritos por regex, CSRF (`PreventRequestForgery` no grupo web).
- Validação: `composer audit`: nenhuma vulnerabilidade. `npm audit`: 0 vulnerabilidades. `php artisan test` (339 testes, incluindo `SecurityTest`: 13, com upload de conteúdo PHP disfarçado, tamanho, mass assignment, rate limit 429, cabeçalhos, erro sem detalhes e `.env.example` sem segredos) e `npm run build` ok. Inspeção manual de `public/`: só `build`, `favicon.ico`, `index.php` e `robots.txt` (sem `storage`, `.env` ou `.sqlite`); no Docker confirmados os cabeçalhos, `APP_DEBUG=false` e o banco com permissão 600.
- Observações: O app não tem autenticação (decisão do projeto: uso pessoal local); quem expuser a porta na rede precisa colocar um proxy com autenticação. Nos testes de upload foi usado arquivo real (não o falso do Laravel, que decide o tipo pela extensão) para validar a detecção pelo conteúdo. O `.env.example` mantém `APP_DEBUG=true` como padrão de desenvolvimento; uso real deve trocar para `false`.
