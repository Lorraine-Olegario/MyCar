# Tarefas do MyCar

Cada arquivo `tarefa<N>.md` contém **um único item** de trabalho. Um item = uma branch = um commit = uma PR pequena. Execute os arquivos **em ordem numérica**, sem pular etapas.

## Como executar

Use o comando `/task tarefa1.md`, depois `/task tarefa2.md`, e assim por diante. Cada execução parte da branch base já atualizada com a PR anterior.

Não commite os 37 arquivos de tarefa de uma vez. A skill considera o arquivo da tarefa como não versionado até a PR correspondente; se todos estiverem na base, a checagem de "número ocupado" pode causar renumeração indevida. O `README.md` deve ser incluído no commit de documentação da `tarefa1.md`.

## Regras gerais

Não trabalhe diretamente na `main`. Não antecipe itens de arquivos seguintes. Não inclua alterações de outros itens no mesmo commit. Se um item for ambíguo a ponto de mudar o resultado, pergunte antes de implementar. Se for apenas um detalhe, siga o padrão do projeto e registre em "Observações" na documentação do item.

## Convenções globais

**Stack:** Laravel 13, PHP 8.5, Vue 3, Vite, SQLite, Tailwind CSS, PHPUnit e Inertia.js. Sem API REST separada.

**Usuário:** aplicação pessoal, sem autenticação nesta versão. Login, se necessário, será uma tarefa nova.

**Moeda:** valores persistidos em centavos inteiros (`*_cents`); conversão para reais apenas na borda.

**Datas:** campos de negócio em `date`, timezone `America/Sao_Paulo`, locale `pt_BR`, exibição `dd/mm/aaaa` e moeda `R$`.

**Organização:** controllers finos em `app/Http`, Eloquent em `app/Models`, regras/cálculos em `app/Domain/<Contexto>` e orquestração em `app/Services` quando houver benefício real. Sem repositórios genéricos.

**Qualidade:** validação no backend, migrations, seeders, paginação, testes automatizados dos cálculos e tratamento de erros. Não adicionar dependências sem necessidade real.

**Arquivos enviados:** fotos, comprovantes e notas ficam em disco privado, servidos por rotas controladas, com validação de MIME e tamanho.

## Índice

| Arquivo | Fase | Item | Título |
|---:|---|---|---|
| `tarefa1.md` | 1 — Fundação | 1.1 | Auditar ambiente e consolidar configuração base |
| `tarefa2.md` | 1 — Fundação | 1.2 | Instalar e configurar Inertia.js com Vue 3 |
| `tarefa3.md` | 1 — Fundação | 1.3 | Layout base, navegação e páginas provisórias |
| `tarefa4.md` | 1 — Fundação | 1.4 | Padrões de UI, formatação brasileira e feedback |
| `tarefa5.md` | 1 — Fundação | 1.5 | Convenções de domínio, documentação e validação final da fase |
| `tarefa6.md` | 2 — Veículos | 2.1 | Estrutura de dados de veículos |
| `tarefa7.md` | 2 — Veículos | 2.2 | CRUD de veículos e arquivamento no backend |
| `tarefa8.md` | 2 — Veículos | 2.3 | Upload e proteção de fotos do veículo |
| `tarefa9.md` | 2 — Veículos | 2.4 | Listagem e detalhe do veículo (Vue) |
| `tarefa10.md` | 2 — Veículos | 2.5 | Formulário de cadastro e edição de veículo (Vue) |
| `tarefa11.md` | 2 — Veículos | 2.6 | Consulta histórica de veículos vendidos e arquivados |
| `tarefa12.md` | 3 — Despesas | 3.1 | Categorias de despesas |
| `tarefa13.md` | 3 — Despesas | 3.2 | Domínio e CRUD de despesas |
| `tarefa14.md` | 3 — Despesas | 3.3 | Interface de despesas |
| `tarefa15.md` | 3 — Despesas | 3.4 | Despesas recorrentes: modelos e ocorrências |
| `tarefa16.md` | 3 — Despesas | 3.5 | Interface de despesas recorrentes e vencimentos |
| `tarefa17.md` | 3 — Despesas | 3.6 | Abastecimentos: domínio e cálculos |
| `tarefa18.md` | 3 — Despesas | 3.7 | Interface de abastecimentos |
| `tarefa19.md` | 3 — Despesas | 3.8 | Histórico de manutenções |
| `tarefa20.md` | 4 — Indicadores e relatórios | 4.1 | Serviço único de consultas financeiras |
| `tarefa21.md` | 4 — Indicadores e relatórios | 4.2 | Componentes de indicadores, gráficos e filtros |
| `tarefa22.md` | 4 — Indicadores e relatórios | 4.3 | Dashboard principal |
| `tarefa23.md` | 4 — Indicadores e relatórios | 4.4 | Página do veículo: visão geral e financeiro |
| `tarefa24.md` | 4 — Indicadores e relatórios | 4.5 | Página do veículo: históricos, documentos e vencimentos |
| `tarefa25.md` | 4 — Indicadores e relatórios | 4.6 | Relatórios 1 a 5 |
| `tarefa26.md` | 4 — Indicadores e relatórios | 4.7 | Relatórios 6, 7, 9 e 10 |
| `tarefa27.md` | 4 — Indicadores e relatórios | 4.8 | Exportação CSV dos relatórios |
| `tarefa28.md` | 5 — Tabela FIPE | 5.1 | Cliente HTTP da API FIPE |
| `tarefa29.md` | 5 — Tabela FIPE | 5.2 | Persistência da seleção FIPE e do histórico de cotações |
| `tarefa30.md` | 5 — Tabela FIPE | 5.3 | Serviço de consulta e atualização com histórico |
| `tarefa31.md` | 5 — Tabela FIPE | 5.4 | Interface de seleção, consulta e histórico FIPE |
| `tarefa32.md` | 5 — Tabela FIPE | 5.5 | FIPE nos indicadores e relatórios |
| `tarefa33.md` | 6 — Finalização | 6.1 | Testes de integração dos fluxos completos |
| `tarefa34.md` | 6 — Finalização | 6.2 | Revisão de UX, responsividade e acessibilidade |
| `tarefa35.md` | 6 — Finalização | 6.3 | Revisão de segurança e configuração |
| `tarefa36.md` | 6 — Finalização | 6.4 | Documentação final e dados de demonstração |
| `tarefa37.md` | 6 — Finalização | 6.5 | Regressão final e checklist do enunciado |

