# Item 5.1 — Cliente HTTP da API FIPE

**Fase:** 5 — Tabela FIPE
**Posição:** item 1 de 5 da fase · tarefa 28 de 37 da sequência
**Pré-requisito:** `tarefa27.md` concluída, revisada e mergeada na branch base; é o último item da fase anterior.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 5 — Tabela FIPE") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **5.1 — Cliente HTTP da API FIPE**
  - Adicionar configuração `fipe.base_url`, timeout, retries e chave de cache em `config/services.php` e `.env.example`.
  - Criar cliente em `app/Domain/Fipe` com métodos para marcas, modelos, anos e valor por ano, retornando DTOs tipados.
  - Mapear a categoria do veículo para o tipo de consulta (`cars`, `motorcycles`, `trucks`); para `other`, permitir escolher o tipo antes de consultar.
  - Aplicar cache curto (ex. 24 h) apenas para catálogo (marcas/modelos/anos); valores consultados são sempre persistidos.
  - Mapear erros: indisponibilidade, timeout, 404 de combinação inexistente e resposta malformada, com mensagens em português.
  - Criar testes com `Http::fake`: sucesso, 404, timeout, resposta inválida, cache e retry; nenhum teste pode chamar a rede real.
  - Critérios de aceite: cliente não derruba a aplicação em falha externa; chamadas repetidas de catálogo usam cache.
  - Validação: `php artisan test --filter=FipeClient`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
