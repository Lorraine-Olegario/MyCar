#!/bin/sh
set -e

[ -f .env ] || cp .env.example .env
# `artisan serve` não repassa variáveis do ambiente ao servidor; o .env é a fonte de verdade.
set_env() {
    if grep -q "^$1=" .env; then sed -i "s#^$1=.*#$1=$2#" .env; else echo "$1=$2" >> .env; fi
}
set_env DB_DATABASE "${DB_DATABASE:-/var/www/html/database/database.sqlite}"
touch "${DB_DATABASE:-/var/www/html/database/database.sqlite}"

if ! grep -q '^APP_KEY=.\+' .env && [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force
exec php artisan serve --host=0.0.0.0 --port=8000
