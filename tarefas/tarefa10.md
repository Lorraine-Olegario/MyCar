# Item 2.5 — Formulário de cadastro e edição de veículo (Vue)

**Fase:** 2 — Veículos
**Posição:** item 5 de 6 da fase · tarefa 10 de 37 da sequência
**Pré-requisito:** `tarefa9.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 2 — Veículos") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **2.5 — Formulário de cadastro e edição de veículo (Vue)**
  - Formulário único para criar/editar, dividido em identificação, documentação, aquisição, venda, foto e observações.
  - Campos de venda aparecem e são exigidos somente quando o status for `vendido`; veículo vendido pode ter data/valor de aquisição anteriores.
  - Placa com normalização e máscara; valores em reais convertidos para centavos; datas em `dd/mm/aaaa`; preview da foto e indicação de substituição.
  - Exibir erros de validação por campo, estado de envio, cancelar e mensagem de sucesso; nunca confiar apenas na validação do cliente.
  - Critérios de aceite: criar e editar veículo com e sem foto; erros do backend aparecem corretamente; formulário utilizável em 360 px.
  - Validação: testes de feature de store/update e `npm test` para conversão de moeda.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — 2.5 — Formulário de cadastro e edição de veículo (Vue)
- Branch: `tarefa10/1-formulario-veiculo`
- Commit: `3e0f55a`
- O que foi feito:
  - `routes/web.php`: o `Route::resource('vehicles', ...)` deixou de excluir `create` e `edit`.
  - `app/Http/Controllers/VehicleController.php`: novas ações `create` e `edit`, que renderizam `Vehicles/Form` com `vehicle` (nulo no cadastro) e `options` (status, categoria e combustível com rótulos em português), reaproveitando o `options()` do item 2.2.
  - `resources/js/pages/Vehicles/Form.vue`: página única de cadastro/edição em seis seções (identificação, documentação, aquisição, situação e venda, foto e observações), com barra de ações fixa, "Voltar"/"Cancelar", estado de envio (`BaseButton` com `loading`) e aviso de erro (`role="alert"`).
  - Campos derivados: o nome é sugerido a partir de marca + modelo + versão enquanto o usuário não escrever um nome próprio; o início do acompanhamento é preenchido com a data de aquisição enquanto não for alterado.
  - Venda: data e valor de venda aparecem — e são exigidos — somente quando o status é "Vendido", e são limpos ao voltar para "Ativo"; a seção lembra que a aquisição pode ser bem anterior à venda.
  - `resources/js/components/ui/BaseDateInput.vue`: campo de data com máscara `dd/mm/aaaa`, teclado numérico, mensagem de data inválida e valor enviado no formato do banco (`YYYY-MM-DD`); datas inexistentes (31/02) são rejeitadas.
  - `resources/js/components/ui/BaseCurrencyInput.vue`: campo de moeda com prefixo "R$", que envia centavos a cada tecla, formata no `blur` e devolve `null` quando está vazio.
  - Os dois campos só sincronizam o texto com o valor do formulário quando não estão em edição, para não reescrever o que o usuário está digitando.
  - Placa com entrada em maiúsculas, restrita a letras/números/hífen (`ABC1234` ou `ABC1D23`), mantendo a normalização definitiva no backend; anos, odômetros e valores aceitam apenas dígitos.
  - Foto: `input` de arquivo com validação de tipo/tamanho no cliente, pré-visualização via `URL.createObjectURL` (revogada ao desmontar), opção "Remover a foto atual" e indicação explícita de substituição ("Nova foto selecionada — a foto atual será substituída ao salvar.").
  - Erros: o formulário usa `novalidate` e exibe os erros vindos do backend por campo (`aria-invalid` + `aria-describedby`), sem confiar apenas na validação do cliente.
  - `resources/js/utils/format.js`: novo `parseDateInput` (dd/mm/aaaa → `YYYY-MM-DD`, rejeitando datas inexistentes) com casos de teste no Vitest.
  - Navegação: botão "Novo veículo" no cabeçalho da listagem, "Cadastrar veículo" no estado vazio e "Editar" no detalhe do veículo.
  - Testes: `tests/Feature/VehicleFormTest.php` com 9 testes (props de `create`/`edit`, 404 para veículo arquivado, cadastro com placa normalizada, atualização a partir do formulário, erros de validação compartilhados com o formulário, venda sem os dados obrigatórios, venda com aquisição antiga e rotas alcançáveis).
- Validação:
  - `php artisan test` → 70 testes / 569 asserções, todos passando.
  - `npm test` → 20 testes (Vitest), todos passando (inclui `parseDateInput` e `parseCurrencyInput`).
  - `npm run build` → build concluído.
  - `./vendor/bin/pint --test` → sem alterações pendentes.
  - Verificação visual e funcional no navegador (`php artisan serve`): cadastro completo com foto (nome derivado, placa `XYZ9Z99` em maiúsculas, `R$ 105.000,00`, `10/03/2021`, campos de venda revelados e exigidos, redirecionamento para o detalhe com mensagem de sucesso), rodada de erro com placa duplicada preservando todos os valores digitados, edição sem tocar na foto (foto mantida), edição substituindo a foto e remoção da foto; conferi também o formulário em 360 px (sem rolagem horizontal) e que a edição de veículo arquivado devolve 404.
- Observações:
  - A validação do cliente é apenas de conveniência: o formulário é enviado ao servidor mesmo com campos vazios (`novalidate`) e os erros exibidos são sempre os do backend, como pede o item.
  - Mensagens em português dependem de `APP_LOCALE=pt_BR`, que já está no `.env.example`; o `.env` local continua com `en`, então a verificação visual mostrou mensagens de validação em inglês (conferi as mensagens em pt-BR fora do navegador).
  - Depois de uma falha de validação o arquivo escolhido não volta a preencher o `input[type=file]` (limitação do navegador); os demais campos são preservados.
  - Esta PR parte de `review/tarefa9` (Fase 2 empilhada).
