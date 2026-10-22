# Checklist de requisitos

> **Origem dos requisitos.** O enunciado original não está versionado neste repositório. Esta checklist foi derivada do
> que está registrado em [`tarefas/README.md`](../tarefas/README.md) (convenções globais e as seis fases) e nos 37 arquivos
> de tarefa, que são a especificação usada na implementação. As "seções 2 a 13" abaixo agrupam esses requisitos.
> Status: **Atendido**, **Parcial** ou **Fora de escopo** (decidido nas tarefas).

## 2. Fundação e convenções

| Requisito | Status | Onde |
|---|---|---|
| Laravel + Vue 3 + Inertia, sem API REST separada | Atendido | `app.js`, controllers Inertia · `tests/Feature/NavigationTest.php` |
| SQLite, migrations, seeders | Atendido | `database/` · `migrate:fresh --seed` na regressão |
| Dinheiro em centavos; conversão só na borda | Atendido | colunas `*_cents`, `utils/format.js` · `format.test.js` |
| Datas `date`, fuso `America/Sao_Paulo`, `dd/mm/aaaa` | Atendido | `format.js`, `config/app.php` |
| UI própria com Tailwind, estados de carregamento/vazio/erro, confirmação de exclusão | Atendido | `components/ui/*` · `docs/revisao-ux.md` |
| Sem autenticação nesta versão | Fora de escopo | decisão do projeto (README: não expor sem proxy autenticado) |

## 3. Veículos

| Requisito | Status | Onde |
|---|---|---|
| Cadastro/edição com placa normalizada e única, odômetro, aquisição e venda | Atendido | `VehicleController`, `StoreVehicleRequest` · `VehicleControllerTest`, `VehicleFormTest` |
| Foto em disco privado, servida por rota controlada | Atendido | `VehiclePhotoStorage` · `VehiclePhotoTest`, `SecurityTest` |
| Arquivar (soft delete), restaurar, vendidos continuam consultáveis | Atendido | `VehicleHistoryTest`, `FullFlowTest` |
| `tracking_start_date` para sinalizar histórico incompleto | Atendido | `FinancialQueries::completeness` · `ReportingQueriesTest`, `FullFlowTest` |
| Filtro histórico com contadores e período de propriedade | Atendido | `Vehicles/Index.vue` · `VehicleHistoryTest` |

## 4. Categorias e despesas

| Requisito | Status | Onde |
|---|---|---|
| 17 categorias iniciais, seed idempotente, CRUD, ativar/desativar, reordenar, não excluir em uso | Atendido | `ExpenseCategorySeeder`, `ExpenseCategoryController` · `ExpenseCategoryTest` |
| Despesas retroativas e futuras; totais por `occurred_on` | Atendido | `ExpenseController` · `ExpenseTest`, `ReportingQueriesTest` |
| Comprovante privado, substituição/remoção sem arquivo órfão | Atendido | `ExpenseReceiptStorage` · `ExpenseTest`, `ExpensePagesTest` |
| Categoria inativa só em registros antigos | Atendido | `StoreExpenseRequest` · `ExpenseTest` |

## 5. Despesas recorrentes e vencimentos

| Requisito | Status | Onde |
|---|---|---|
| Modelo + ocorrências; mensal/trimestral/semestral/anual; `due_day` em meses curtos | Atendido | `OccurrenceGenerator` · `RecurringExpenseTest` |
| Geração idempotente e agendada (`recurring:generate`, diária) | Atendido | `routes/console.php` · `RecurringExpenseTest` |
| Confirmar pagamento cria exatamente uma despesa; bloquear duplicidade; cancelar previsão | Atendido | `RecurringExpenseService` · `RecurringExpenseTest`, `RecurringExpensePagesTest` |
| Previsão nunca conta como pago; estimativa mensal separada do pago | Atendido | `monthSummary`, `ForecastVsPaidReport` · `ReportsAdvancedTest` |
| Próximos vencimentos (recorrências e manutenções) reutilizáveis | Atendido | `UpcomingDueList.vue`, `MaintenanceDueQueries` · `DashboardTest`, `VehicleHistoryTabsTest` |

## 6. Abastecimentos e manutenções

