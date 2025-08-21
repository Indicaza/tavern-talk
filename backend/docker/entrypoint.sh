#!/usr/bin/env bash
set -euo pipefail

php artisan optimize:clear

until php -r 'try{$c=new PDO("pgsql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));echo "ok\n";}catch(Throwable $e){http_response_code(1);}'; do
  sleep 1
done

php artisan migrate --force || true
php artisan storage:link || true

exec ${START_CMD:-php artisan serve --host=0.0.0.0 --port=9000}
