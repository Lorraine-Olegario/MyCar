# MyCar

Aplicação base com **Laravel 13**, **Vue 3**, **Vite 8** e **SQLite**.

Aplicação pessoal para acompanhar **veículos, despesas, abastecimentos e
indicadores financeiros**. O projeto está na **Fase 1 (Fundação)**: Laravel +
Inertia.js + Vue 3 integrados, layout base com navegação, utilitários de
formatação brasileira e componentes de UI reutilizáveis. Nenhuma funcionalidade
de negócio foi implementada ainda.

As convenções de código (centavos, datas, enums, domínio, testes e UI) estão em
[`docs/arquitetura.md`](docs/arquitetura.md).

## Stack

| Tecnologia         | Versão instalada | Observação                          |
| ------------------ | ---------------- | ----------------------------------- |
| PHP                | 8.5.8            | Laravel exige `^8.3`                |
| Laravel Framework  | 13.38.0          | skeleton `laravel/laravel` 13.11.0  |
| Composer           | 2.8.10           |                                     |
| Vue                | 3.5.44           |                                     |
| Vite               | 8.3.5            |                                     |
| laravel-vite-plugin| 3.2.0            |                                     |
| @vitejs/plugin-vue | 6.0.10           |                                     |
| Tailwind CSS       | 4.3.3            | padrão do skeleton oficial          |
| PHPUnit            | 12.5.40          |                                     |
| Node.js            | 20.20.0          | Vite exige `^20.19` ou `>=22.12`    |
| SQLite             | 3.51.0           |                                     |

## Requisitos

- PHP `>= 8.3` com as extensões `pdo_sqlite` e `sqlite3`
- Composer 2.x
- Node.js `>= 20.19`
- npm `>= 10`

> **Nota sobre o Node:** o comando `composer dev` usa o runner
> `@laravel/multiplex`, que declara `node >= 22.13`. No Node 20 ele funciona,
> mas o npm emite avisos de `EBADENGINE`. Para evitar os avisos, use Node 22.13+
> ou rode o servidor e o Vite em terminais separados (recomendado abaixo).

> **Nota sobre o npm:** a resolução de dependências do Vite 8 aciona um bug do
> npm 10.8.x (`Cannot read properties of null (reading 'edgesOut')`) quando não
> existe lockfile. O `package-lock.json` está versionado, então `npm install` e
> `npm ci` funcionam normalmente. Caso o erro apareça, use `npx npm@11 install`.

## Instalação

Forma completa, em um único comando (script oficial do skeleton):

```sh
composer setup
```

Ou passo a passo:

```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm ci
```

O arquivo `.env` já vem com `DB_CONNECTION=sqlite`, portanto o Laravel usa
`database/database.sqlite` automaticamente.

## Ambiente de desenvolvimento

Recomendado — dois terminais:

```sh
# terminal 1: servidor PHP
php artisan serve

# terminal 2: Vite com HMR
npm run dev
```

Alternativa — um único processo (PHP + Vite + queue + logs):

```sh
composer dev
```

Acesse <http://localhost:8000>.

## Build de produção

```sh
npm run build
```

Os arquivos são gerados em `public/build` e servidos pelo Laravel.

## Testes

```sh
php artisan test
# equivalente
composer test
```

Os testes usam SQLite em memória (`:memory:`), então não afetam o banco local.

Para os utilitários de formatação/conversão (Vitest):

```sh
npm test
```

## Banco de dados

- Arquivo: `database/database.sqlite`
- Recriar as migrations: `php artisan migrate:fresh`
- Inspecionar: `sqlite3 database/database.sqlite ".tables"`

## Estrutura

```
app/Http/Middleware/HandleInertiaRequests.php  # props compartilhadas do Inertia
resources/
  css/app.css                     # entrada do Tailwind CSS
  js/app.js                       # entrypoint: createInertiaApp + layout padrão
  js/layouts/AppLayout.vue        # layout base: sidebar, cabeçalho e drawer
  js/pages/                       # páginas Inertia (Dashboard, Vehicles, ...)
  js/components/ui/               # componentes base de UI
  js/utils/format.js              # formatação brasileira (moeda, data, km)
  views/app.blade.php             # template raiz com @inertia e @vite
routes/web.php                    # rotas nomeadas das seções
database/migrations/              # migrations iniciais (users, cache, jobs)
tests/Feature/NavigationTest.php  # testes das rotas e da 404 Inertia
vite.config.js                    # Laravel + Vue + Tailwind
vitest.config.js                  # Vitest (utilitários de formatação)
```

O `HandleInertiaRequests` compartilha o `appName` e as flash messages com as
páginas; o `app.js` resolve as páginas em `resources/js/pages` e aplica o
`AppLayout` como layout padrão.

## Arquivos locais não versionados

`.env`, `database/*.sqlite*`, `node_modules`, `public/build`, `public/hot` e
`vendor` estão no `.gitignore`. Nenhuma credencial é versionada.

## Docker

```bash
docker compose up -d --build   # http://localhost:8000
docker compose down            # os dados ficam nos volumes (use -v para apagá-los)
```

O container aplica as migrations ao iniciar e guarda o SQLite em um volume (`/data`).