| Requisito | Status | Onde |
|---|---|---|
| Consumo pelo método do tanque cheio; sem dados = "sem dados suficientes" | Atendido | `FuelConsumptionCalculator` · `FuelConsumptionCalculatorTest`, `FuelingPagesTest` |
| Validação de odômetro por veículo e duplicidade com confirmação | Atendido | `StoreFuelingRequest` · `FuelingTest` |
| Total automático com ajuste manual; odômetro do veículo = maior conhecido | Atendido | `FuelingRecorder` · `FuelingTest` |
| Manutenção 1:1 com a despesa, em transação; próxima troca por data/odômetro | Atendido | `MaintenanceService` · `MaintenanceTest` |
| Abastecimentos somados às despesas | Fora de escopo | registro à parte por decisão das tarefas 17/18 (documentado no README) |

## 7. Indicadores e dashboard

| Requisito | Status | Onde |
|---|---|---|
| Serviço único de consultas (período inclusivo, mês/ano, categoria, veículo) | Atendido | `app/Domain/Reporting/FinancialQueries` · `ReportingQueriesTest` |
| TCO, custo médio mensal, custo por km, resultado da venda | Atendido | `vehicleSummary` · `ReportingQueriesTest`, `VehicleShowTest` |
| Dashboard com cards, evolução, categorias, comparativo, vencimentos e filtros na URL | Atendido | `DashboardOverview`, `Dashboard.vue` · `DashboardTest` |
| Compra ≠ despesa; previsto ≠ pago; FIPE ≠ gasto | Atendido | `DashboardTest`, `FipeReportingTest`, `FullFlowTest` |
| Veículos vendidos/arquivados fora dos indicadores padrão | Atendido | `ReportFilters` · `ReportingQueriesTest`, `FullFlowTest` |

## 8. Página do veículo

| Requisito | Status | Onde |
|---|---|---|
| Visão geral e financeiro com seletor de ano/mês e histórico incompleto | Atendido | `VehicleFinancialOverview` · `VehicleShowTest` |
| Históricos paginados (despesas, abastecimentos, manutenções, recorrências, vencimentos) | Atendido | `VehicleHistory` · `VehicleHistoryTabsTest` |
| Links para editar sem duplicar formulários (`?edit=ID`) | Atendido | `VehicleHistoryTabsTest::test_edit_deep_links…` |

## 9. Relatórios

| Requisito | Status | Onde |
|---|---|---|
| Relatórios 1–5 (mensal e anual por veículo, histórico, comparativo, categoria) | Atendido | `Reports/*` · `ReportsTest` |
| Relatórios 6, 7, 9, 10 (consumo, previsto × pago, TCO, resultado da venda) | Atendido | `ReportsAdvancedTest` |
| Relatório 8 (evolução FIPE com lacunas) | Atendido | `FipeEvolutionReport` · `FipeReportingTest` |
| Filtros, totais, tabela/gráfico, filtros aplicados no cabeçalho, paginação | Atendido | `ReportController`, `Reports/Show.vue` · `ReportsTest` |
| Mesmos valores no dashboard e nos relatórios para o mesmo filtro | Atendido | `ReportsTest`, `FullFlowTest` |

## 10. Exportação CSV

| Requisito | Status | Onde |
|---|---|---|
| UTF-8 com BOM, `;`, decimal com vírgula, datas `dd/mm/aaaa`, cabeçalhos claros | Atendido | `ReportCsv` · `CsvExportTest` |
| Streaming, filtros ativos, todas as linhas, nome com relatório e período | Atendido | `ReportExportController` · `CsvExportTest` |
| Sem dados sensíveis; fórmulas neutralizadas | Atendido | `CsvExportTest` |

## 11. Tabela FIPE

| Requisito | Status | Onde |
|---|---|---|
| Cliente HTTP resiliente (timeout, retry limitado, cache só do catálogo, erros em português) | Atendido | `FipeClient` · `FipeClientTest` |
| Histórico de cotações; atual = último registro; mês de referência da fonte | Atendido | `FipeQuote`, `FipeQuoteService` · `FipeQuoteTest`, `FipeServiceTest` |
| Combinação deve corresponder ao veículo (marca, modelo/versão, ano) | Atendido | `FipeVehicleMatcher` · `FipeServiceTest` |
| Falha preserva o valor anterior com aviso de desatualização; nunca valor inventado | Atendido | `FipeServiceTest`, `FipePageTest` |
| Interface: cascata, card, atualizar/tentar novamente, histórico, aviso "referência, não preço garantido" | Atendido | `VehicleFipe.vue` · `FipePageTest` (+ verificação manual com a API real) |
| FIPE nos indicadores (só ativos e cotações atuais) e relatório 8 | Atendido | `FipeFleetSummary` · `FipeReportingTest` |
| Outras fontes pagas, avaliação de preço de venda, leilões | Fora de escopo | decisão da Fase 5 |

