# Item 2.3 — Upload e proteção de fotos do veículo

**Fase:** 2 — Veículos
**Posição:** item 3 de 6 da fase · tarefa 8 de 37 da sequência
**Pré-requisito:** `tarefa7.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 2 — Veículos") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **2.3 — Upload e proteção de fotos do veículo**
  - Aceitar `photo` como imagem JPG, PNG ou WebP de até 4 MB; validar MIME real no backend.
  - Salvar em disco `local`, caminho `vehicles/{id}/...`, com nome único; nunca em `public/`.
  - Criar rota `GET /vehicles/{vehicle}/photo` que serve o arquivo do disco privado e responde 404 quando não houver foto.
  - Ao substituir ou remover a foto, apagar o arquivo antigo; ao arquivar, manter a foto para histórico.
  - Criar testes de upload válido, MIME inválido, substituição (arquivo antigo removido) e acesso à rota.
  - Critérios de aceite: nenhuma foto acessível por URL pública direta; upload não quebra a criação/edição do veículo.
  - Validação: `php artisan test --filter=VehiclePhoto`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.