## Fase 1 — Fundação

**Objetivo:** deixar o projeto executável e preparado para receber as funcionalidades de negócio: Laravel + Vue 3 integrados, SQLite configurado, layout base com navegação, padrões de UI/formatação e testes iniciais verdes.

**Estado atual do repositório:** Laravel 13.38.0, PHP 8.5.8, Vue 3.5.44, Vite 8.3.5, Tailwind CSS 4.3.3, SQLite 3.51.0, PHPUnit 12.5.40 e Node 20.20.0. Já existem `.env.example` com SQLite, `resources/views/app.blade.php`, `resources/js/App.vue` e um teste da rota inicial. Auditar e evoluir essa base; **não recriar o projeto** nem apagar histórico sem necessidade.

**Frontend:** adotar **Inertia.js** como ponte entre Laravel e Vue 3: rotas, validação e regras no backend; páginas Vue no frontend; sem API REST separada.

**Escopo de usuário:** aplicação de uso pessoal, **sem autenticação** nesta versão. Se login for necessário, tratar como tarefa nova.

**Moeda:** valores monetários persistidos em **centavos inteiros** (`*_cents`); conversão para reais somente na borda (formulários e exibição).

**Datas:** datas de negócio em `date` (sem hora), timezone `America/Sao_Paulo` e locale `pt_BR`.

**UI:** sem biblioteca de componentes; componentes próprios com Tailwind. Chart.js só entra na Fase 4.

**Estrutura:** controllers finos em `app/Http`, Eloquent em `app/Models`, cálculos e regras em `app/Domain/<Contexto>` e orquestração em `app/Services` quando houver benefício real. Sem repositórios genéricos.

**Fora de escopo:** veículos, despesas, abastecimentos, FIPE, relatórios e qualquer model de negócio.

## Fase 2 — Veículos

**Objetivo:** permitir cadastrar, editar, consultar, arquivar e manter o histórico de veículos ativos e vendidos, incluindo foto, dados de aquisição/venda e suporte a veículos que já não pertencem ao usuário.

**Arquivamento:** veículo arquivado usa soft delete (`deleted_at`) e pode ser restaurado; veículo vendido continua visível, com `status = sold` e dados de venda.

**Histórico:** `tracking_start_date` indica a partir de quando o usuário acompanha os gastos daquele veículo. É usado nas fases seguintes para sinalizar histórico incompleto.

**Odômetro:** `acquisition_odometer` permite calcular custo por quilômetro no futuro. É opcional, mas recomendado.

**Placa:** normalizada em maiúsculas, sem espaços/hífen, única quando informada; pode ser nula para veículos sem placa.

**Foto:** armazenada em disco privado e servida por rota controlada pelo próprio Laravel (sem `public/storage`).

**Cálculos:** o resultado financeiro com despesas e o custo total de propriedade ficam para a Fase 4, quando existirem despesas. Aqui só se persiste e exibe aquisição/venda.

**Fora de escopo:** despesas, abastecimentos, manutenções, FIPE, dashboard e relatórios.

## Fase 3 — Despesas

**Objetivo:** registrar categorias, despesas (inclusive retroativas), despesas recorrentes, abastecimentos e manutenções, com cálculos e validações confiáveis.

