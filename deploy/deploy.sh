#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/unifco_platform"
APP_NAME="unifco-app"

cd "$APP_DIR"

echo "==> Fetching latest main from origin"
git fetch origin main

echo "==> Checking out origin/main"
git reset --hard origin/main

echo "==> Installing dependencies"
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "==> Materializing official UNIFCO logo"
php artisan brand:materialize

echo "==> Applying migrations"
php artisan migrate --force

echo "==> Clearing caches"
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo "==> Restarting $APP_NAME"
supervisorctl restart "$APP_NAME"

echo "==> Deploy complete"
