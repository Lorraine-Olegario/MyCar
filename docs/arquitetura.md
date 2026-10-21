# Arquitetura e convenções do MyCar

Aplicação pessoal (sem autenticação nesta versão) para acompanhar veículos,
despesas, abastecimentos e indicadores financeiros. Stack: Laravel, Inertia.js,
Vue 3, Vite, Tailwind CSS, SQLite e PHPUnit.

## Camadas e organização

- **Controllers finos** em `app/Http`: validam a entrada, delegam e devolvem a
  resposta (`Inertia::render` ou redirecionamento). Sem regra de negócio.
- **Models Eloquent** em `app/Models`.
- **Regras e cálculos de domínio** em `app/Domain/<Contexto>`
  (ex.: `app/Domain/Vehicles`, `app/Domain/Expenses`, `app/Domain/Reporting`),
  independentes de HTTP.
- **Orquestração** em `app/Services` apenas quando houver benefício real
  (múltiplas etapas ou colaboração entre contextos).
- **Sem repositórios genéricos**.
- **Frontend** com Inertia.js + Vue 3: rotas, validação e regras no backend;
  páginas em `resources/js/pages`. Sem API REST separada.

## Moeda

- Valores monetários persistidos em **centavos inteiros**, com sufixo `_cents`
  (ex.: `amount_cents`). Nunca usar `float` para dinheiro.
- Conversão para reais somente na borda, com `formatCurrency` e
  `parseCurrencyInput` (`resources/js/utils/format.js`).
- Exibição no padrão `R$ 1.234,56`.

## Datas e fuso

- Campos de negócio em `date` (sem hora); `created_at` nunca representa quando
  o gasto aconteceu — usar `occurred_on`.
- Timezone `America/Sao_Paulo`, locale `pt_BR`.
- Exibição `dd/mm/aaaa` (`formatDate`) e mês/ano por extenso
  (`formatMonthYear`).
- Datas em string ISO são lidas literalmente para evitar deslocamento de fuso.

## Enums

- Usar enums nativos do PHP (backed enums) para estados fechados
  (ex.: status do veículo, tipo de despesa).
- Persistir o `value`; evitar "strings mágicas" espalhadas pelo código.
- Rótulos de interface em português, definidos junto ao enum ou na apresentação.

## Testes

- **Backend:** PHPUnit em `tests/Feature` e `tests/Unit`
  (`composer test` ou `php artisan test`), com SQLite em memória.
- **Frontend:** Vitest apenas para utilitários de formatação/conversão
  (`npm test`).
- Nenhum teste ignorado ou pulado; cálculos e regras de negócio devem ser
  cobertos.

## Componentes de UI

- Sem biblioteca de componentes externa; componentes próprios com Tailwind.
- Base reutilizável em `resources/js/components/ui`; as páginas consomem esses
  componentes.
- Acessibilidade: foco visível, `aria-current`/`aria-invalid`/`role`
  apropriados e link "pular para o conteúdo".
- **Confirmação obrigatória** antes de excluir ou arquivar (usar
  `ConfirmDialog`).
- Flash messages via props compartilhadas do Inertia
  (`flash.success`/`flash.error`/`flash.message`), exibidas pelo
  `FlashMessages`.

## Arquivos enviados

- Fotos, comprovantes e notas ficam em disco privado, servidos por rotas
  controladas pelo Laravel, com validação de MIME e tamanho. Sem
  `public/storage`.

## Configuração

- `APP_NAME=MyCar`, `APP_LOCALE=pt_BR`, `APP_FALLBACK_LOCALE=en`,
  `APP_FAKER_LOCALE=pt_BR`, `APP_TIMEZONE=America/Sao_Paulo` e
  `DB_CONNECTION=sqlite`.
- `.env` não versionado; `database/*.sqlite*`, `vendor`, `node_modules` e
  `public/build` ignorados. Nenhuma credencial versionada.
