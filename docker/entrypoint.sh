#!/bin/sh
set -e

[ -f .env ] || cp .env.example .env
# `artisan serve` não repassa variáveis do ambiente ao servidor; o .env é a fonte de verdade.
set_env() {
    if grep -q "^$1=" .env; then sed -i "s#^$1=.*#$1=$2#" .env; else echo "$1=$2" >> .env; fi
}
set_env APP_ENV "${APP_ENV:-production}"
set_env APP_DEBUG "${APP_DEBUG:-false}"
set_env APP_URL "${APP_URL:-http://localhost:8000}"
set_env DB_DATABASE "${DB_DATABASE:-/var/www/html/database/database.sqlite}"
touch "${DB_DATABASE:-/var/www/html/database/database.sqlite}"
# O banco só deve ser legível pelo usuário do app.
chmod 600 "${DB_DATABASE:-/var/www/html/database/database.sqlite}"

if ! grep -q '^APP_KEY=.\+' .env && [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --class=ExpenseCategorySeeder --force
# Agendador (gera as ocorrências das despesas recorrentes todo dia); a geração inicial é idempotente.
php artisan recurring:generate
php artisan schedule:work > /dev/null 2>&1 &

exec php artisan serve --host=0.0.0.0 --port=8000
