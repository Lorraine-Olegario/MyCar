# Item 1.5 — Convenções de domínio, documentação e validação final da fase

**Fase:** 1 — Fundação
**Posição:** item 5 de 5 da fase · tarefa 5 de 37 da sequência
**Pré-requisito:** `tarefa4.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 1 — Fundação") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **1.5 — Convenções de domínio, documentação e validação final da fase**
  - Registrar em `README.md` (ou `docs/arquitetura.md`) as convenções: centavos, datas, timezone, enums, uso de `app/Domain`, limites de controllers/services e política de testes.
  - Configurar `APP_NAME`, locale e faker locale nos arquivos de configuração quando necessário; garantir `php artisan migrate:fresh` funcionando.
  - Atualizar a página inicial com um resumo do MyCar e links para as seções.
  - Revisar `pint`, remover o componente/teste de exemplo remanescente e garantir que nenhum teste seja ignorado.
  - Critérios de aceite: documentação compatível com o código; suíte PHP e Vitest verdes; build limpo; instalação reproduzível a partir do README.
  - Validação: `php artisan migrate:fresh`, `composer test`, `npm test` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Esta é a última tarefa da Fase 1; ao terminar, aguarde orientação antes de iniciar a próxima fase.

---

## Realizado

### Item 1 — 1.5 — Convenções de domínio, documentação e validação final da fase
- Branch: `tarefa5/1-convencoes-dominio-documentacao`
- Commit: `27dd464`
- O que foi feito:
  - `docs/arquitetura.md` (novo): convenções de camadas (controllers finos, `app/Domain/<Contexto>`, `app/Services` quando houver benefício, sem repositórios genéricos), moeda em centavos, datas/timezone/locale, enums PHP (backed), política de testes (PHPUnit + Vitest), componentes de UI e arquivos privados.
  - `README.md` atualizado: descrição do MyCar/Fase 1, link para `docs/arquitetura.md`, estrutura de pastas atual (Inertia, pages, components/ui, utils) e seção de testes com `npm test`.
  - `config/app.php`: padrões do projeto — `APP_NAME` MyCar, locale `pt_BR`, fallback `en`, faker `pt_BR` e timezone `America/Sao_Paulo`.
  - Página inicial (`Dashboard.vue`) com resumo do MyCar e cartões com links para Veículos, Despesas, Abastecimentos, Relatórios e Categorias.
  - Removido `tests/Unit/ExampleTest.php` (teste de exemplo); diretório mantido com `.gitkeep` para o `php artisan test` continuar enxergando `tests/Unit`. Nenhum teste ignorado/pulado.
  - Pint revisado e aplicado (`bootstrap/app.php`).
- Validação:
  - `php artisan migrate:fresh` → migrations executadas com sucesso.
  - `composer test` → 7 testes / 58 asserções, passando.
  - `npm test` → 14 testes passando.
  - `npm run build` → OK.
  - Página inicial conferida no browser (resumo + 5 links de seção).
- Observações:
  - Ao remover o teste de exemplo, o diretório `tests/Unit` deixaria de existir e `php artisan test` falharia; resolvido com `tests/Unit/.gitkeep`.
  - As mudanças de default em `config/app.php` só têm efeito quando a variável não está no `.env`. O `.env` local (não versionado) ainda tem `APP_LOCALE=en`/faker `en_US`; para o runtime local seguir o padrão, copie os valores do `.env.example` (`APP_LOCALE=pt_BR`, `APP_FAKER_LOCALE=pt_BR`, `APP_TIMEZONE=America/Sao_Paulo`). O `.env` não foi alterado por ser arquivo local do usuário.
  - Trabalho **empilhado**: `review/tarefa5` parte de `review/tarefa4` e a PR aponta para `review/tarefa4`.
  - Fim da Fase 1.