## 12. Qualidade, UX e segurança

| Requisito | Status | Onde |
|---|---|---|
| Responsivo (360/768/1280), teclado, foco, rótulos, contraste | Atendido | `docs/revisao-ux.md` |
| Leitor de tela testado manualmente | Parcial | só estrutura semântica verificada (documentado) |
| Arquivos em disco privado, MIME real, tamanho, nomes gerados, sem path traversal | Atendido | `SecurityTest` |
| Mass assignment, CSRF, erros sem detalhes, cabeçalhos, rate limit FIPE | Atendido | `SecurityTest` |
| `composer audit` e `npm audit` | Atendido | sem vulnerabilidades na regressão final |
| `.env.example` sem segredos; `APP_DEBUG=false` documentado | Atendido | `SecurityTest::test_env_example…` |

## 13. Entrega

| Requisito | Status | Onde |
|---|---|---|
| README para instalar/executar do zero (requisitos, seed, testes, agendador, backup, problemas) | Atendido | `README.md` (instalação limpa validada) |
| Arquitetura e convenções documentadas | Atendido | `docs/arquitetura.md` |
| Dados de demonstração opcionais, idempotentes e separados | Atendido | `DemoData`, `mycar:demo` · `DemoDataTest` |
| Execução em contêiner | Atendido | `Dockerfile`, `docker-compose.yml` |
| Deploy em produção, autenticação/multiusuário, novas integrações | Fora de escopo | decisão da Fase 6 |

## Regressão final

| Comando | Resultado |
|---|---|
| `php artisan migrate:fresh --seed` (banco temporário) | 10 migrations, 17 categorias |
| `composer test` | 348 testes, 3884 asserções, verdes (também em ordem aleatória) |
| `npm test` | 32 testes (5 arquivos) verdes |
| `npm run build` | ok |
| `vendor/bin/pint --test` | ok |
| TODO/FIXME/`dd()`/`console.log` em `app`, `resources`, `routes`, `database`, `tests`, `config` | nenhum |
| Testes ignorados/incompletos | nenhum |
| Log da aplicação | testes gravam no canal `null`; sem erros do Docker no log do container |

## Roteiro manual registrado

Executado no Docker (`docker compose up -d --build`) com `php artisan mycar:demo seed --force`: um veículo **atual** (Onix) e um
**vendido** (Fit), despesas em anos diferentes e uma **recorrência anual** (seguro) com pagamento confirmado.

| Verificação | Cálculo manual | Tela |
|---|---|---|
| Resultado da venda do Fit | 41.000 − 48.000 − 8.200 = **−15.200** | R$ −15.200,00 (50 meses) |
| Despesas do Onix desde a aquisição | 350+2.200+3.600+1.900+480+90+45+380+3.720 = **12.765** | R$ 12.765,00 |
| TCO do Onix | 75.000 + 12.765 = **87.765** | R$ 87.765,00 |
| Custo médio mensal (30 meses) | 87.765 ÷ 30 = **2.925,50** | R$ 2.925,50 |
| Despesas de 2026 do Onix | 1.900+480+90+45+380+3.720 = **6.615** | R$ 6.615,00 |
| Custo por km (40.800 km rodados) | 12.765 ÷ 40.800 ≈ **0,31** | R$ 0,31/km |
| FIPE separada dos gastos, com variação | 65.500 − 67.000 = −1.500 (−2,2%) | card FIPE; TCO e gastos não a incluem |
| Recorrência anual | paga confirmada = 1 despesa; previsão futura fora do realizado | conferido em `FullFlowTest` e na tela |

## Pendências conhecidas (não bloqueantes)

- Teste manual com leitor de tela.
- A cotação FIPE real foi consultada manualmente uma vez (API pública); os testes automatizados usam apenas rede fake.
