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
