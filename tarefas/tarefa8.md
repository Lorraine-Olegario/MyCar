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

---

## Realizado

### Item 1 — 2.3 — Upload e proteção de fotos do veículo
- Branch: `tarefa8/1-upload-fotos-veiculo`
- Commit: `35a5813`
- O que foi feito:
  - `app/Domain/Vehicles/VehiclePhotoStorage.php`: grava a foto em `vehicles/{id}/` no disco `local` (raiz `storage/app/private`), com nome único (`UUID` + extensão real do arquivo); apaga o arquivo anterior antes de gravar o novo (`put`), permite remover a foto (`forget`) e informa se há arquivo gravado (`exists`). Nada é escrito em `public/`.
  - `StoreVehicleRequest`: regra `photo` com `file`, `mimes:jpg,jpeg,png,webp`, `mimetypes:image/jpeg,image/png,image/webp` (MIME real do conteúdo, não a extensão enviada) e `max:4096` (4 MB). `photo_path` saiu das regras — o caminho passa a ser definido só pelo backend, então o cliente não consegue apontar a rota de foto para outro arquivo do disco privado.
  - `UpdateVehicleRequest`: acrescenta `remove_photo` (booleano) para excluir a foto atual.
  - `VehicleController`: `store` e `update` persistem os dados com `safe()->except('photo')` e tratam o arquivo em seguida (substituir ou remover); novo `photo` serve o arquivo do disco privado e responde 404 quando o veículo não tem foto. `destroy` continua sendo soft delete e não toca no arquivo, mantendo a foto para histórico.
  - `routes/web.php`: `GET vehicles/{vehicle}/photo` (`vehicles.photo`) com `withTrashed()`, para servir também a foto de veículos arquivados.
  - `lang/pt_BR/validation.php`: atributos `photo` (foto) e `remove_photo` (remover foto).
  - `tests/Feature/VehiclePhotoTest.php`: 13 testes — upload no cadastro (caminho `vehicles/{id}/...` e arquivo no disco), PNG e WebP, arquivo acima de 4 MB, arquivo cujo conteúdo não é imagem (MIME real), `photo_path` enviado pelo cliente é ignorado, substituição apagando o arquivo antigo, remoção da foto, edição sem foto, rota servindo o arquivo com `Content-Type` de imagem, 404 sem foto, arquivamento mantendo a foto (e a rota ainda servindo), e ausência de URL pública direta.
- Validação:
  - `php artisan test --filter=VehiclePhoto` → 13 testes / 52 asserções, todos passando.
  - `php artisan test` → 48 testes / 304 asserções, todos passando.
  - `./vendor/bin/pint` → sem alterações pendentes.
  - `php artisan tinker` confirmou que o disco `local` grava em `storage/app/private` (fora de `public/`).
  - `npm run build` → build concluído.
- Observações:
  - O disco `local` mantém `serve => true` (padrão do Laravel), mas o `/storage/{path}` só responde com URL assinada; sem assinatura retorna 403 em dev e 404 em produção. O app nunca gera essas URLs: as fotos saem sempre por `vehicles.photo`.
  - A foto do veículo arquivado continua acessível por `vehicles.photo` (mantida para histórico); as páginas padrão é que passam a não listar o veículo.
  - O nome do arquivo é um UUID, então trocar a foto nunca reaproveita o caminho antigo (sem cache enganoso no navegador).
  - Esta PR parte de `review/tarefa7` (Fase 2 empilhada).
