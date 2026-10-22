# Revisão de UX, responsividade e acessibilidade

Checklist da tarefa 34, executado no navegador sobre o Docker (`docker compose up -d --build`) com dados de exemplo.

## Varredura automatizada em 360, 768 e 1280 px

22 caminhos percorridos por navegação do próprio app (dashboard, lista/detalhe/edição/cadastro de veículo, despesas,
recorrentes, abastecimentos, manutenções, categorias, índice de relatórios e os 10 relatórios). Para cada tela:

| Verificação | 360 px | 768 px | 1280 px |
|---|---|---|---|
| Sem rolagem horizontal da página (tabelas largas rolam dentro do próprio contêiner) | ok | ok | ok |
| Todo campo (input/select/textarea) com `label` ou `aria-label` | ok | ok | ok |
| Toda imagem com `alt` | ok | ok | ok |
| Todo botão/link com nome acessível | ok | ok | ok |
| Exatamente um `h1` por tela | ok | ok | ok |

## Teclado e foco

- Link "Pular para o conteúdo" no início de cada página e foco visível (`focus-visible`) em todos os controles.
- Diálogos (`BaseModal`/`ConfirmDialog`): foco vai para o diálogo ao abrir, **Tab/Shift+Tab ficam presos dentro dele**,
  **Esc fecha** e o foco **volta ao botão que abriu** (verificado no navegador).
- Abas da página do veículo e do gráfico/tabela usam `role="tab"`/`aria-pressed`; gráficos têm `role="img"` com rótulo
  e uma tabela equivalente sempre no DOM.

## Contraste (WCAG AA, texto normal ≥ 4,5:1)

Calculado para os pares do tema (`resources/css/app.css`). Ajustes feitos nesta tarefa:

| Par | Antes | Depois |
|---|---|---|
| Rótulos/links de destaque (laranja) sobre branco | 4,04 | 5,92 |
| Botão primário no hover (texto branco) | 4,04 | 5,92 |
| Texto de aviso "previsto" (âmbar) sobre branco | 3,19 | 5,02 |
| Rodapé da barra lateral (cinza) sobre fundo escuro | 3,49 | 8,52 |
| Rótulo "Veículo ativo" no cartão escuro | 4,48 | acima de 8 (âmbar claro) |

Demais pares conferidos acima de 4,5 (texto secundário 4,6–5,2; estados de sucesso/erro 4,8–7,6).

## Estados e feedback

- Listagens: carregando, vazio (com ação), erro e filtros sem resultado existem em veículos, despesas, abastecimentos,
  manutenções, recorrentes, categorias e relatórios.
- Mensagens de sucesso/erro pelo `FlashMessages` (com `role="status"`/`alert`); erros de validação junto ao campo.
- Exclusões e ações irreversíveis (arquivar veículo, excluir despesa/abastecimento/manutenção/categoria, cancelar
  previsão) pedem confirmação.

## Textos e formatação

- Moeda `R$ 1.234,56`, datas `dd/mm/aaaa`, números e percentuais pt-BR em telas e CSV.
- Pago × previsto sempre em rótulos e cores distintos; FIPE e valor de compra aparecem separados dos gastos, com aviso.

## Limitações conhecidas

- A verificação de contraste cobre os pares do tema, não cada combinação de classes pontual.
- Leitor de tela não foi testado manualmente (apenas a estrutura semântica acima).
