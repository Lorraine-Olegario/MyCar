# MyCar

Aplicação **pessoal e local** para acompanhar **veículos, despesas, abastecimentos, manutenções, vencimentos e
indicadores financeiros**, com consulta à **Tabela FIPE**, relatórios e exportação CSV. Sem login: os dados ficam
em um arquivo SQLite na sua máquina.

**Stack:** Laravel 13 · PHP 8.5 · Inertia.js + Vue 3 · Vite · Tailwind CSS · SQLite · PHPUnit ·
Chart.js (carregado sob demanda).

## O que o sistema faz

| Área | Resumo |
|---|---|
| Veículos | Cadastro com foto, histórico (ativo, vendido, arquivado), período de propriedade e restauração. |
| Despesas | Lançamentos (inclusive de anos anteriores), categorias, comprovantes, filtros e total do filtro. |
| Recorrentes | Seguro, IPVA etc. como *modelo + ocorrências*: previsão nunca vira gasto até confirmar o pagamento. |
| Abastecimentos | Consumo pelo método do **tanque cheio**; sem dados suficientes, nada é estimado. |
| Manutenções | Histórico ligado a uma despesa, com próxima troca por data/odômetro. |
| Dashboard e veículo | Totais, evolução mensal, categorias, comparativos, vencimentos, TCO, custo por km, resultado da venda. |
| Relatórios (10) | Mensal/anual por veículo, histórico, comparativo, categoria, consumo, previsto × pago, FIPE, TCO e venda; todos exportam **CSV**. |
| FIPE | Seleção marca → modelo → ano, valor de referência, histórico e variação, sempre separados dos gastos. |

## Requisitos

- PHP `>= 8.3` (testado com 8.5) com as extensões `pdo_sqlite`, `sqlite3` e `mbstring`
- Composer 2.x
- Node.js `>= 20.19` e npm `>= 10`
- Opcional: Docker (Docker Desktop ou Engine com Compose)

## Instalação do zero

### Opção A — Docker (mais rápida)

```bash
docker compose up -d --build   # http://localhost:8000
```

O container aplica as migrations, semeia as categorias, gera as ocorrências recorrentes e roda o agendador. O SQLite
fica no volume `mycar-data` (`/data`) e os arquivos enviados no volume `mycar-storage`.

```bash
docker compose logs -f app     # acompanhar logs
docker compose down            # para; os dados continuam nos volumes
docker compose down -v         # para e APAGA os dados
docker compose exec app php artisan mycar:demo seed --force   # opcional: dados de demonstração
```

