# Item 5.4 — Interface de seleção, consulta e histórico FIPE

**Fase:** 5 — Tabela FIPE
**Posição:** item 4 de 5 da fase · tarefa 31 de 37 da sequência
**Pré-requisito:** `tarefa30.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 5 — Tabela FIPE") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **5.4 — Interface de seleção, consulta e histórico FIPE**
  - Na página do veículo, selecionar marca → modelo → ano/versão em cascata, carregando opções da API com estados de carregamento e erro.
  - Exibir card com valor em R$, mês/ano de referência, marca, modelo, ano do modelo, código FIPE e data da última consulta.
  - Botão "Atualizar cotação" com feedback; em erro, mensagem clara, botão "Tentar novamente" e aviso quando o valor exibido estiver desatualizado.
  - Histórico de cotações em tabela/gráfico, com data da consulta e mês de referência; incluir aviso de que FIPE é referência, não preço garantido de venda.
  - Criar testes de feature das props e estados de erro; conferir responsividade.
  - Critérios de aceite: usuário consegue selecionar a versão correta; falha não apaga o último valor; datas e moeda em pt-BR.
  - Validação: `php artisan test --filter=FipePage` e `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Interface de seleção, consulta e histórico FIPE
- Branch: `tarefa31/1-interface-fipe`
- Commit: `f017b61`
- O que foi feito: `VehicleFipeController` (catálogo em JSON para a cascata, consulta e atualização) e rotas `/vehicles/{vehicle}/fipe/*`; prop lazy `fipe` em `VehicleController::show`; componente `VehicleFipe.vue` na página do veículo: cascata tipo → marca → modelo → ano/versão com estados de carregamento e erro com "Tentar novamente", card com valor, mês de referência, marca, modelo, ano, código, combustível e data da consulta, botão "Atualizar cotação", aviso de cotação desatualizada, mensagem de erro que mantém o último valor, histórico em tabela e gráfico e aviso de que FIPE é referência e não é somada a gastos. A aba FIPE provisória foi removida. `BaseSelect` ganhou `disabled` e recria o select quando as opções carregam depois.
- Validação: `php artisan test` (308 testes, incluindo `--filter=FipePage`: 11, com rede fake) e `npm run build` ok. Verificação manual no Docker contra a API real (leitura): cascata Chevrolet → Onix → 2023 carregou 109 marcas/558 modelos, a consulta foi aceita pelo conferidor e gravou o valor com referência outubro de 2026; o histórico e o card renderizaram.
- Observações: Os erros da consulta/atualização voltam pelo bag `errors.fipe` do Inertia, para a tela mostrar a falha sem perder o valor anterior. Veículos da categoria "outro" escolhem o tipo (carros/motos/caminhões); os demais têm o tipo fixo. A consulta real feita na verificação manual ficou gravada no banco do Docker (dado de demonstração).
