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

---

## Realizado

### Item 1 — Cliente HTTP da API FIPE
- Branch: `tarefa28/1-cliente-http-fipe`
- Commit: `097851a`
- O que foi feito: Configuração `services.fipe` (base_url, token opcional, timeout, retries, cache) e variáveis em `.env.example`. Em `app/Domain/Fipe`: `FipeClient` (marcas, modelos, anos e valor por ano da API Parallelum v2, com timeout curto, retry apenas em falha de conexão/5xx, cache de 24 h só do catálogo e nunca do valor), DTOs `FipeOption` e `FipeQuoteData` (valor em centavos e mês de referência normalizado a partir da resposta), `FipeVehicleType` (carro→cars, moto→motorcycles, caminhões; "outro" exige escolha) e `FipeException` com motivo tipado (indisponível, timeout, 404, resposta inválida) e mensagens em português.
- Validação: `php artisan test` (276 testes, incluindo `--filter=FipeClient`: 12, todos com `Http::fake` e `preventStrayRequests`; nenhuma chamada à rede real) ok. Endpoints e campos conferidos na documentação oficial (deividfortuna.github.io/fipe/v2).
- Observações: O token `X-Subscription-Token` é opcional (a doc lista o header sem dizer se é obrigatório). O parâmetro `reference` da API não é usado: o mês de referência vem sempre da resposta. Falhas não são cacheadas.