### Opção B — local

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
chmod 600 database/database.sqlite     # só o seu usuário deve ler o banco
php artisan migrate --seed             # tabelas + as 17 categorias de despesas
npm ci
npm run build                          # ou `npm run dev` durante o desenvolvimento
php artisan serve                      # http://localhost:8000
```

Atalho oficial do skeleton: `composer setup`. Em desenvolvimento, use dois terminais (`php artisan serve` e
`npm run dev`) ou `composer dev`.

> **Node 20:** o `composer dev` usa um runner que pede Node ≥ 22.13 (funciona no 20 com avisos). Se `npm install`
> falhar sem lockfile com `edgesOut`, use `npx npm@11 install`; o `package-lock.json` versionado evita isso com `npm ci`.

## Configuração (`.env`)

| Variável | Padrão | Para que serve |
|---|---|---|
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | **Uso real: `production` e `false`** (nunca exponha detalhes de erro). |
| `APP_URL`, `APP_TIMEZONE`, `APP_LOCALE` | `localhost:8000`, `America/Sao_Paulo`, `pt_BR` | Endereço, fuso e idioma. |
| `DB_CONNECTION` / `DB_DATABASE` | `sqlite` / `database/database.sqlite` | Banco. |
| `SESSION_SECURE_COOKIE` | comentado | Ative atrás de HTTPS. |
| `FIPE_BASE_URL`, `FIPE_TOKEN` | API pública Parallelum v2, vazio | Fonte FIPE (o token é opcional). |
| `FIPE_TIMEOUT`, `FIPE_RETRIES`, `FIPE_CACHE_TTL`, `FIPE_STALE_DAYS` | `5`, `2`, `86400`, `35` | Timeout (s), tentativas, cache do catálogo (s) e idade máxima de uma cotação "atual". |
| `MYCAR_DEMO` | `false` | Com `true`, `php artisan db:seed` também cria os dados de demonstração. |

## Migrations, seed e dados de demonstração

```bash
php artisan migrate                 # aplica as migrations
php artisan db:seed                 # categorias (idempotente) + usuário técnico; NÃO cria demo
php artisan migrate:fresh --seed    # recria tudo (apaga os dados!)
```

**Demonstração (opcional e separada dos dados reais).** Cria dois veículos identificados por `[DEMO]` (um ativo e um
vendido), com despesas de vários anos, abastecimentos, manutenção, recorrências e cotações FIPE de exemplo:

```bash
php artisan mycar:demo seed         # pergunta antes; use --force para não perguntar
php artisan mycar:demo clear        # remove somente os veículos [DEMO] e o que depende deles
MYCAR_DEMO=true php artisan db:seed # alternativa: demo junto do seed
```

A criação é idempotente (por placa reservada `DEM0A01`/`DEM0B02`) e um `db:seed` comum **nunca** cria demonstração.
As cotações FIPE da demo são fictícias e marcadas com `payload.demo`.

## Execução e build

```bash
php artisan serve        # servidor PHP
npm run dev              # Vite com HMR (desenvolvimento)
npm run build            # assets de produção em public/build
```

## Testes e qualidade

```bash
composer test                 # PHPUnit (SQLite em memória, datas fixas, rede sempre fake)
npm test                      # Vitest (utilitários de formatação, gráficos, filtros)
vendor/bin/pint --test        # estilo de código
npm run build                 # garante que o frontend compila
```

Os testes nunca chamam a rede real (`Http::fake` + `preventStrayRequests`) e não dependem de ordem
(`vendor/bin/phpunit --order-by=random`).

## Agendador (despesas recorrentes)

O comando `recurring:generate` (agendado **diariamente**) cria, de forma idempotente, as previsões dos próximos 12 meses.
Para ele rodar sozinho, mantenha o agendador ativo:

```bash
php artisan schedule:work                                  # em um terminal (desenvolvimento)
* * * * * cd /caminho/MyCar && php artisan schedule:run    # cron (uso contínuo)
php artisan recurring:generate --months=24                 # execução manual
```

No Docker o agendador já é iniciado pelo container.

## Backup e restauração

Tudo o que importa está em **dois lugares**: o banco SQLite e os arquivos enviados (`storage/app/private`: fotos e comprovantes).

```bash
# backup consistente do banco (mesmo com o app rodando)
sqlite3 database/database.sqlite ".backup 'backup-$(date +%F).sqlite'"
tar czf anexos-$(date +%F).tgz storage/app/private

# restaurar: pare o app, substitua o arquivo e (se necessário) rode as migrations
cp backup-2026-01-31.sqlite database/database.sqlite && php artisan migrate
```

No Docker:

```bash
docker compose exec app sqlite3 /data/database.sqlite ".backup '/data/backup.sqlite'"   # se o sqlite3 estiver instalado
docker run --rm -v mycar_mycar-data:/data -v "$PWD":/out alpine cp /data/database.sqlite /out/backup.sqlite
```

## Tabela FIPE

- **Fonte:** API pública **Parallelum FIPE v2** (`https://fipe.parallelum.com.br/api/v2`), configurável por `FIPE_BASE_URL`.
  Endpoints usados: marcas, modelos, anos e valor por ano (o token `X-Subscription-Token` é opcional).
- **Resiliência:** todo acesso passa pelo `FipeClient` (timeout de 5 s, até 2 tentativas só para falha de conexão/5xx,
  erros tipados em português). Falha **nunca** gera valor inventado.
- **Cache:** só o catálogo (marcas/modelos/anos) por 24 h. O valor do veículo nunca é cacheado: cada consulta é gravada
  no histórico com o **mês de referência devolvido pela própria FIPE**.
