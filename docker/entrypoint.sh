#!/bin/sh
set -e

cd /var/www/html

# Render / reverse proxy: Apache escuta na PORT dinâmica
PORT="${PORT:-80}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/public \
  storage/app/visitas/fotos storage/app/visitas/audios bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true

if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Só cacheia se APP_KEY existir (senão o build de health falha cedo)
if [ -n "${APP_KEY:-}" ]; then
  php artisan config:cache || true
  php artisan route:cache || true
  php artisan view:cache || true
fi

# Migrações (Postgres/MySQL do Render). Ignore se DB ainda não estiver pronta no 1º boot.
if [ "${RUN_MIGRATIONS:-true}" = "true" ] && [ -n "${APP_KEY:-}" ]; then
  php artisan migrate --force || echo "migrate: falhou (verifique DB_*); subindo mesmo assim"
fi

# Telegram: registra webhook HTTPS se estiver ligado
if [ "${TELEGRAM_ENABLED:-false}" = "true" ] && [ -n "${TELEGRAM_WEBHOOK_SECRET:-}" ] && [ -n "${TELEGRAM_BOT_TOKEN:-}" ]; then
  php artisan telegram:configurar-webhook || echo "telegram webhook: falhou (confira APP_URL https)"
fi

exec apache2-foreground