**Data efetiva:** despesas usam `occurred_on` em todos os relatórios; `created_at` nunca representa quando o gasto aconteceu.

**Recorrências:** modeladas como **modelo + ocorrências**. Previsão (`forecast`) não entra em totais realizados; ao confirmar o pagamento, uma despesa real é criada em transação e vinculada à ocorrência.

**Combustível:** consumo usa o **método de tanque cheio**. Só há km/l quando existem dois abastecimentos cheios consecutivos; abastecimentos parciais entram no cálculo do próximo tanque cheio.

**Manutenção:** detalhe 1:1 de uma despesa (a despesa carrega valor/data/categoria; a manutenção carrega tipo, odômetro e próxima troca).

**Precisão:** valores monetários em centavos; litros e preço por litro usam precisão decimal adequada.

**Datas:** passadas e futuras são aceitas para despesas avulsas; previsões de recorrência vivem separadas e não contam como pagas.

**Fora de escopo:** dashboard consolidado, relatórios, FIPE e página completa do veículo.

## Fase 4 — Indicadores e relatórios

**Objetivo:** transformar os lançamentos em indicadores confiáveis: dashboard, página completa do veículo, relatórios com filtros e exportação CSV.

**Fonte dos dados:** todos os indicadores derivam de `occurred_on` (data efetiva), nunca de `created_at`.

**Centralização:** cálculos ficam em `app/Domain/Reporting` e são a única fonte de verdade; controllers apenas validam filtros e entregam props.

**Previsões:** aparecem separadas dos valores realizados e nunca entram nos totais pagos.

**Histórico incompleto:** período anterior a `tracking_start_date` do veículo é marcado como **histórico incompleto**; nunca apresentar esse intervalo como se tivesse todos os gastos.

**TCO e resultado:** custo total de propriedade = valor de compra + despesas operacionais. Resultado da venda = valor de venda − valor de compra − despesas. Compra não é despesa operacional.

**Médias:** custo médio mensal = TCO ÷ meses de propriedade (mínimo 1); também exibir a média operacional mensal separadamente.

**Custo por km:** despesas operacionais ÷ (odômetro atual ou de venda − `acquisition_odometer`), somente quando o intervalo for positivo.

**Escopo por status:** veículos vendidos entram apenas em consultas históricas, limitados ao período aquisição→venda; veículos arquivados ficam fora dos indicadores padrão.

**FIPE:** indicador de referência separado, nunca somado a gastos. O relatório de evolução FIPE fica no item 5.5 (`tarefa32.md`).

**Gráficos:** Chart.js + vue-chartjs, importados sob demanda; nenhuma outra biblioteca de UI.

**Fora de escopo:** integração FIPE, autenticação e funcionalidades não descritas no enunciado.

## Fase 5 — Tabela FIPE

**Objetivo:** consultar valores de referência da FIPE por marca/modelo/ano/versão, manter histórico das cotações e integrar o valor de referência aos indicadores, sem confundir FIPE com gastos ou preço de venda.

**Fonte:** API pública **Parallelum FIPE v2** (`https://fipe.parallelum.com.br/api/v2`), configurável por `FIPE_BASE_URL`. A escolha prioriza endpoints de marcas, modelos, anos e valor por ano, com código FIPE e mês de referência.

**Resiliência:** toda chamada externa passa por um cliente próprio com timeout curto, retry limitado, cache curto de catálogo e tratamento explícito de erro. Falha nunca gera valor inventado.

**Histórico:** persistido no banco; a consulta mais recente atualiza os campos "atuais" do veículo, mas nunca sobrescreve o histórico.

**Transparência:** valor FIPE é referência de mercado, não avaliação garantida; a interface sempre exibe o mês de referência e a data da consulta.

**Desatualização:** cotação antiga não pode ser apresentada como atual. Se a atualização falhar, mostrar o último valor com aviso de desatualização e a data.

**Correspondência:** a seleção deve corresponder à versão e ao ano do modelo, não apenas ao nome comercial.

**Fora de escopo:** outras fontes pagas, avaliação de preço de venda e integração com leilões.

## Fase 6 — Finalização

**Objetivo:** validar o sistema de ponta a ponta, revisar experiência de uso, segurança e documentação, e entregar o MyCar pronto para uso local.

**Escopo:** nenhuma funcionalidade nova de negócio; apenas correções, testes, acessibilidade, segurança e documentação.

**Demonstração:** dados de demonstração são opcionais, idempotentes e claramente separados dos dados reais.

**Documentação:** deve permitir que outra pessoa instale e execute o projeto do zero, sem conhecimento prévio do código.

**Checklist:** a revisão final usa uma checklist derivada do enunciado, com cada requisito marcado como atendido, parcial ou fora de escopo.

**Fora de escopo:** deploy em produção, autenticação/multiusuário e novas integrações.