- **Correspondência:** a cotação só é aceita se marca, modelo/versão e ano do modelo baterem com o veículo.
- **Desatualização:** cotação com mais de 35 dias é marcada como desatualizada e não entra no valor da frota do dashboard.
  Se a atualização falhar (FIPE fora do ar), o último valor é mantido, com aviso e a data da consulta.
- **Limites:** 60 consultas de catálogo/min e 10 consultas de valor/min por IP.
- **Transparência:** a FIPE é referência de mercado, **não** preço garantido de venda, e nunca é somada a gastos, TCO ou resultado de venda.

## Segurança e uso real

- Sem autenticação por decisão do projeto (uso pessoal). **Não exponha a porta na internet** sem um proxy com autenticação/HTTPS.
- Fotos e comprovantes em disco privado, servidos só por rotas controladas, com validação de tipo/tamanho e nomes gerados.
- Para uso real: `APP_ENV=production`, `APP_DEBUG=false`, `chmod 600` no SQLite, `SESSION_SECURE_COOKIE=true` atrás de HTTPS.
- Cabeçalhos de segurança, proteção CSRF e `composer audit`/`npm audit` sem pendências na última revisão.

## Estrutura

```
app/Domain/        # regras e cálculos (Vehicles, Expenses, Recurring, Fuel, Maintenance, Reporting, Fipe, Demo)
app/Http/          # controllers finos, requests de validação, middleware
app/Models/        # Eloquent
app/Enums/         # estados fechados (status, categorias, frequência...)
resources/js/      # páginas Inertia, componentes (ui, reporting, vehicle, recurring), utilitários
database/          # migrations, factories, seeders (categorias e demonstração)
docs/              # arquitetura, revisão de UX, checklist
tests/             # Feature e Unit (PHPUnit) — resources/js/**/*.test.js (Vitest)
```

Detalhes e convenções em [`docs/arquitetura.md`](docs/arquitetura.md).

## Convenções importantes

- **Dinheiro em centavos** inteiros (`*_cents`); conversão para reais só na borda.
- **Datas de negócio** em `date`, fuso `America/Sao_Paulo`, exibição `dd/mm/aaaa`. Totais usam sempre a data efetiva
  (`occurred_on`), nunca `created_at`.
- **Compra não é despesa operacional.** TCO = compra + despesas; resultado da venda = venda − compra − despesas.
- **Previsto ≠ pago**, **FIPE ≠ gasto**: sempre em campos, colunas e totais separados.
- **Histórico incompleto:** períodos anteriores ao início do acompanhamento do veículo são sinalizados.

## Limitações conhecidas

- Um único usuário, sem login nem permissões; sem multiusuário/multiempresa.
- Abastecimentos são um registro à parte e **não** entram nos totais de despesas (use a categoria Combustível se quiser somar).
- O consumo só existe entre dois tanques cheios consecutivos.
- Relatórios longos são paginados em memória (adequado ao porte pessoal).
- A FIPE depende de uma API pública de terceiros, sem SLA; o catálogo é cacheado por 24 h.
- SQLite é o único banco testado.

## Solução de problemas

| Sintoma | O que fazer |
|---|---|
| Tela em branco / erro de Vite (`Vite manifest not found`) | Rode `npm run build` (ou `npm run dev`). |
| `no such table` | Rode `php artisan migrate`. Sem categorias: `php artisan db:seed`. |
| `Database file ... does not exist` | `touch database/database.sqlite` e confira `DB_DATABASE` no `.env`. |
| `database is locked` | Feche outras conexões ao SQLite (ex.: cliente gráfico) e tente de novo. |
| Foto/comprovante não aparece | Os arquivos ficam em `storage/app/private`; confira permissões e se o volume foi mantido. |
| FIPE "indisponível" ou "demorou demais" | É a API externa: use "Tentar novamente"; o último valor foi preservado. |
| Previsões recorrentes não aparecem | Rode `php artisan recurring:generate` e mantenha o agendador ativo. |
| Porta 8000 ocupada | `php artisan serve --port=8001` (ou mude a porta no `docker-compose.yml`). |
| Docker mostra tela antiga | `docker compose up -d --build` para refazer a imagem. |
