# Item 6.2 — Revisão de UX, responsividade e acessibilidade

**Fase:** 6 — Finalização
**Posição:** item 2 de 5 da fase · tarefa 34 de 37 da sequência
**Pré-requisito:** `tarefa33.md` concluída, revisada e mergeada na branch base.
**Contexto e convenções:** leia `tarefas/README.md` (seção "Fase 6 — Finalização") antes de implementar.

**Fora de escopo:** os demais itens desta fase e qualquer mudança que não seja necessária para este item.

## Item

- **6.2 — Revisão de UX, responsividade e acessibilidade**
  - Revisar todas as páginas em 360 px, 768 px e 1280 px; eliminar rolagem horizontal e tabelas quebradas.
  - Garantir navegação por teclado, foco visível, labels associados, contraste adequado e textos alternativos nas imagens.
  - Conferir estados de carregamento, vazio, erro e sucesso em todas as listagens e formulários; confirmação antes de exclusões.
  - Revisar textos em português, formatação R$/datas e clareza dos indicadores (pago vs previsto, FIPE vs gastos).
  - Critérios de aceite: nenhuma tela inacessível ou inutilizável em mobile; feedback consistente em todas as ações.
  - Validação: checklist manual + `npm run build`.

**Execução:** implemente somente este item; um item = uma branch = um commit. Rode as validações indicadas. Não avance para a próxima tarefa sem orientação.

---

## Realizado

### Item 1 — Revisão de UX, responsividade e acessibilidade
- Branch: `tarefa34/1-revisao-ux-acessibilidade`
- Commit: `3d44cbd`
- O que foi feito: Varredura de 22 telas em 360/768/1280 px (sem rolagem horizontal, campos rotulados, imagens com alt, botões/links com nome, um h1 por tela). Correções: contraste do tema (laranja de destaque 4,04→5,92, texto âmbar 3,19→5,02, rodapé da sidebar 3,49→8,52, rótulo do cartão escuro em âmbar claro) e `BaseModal` com foco preso (Tab/Shift+Tab), Esc e devolução do foco ao elemento que abriu. Checklist registrado em `docs/revisao-ux.md`.
- Validação: `npm run build`, `npm test` (32) e `php artisan test` (326) ok. Checklist manual no navegador (Docker) com o script de auditoria em 3 larguras; foco preso/Esc/restauração do foco verificados no navegador.
- Observações: Sem leitor de tela: a validação cobre estrutura semântica, rótulos, contraste calculado e teclado. Telas verificadas visualmente em 360 px: despesas (e, antes, o painel do gráfico); as demais apenas pela auditoria automatizada.
